<?php

class MugoMemCacheTemplateOperators
{
    
    function __construct()
    {
    }

    function operatorList()
    {
        return array( 'insideBlock' );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'insideBlock' => array( ) );
    }

    function modify( $tpl, $operatorName, $operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch ( $operatorName )
        {
            case 'insideBlock':
            {
                sleep( 3 );
                $operatorValue = 'Heel';
                
                error_log( 'InsideBlock' . "\n", 3, 'var/log/insideblock.log');
            }
            break;
        }
    }

}