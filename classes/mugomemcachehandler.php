<?php

class MugoMemCacheHandler implements ezpStaticCache
{
    public function generateNodeListCache( $nodeList )
	{
        if( !empty( $nodeList ) )
        {
            // clear by subtree
            $nodePath = $this->getNodePath( $nodeList );
            $this->purgeSubTree( $nodePath );
            
            // clear node ids (old content cache)
            $this->purgeNodes( $nodeList );
        }
    }

    // assuming the node path comes first in the nodeList
    private function getNodePath( $nodeList )
    {
        $return = array();
        
        foreach( $nodeList as $node )
        {
            $return[] = $node;

            if( $node == 1 )
            {
                break;
            }
        }
        
        return $return;
    }
    
    private function purgeNodes( $nodeList )
    {
        $db = ezDB::instance();
        $sql = 'SELECT `key` FROM mugo_memcache WHERE `node_id` IN ('. implode(',', $nodeList ) .')';
        
        $result = $db->arrayQuery( $sql );

        if( !empty( $result ) )
        {
            $mugoMemCacheBlock = MugoMemCacheBlock::instance();
            foreach( $result as $entry )
            {
                $mugoMemCacheBlock->purge( $entry[ 'key' ] );
            }
        }
    }

    private function purgeSubTree( $nodePath )
    {
        $db = ezDB::instance();
        $sql = 'SELECT `key` FROM mugo_memcache WHERE `subtree_id` IN ('. implode(',', $nodePath ) .')';
        
        $result = $db->arrayQuery( $sql );

        if( !empty( $result ) )
        {
            $mugoMemCacheBlock = MugoMemCacheBlock::instance();
            foreach( $result as $entry )
            {
                $mugoMemCacheBlock->purge( $entry[ 'key' ] );
            }
        }
    }
    
    public function generateAlwaysUpdatedCache( $quiet = false, $cli = false, $delay = true )
	{}
    
    public function generateCache( $force = false, $quiet = false, $cli = false, $delay = true )
    {}
    
    public function cacheURL( $url, $nodeID = false, $skipExisting = false, $delay = true )
	{}

	public function removeURL( $url )
	{}
    
    static function executeActions()
	{}
}
