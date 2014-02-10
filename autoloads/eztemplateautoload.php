<?php

$eZTemplateOperatorArray = array();

$eZTemplateOperatorArray[] = array( 'script' => 'extension/mugo_memcache/classes/mugo_memcachetemplateoperators.php',
                                    'class' => 'MugoMemCacheTemplateOperators',
                                    'operator_names' => array( 'insideBlock' ) );

$eZTemplateFunctionArray = array();
$eZTemplateFunctionArray[] = array( 'class' => 'MugoMemCacheTemplateFunction',
                                    'function_names' => array( 'mcache-block' ) );
