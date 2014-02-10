<?php

class MugoMemCacheBlock
{
    private static $instance;
    private $memcache;
            
    private function __construct()
    {
        $this->memcache = new Memcache;
        $this->memcache->connect( '127.0.0.1', 11211 );
    }

    public static function instance()
    {
        if( !isset( self::$instance ) )
        {
            $className = __CLASS__;
            self::$instance = new $className;
        }
        return self::$instance;
    }
    
    public function get( $cacheKeys, $lock = true, $tries = 0 )
    {
        $key = $this->cacheKeysToKey( $cacheKeys );
        
        if( !$lock )
        {
            return $this->memcache->get( $key );
        }
        else
        {
            $content = $this->memcache->get( $key );
            
            if( $content === false )
            {
                // empty cache
                $success = $this->memcache->add( $key, 'lock::'. microtime( true ) );
                
                if( !$success )
                {
                    //error_log( 'RaceCondition' . "\n", 3, 'var/log/insideblock.log');
                    // race-condition - another process was faster - let's wait
                    return $this->retryGet( $cacheKeys, $tries );
                }
                else
                {
                    //error_log( 'Setting lock' . "\n", 3, 'var/log/insideblock.log');
                    // Successfully set lock
                    // Let this request build the cache block
                    return false;
                }
            }
            else
            {
                // Check if we're still building the cache block
                if( substr( $content, 0, 6 ) == 'lock::' )
                {
                    //error_log( 'Has Lock' . "\n", 3, 'var/log/insideblock.log');
                    // Still building cache file - let's wait and retry
                    return $this->retryGet( $cacheKeys, $tries );
                }
                
                return $content;
            }
        }
    }
    
    private function retryGet( $cacheKeys, $tries )
    {
        $max_tries = 100;
        
        //usleep( 10000 * ( $tries % ( $max_tries / 10 ) ) ); // exponential backoff style of sleep
        sleep( 1 );
        
        if( $tries < $max_tries )
        {
            return $this->get( $cacheKeys, true, $tries++ );
        }
        else
        {
            //error_log( 'Complete Failure' . "\n", 3, 'var/log/insideblock.log');
            // that was not successful at all
            // not sure if we should return false or an empty string
            return '';
        }
    }
    
    public function put( $cacheKeys, $content, $ttl, $subTreeExpiry, $nodeExpiry )
    {
        $key = $this->cacheKeysToKey( $cacheKeys );
        $subTreeExpiry = (int) $subTreeExpiry ? (int) $subTreeExpiry : 0;
        $nodeExpiry    = (int) $nodeExpiry    ? (int) $nodeExpiry    : 0;
        
        $success = $this->memcache->set( $key, $content, 0, $ttl );
        
        // cache blocks with subTreeExpiry and nodeExpiry only expire by the ttl - there is no purge
        // therefore we don't need to add it to the DB
        if( $success && ( $subTreeExpiry || $nodeExpiry ) )
        {
            $expiry = time() + (int)$ttl;
            
            $db = ezDb::instance();
            
            $sql  = 'INSERT INTO mugo_memcache ( `key`, `expiry`, `subtree_id`, `node_id` ) ';
            $sql .= 'VALUES ( "'. $key .'",'. $expiry. ','. $subTreeExpiry .','. $nodeExpiry .') ';
            $sql .= 'ON DUPLICATE KEY UPDATE `expiry`='. $expiry .', `subtree_id`=' . $subTreeExpiry .', `node_id`='. $nodeExpiry;
            
            //echo $sql;
            
            $success = $db->query( $sql );
        }
        
        return $success;
    }
    
    public function purge( $key )
    {
        $this->memcache->delete( $key );
        
        $db = ezDb::instance();
        $sql = 'UPDATE mugo_memcache SET expiry=-1 WHERE `key`="'. $key .'"';
        //echo $sql;
        return $db->query( $sql );
    }

    private function cacheKeysToKey( $keys )
    {
        return md5( serialize( $keys ) );
    }
    

    static public function contentViewRetrieve( $cachePath, $timestamp )
    {
        var_dump( $cachePath );
        var_dump( $timestamp );
        die('dddd');
    }

    static public function contentViewGenerate( $cachePath, $args )
    {
        $data = eZNodeviewfunctions::contentViewGenerate( false, $args );
        
        return $data;
    }
    /*!
     \static
     Calculates the key entry for the function placement array $functionPlacement and returns it.

     \note This function is placed in this class to reduce the need to load the class eZTemplateCacheFunction
           when the templates are compiled. This reduces memory usage.
     */
    static function placementString( $functionPlacement )
    {
        $placementString =  $functionPlacement[0][0] . "_";
        $placementString .= $functionPlacement[0][1] . "_";
        $placementString .= $functionPlacement[1][0] . "_";
        $placementString .= $functionPlacement[1][1] . "_";
        $placementString .= $functionPlacement[2];
        return $placementString;
    }
    
    public function __clone()
    {
        trigger_error('Clonen ist nicht erlaubt.', E_USER_ERROR);
    }

    public function __wakeup()
    {
        trigger_error('Deserialisierung ist nicht erlaubt.', E_USER_ERROR);
    }

}

?>
