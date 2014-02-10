<?php

class MugoMemCacheTemplateFunction
{
    const DEFAULT_TTL = 7200; // 2 hours = 60*60*2

    /*!
     Initializes the object with names.
    */
    function __construct( $blockName = 'mcache-block' )
    {
        $this->BlockName = $blockName;
    }

    /*!
     Returns an array containing the name of the block function, default is "block".
     The name is specified in the constructor.
    */
    function functionList()
    {
        return array( $this->BlockName );
    }

    function functionTemplateHints()
    {
        return array( $this->BlockName => array( 'parameters' => true,
                                                 'static' => false,
                                                 'transform-children' => true,
                                                 'tree-transformation' => true,
                                                 'transform-parameters' => true ) );
    }

    function templateNodeTransformation( $functionName, &$node,
                                         $tpl, $parameters, $privateData )
    {
        $ini = eZINI::instance();
        $children = eZTemplateNodeTool::extractFunctionNodeChildren( $node );
        if ( $ini->variable( 'TemplateSettings', 'TemplateCache' ) != 'enabled' )
        {
            return $children;
        }

        // ttl
        $ttl = $this->getTtl( $parameters );
        
        // subtree expiry
        $subTreeExpiry = isset( $parameters[ 'subtree_expiry' ] ) ? $parameters[ 'subtree_expiry' ][0][1] : null;
        $subTreeExpiryText = eZPHPCreator::variableText( $subTreeExpiry, 0, 0, false );

        // node expiry
        $nodeExpiry = isset( $parameters[ 'node_expiry' ] ) ? $parameters[ 'node_expiry' ][0][1] : null;
        $nodeExpiryText = eZPHPCreator::variableText( $nodeExpiry, 0, 0, false );
        
        // lock
        $lock = isset( $parameters[ 'lock' ] ) ? $parameters[ 'lock' ][0][1] : true;
        $lockText = eZPHPCreator::variableText( $lock, 0, 0, false );

        // build cache keys
        $cacheKeysData = array();
        
        // add siteaccess to cache keys
        $cacheKeysData[ 'accessName' ] = isset( $GLOBALS[ 'eZCurrentAccess' ][ 'name' ] ) ? $GLOBALS[ 'eZCurrentAccess' ][ 'name' ] : false;

        // user defined cache keys
        // Kinda strange how the keys are hidden in sub arrays
        $cacheKeysData[ 'custom' ] = !empty( $parameters[ 'keys' ] ) ? $parameters[ 'keys' ][0][1] : array();

        // Get mcache-block position
        $functionPlacement = eZTemplateNodeTool::extractFunctionNodePlacement( $node );
        $cacheKeysData[ 'placementKeyString' ] = eZTemplateCacheBlock::placementString( $functionPlacement );

        // render output
        $newNodes = array();

        $cacheKeysDataText = eZPHPCreator::variableText( $cacheKeysData, 0, 0, false );
                
        $code  = "\$cacheKeys = $cacheKeysDataText;\n";
        $code .= "\$mugoMemCacheBock = MugoMemCacheBlock::instance();\n";
        $code .= "\$contentData =\n  \$mugoMemCacheBock->get( \$cacheKeys, $lockText );\n";
        $code .=
            "if ( \$contentData !== false )\n" .
            "{\n";

        $newNodes[] = eZTemplateNodeTool::createCodePieceNode( $code, array( 'spacing' => 0 ) );
        $newNodes[] = eZTemplateNodeTool::createWriteToOutputVariableNode( 'contentData', array( 'spacing' => 4 ) );
        $newNodes[] = eZTemplateNodeTool::createCodePieceNode( "    unset( \$contentData );\n" .
                                                               "}\n" .
                                                               "else\n" .
                                                               "{\n" .
                                                               "    unset( \$contentData );" );

        $newNodes[] = eZTemplateNodeTool::createOutputVariableIncreaseNode( array( 'spacing' => 4 ) );
        $newNodes[] = eZTemplateNodeTool::createSpacingIncreaseNode( 4 );
        $newNodes = array_merge( $newNodes, $children );
        $newNodes[] = eZTemplateNodeTool::createSpacingDecreaseNode( 4 );
        $newNodes[] = eZTemplateNodeTool::createAssignFromOutputVariableNode( 'cachedText', array( 'spacing' => 4 ) );

        $code =
            "\$mugoMemCacheBock->put( \$cacheKeys, \$cachedText, $ttl, $subTreeExpiryText, $nodeExpiryText );\n";

        $newNodes[] = eZTemplateNodeTool::createCodePieceNode( $code, array( 'spacing' => 4 ) );
        $newNodes[] = eZTemplateNodeTool::createOutputVariableDecreaseNode( array( 'spacing' => 4 ) );
        $newNodes[] = eZTemplateNodeTool::createWriteToOutputVariableNode( 'cachedText', array( 'spacing' => 4 ) );
        $newNodes[] = eZTemplateNodeTool::createCodePieceNode( "    unset( \$cachedText, \$mugoMemCacheBock );\n}\n" );

        return $newNodes;
    }

    /*!
     Processes the function with all it's children.
    */
    function process( $tpl, &$textElements, $functionName, $functionChildren, $functionParameters, $functionPlacement, $rootNamespace, $currentNamespace )
    {
        die('process');
        switch ( $functionName )
        {
            case $this->BlockName:
            {
                var_dump( $textElements );
                die('not sure if that is getting called');
            }
            break;
        }
    }

    private function getTtl( $parameters )
    {
        $return = null;
        
        if( isset( $parameters[ 'expiry' ] ) )
        {
            if ( eZTemplateNodeTool::isConstantElement( $parameters[ 'expiry' ] ) )
            {
                $expiryValue = eZTemplateNodeTool::elementConstantValue( $parameters['expiry'] );
                $return = $expiryValue > 0 ? eZPHPCreator::variableText( $expiryValue , 0, 0, false ) : 'null';
            }
            else
            {
                $newNodes[] = eZTemplateNodeTool::createVariableNode( false, $parameters['expiry'], false, array(), 'localExpiry' );
                $return = "( \$localExpiry > 0 ? \$localExpiry : null )";
            }
        }
        else
        {
            $return = eZPHPCreator::variableText( self::DEFAULT_TTL, 0, 0, false );
        }
        
        return $return;
    }
    
    /*!
     Returns true.
    */
    function hasChildren()
    {
        return true;
    }

    /// \privatesection
    /// Name of the function
    public $BlockName;
}
