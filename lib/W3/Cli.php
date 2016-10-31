<?php

/**
 * The W3 Total Cache plugin
 *
 * @package wp-cli
 * @subpackage commands/third-party
 * @maintainer Anthony Somerset
 */
class W3TotalCache_Command extends WP_CLI_Command {

        /**
	 * Flushes the whole cache or specific items based on provided arguments
	 *
	 * ## OPTIONS
	 *
	 * [<post|database|minify|object>]
	 * : post     = Flush a specific post using --postid or --permalink
	 * database = Flush the database cache
	 * object   = Flush the object cache
	 * minify   = Flush the minify cache
	 *
	 * [--postid=<id>]
	 * : Flush a specific post ID
	 *
	 * [--permalink=<url>]
	 * : Flush a specific permalink
	 */
	function flush($args = array(), $vars = array()) {
		$args = array_unique($args);

		do {
			$cache_type = array_shift($args);

			switch($cache_type) {
			case 'db':
			case 'database':
				try {
 					$w3_db = w3_instance('W3_CacheFlush');
 					$w3_db->dbcache_flush();
				}
				catch (Exception $e) {
 				  WP_CLI::error(__('Flushing the DB cache failed.', 'w3-total-cache'));
				}
				WP_CLI::success(__('The DB cache has flushed successfully.', 'w3-total-cache'));
				break;

			case 'minify':
				try {
 					$w3_minify = w3_instance('W3_CacheFlush');
 					$w3_minify->minifycache_flush();
				}
				catch (Exception $e) {
 				  WP_CLI::error("Flushing the minify cache failed: $e");
				}
				WP_CLI::success(__('The minify cache has flushed successfully.', 'w3-total-cache'));
				break;

			case 'object':
				try {
 				  $w3_objectcache = w3_instance('W3_CacheFlush');
 					$w3_objectcache->objectcache_flush();
				}
				catch (Exception $e) {
 				  WP_CLI::error("Flushing the object cache failed: $e");
				}
				WP_CLI::success(__('The object cache has flushed successfully.', 'w3-total-cache'));
				break;

			case 'post':
			default:
				if (isset($vars['postid'])) {
					if (is_numeric($vars['postid'])) {
					  try {
                        			$w3_cacheflush = w3_instance('W3_CacheFlush');
                        			$w3_cacheflush->pgcache_flush_post($vars['postid']);
                        			$w3_cacheflush->varnish_flush_post($vars['postid']);
                      			  }
  				  	  catch (Exception $e) {
   					  	WP_CLI::error("Flushing the page from cache failed: $e");
   					  }
   					  WP_CLI::success(__('The page has been flushed from cache successfully.', 'w3-total-cache'));
					} else {
						WP_CLI::error(__('This is not a valid post id.', 'w3-total-cache'));
					}

					w3tc_pgcache_flush_post($vars['postid']);
				}
				elseif (isset($vars['permalink'])) {
					$id = url_to_postid($vars['permalink']);

					if (is_numeric($id)) {
						try {
  					      		$w3_cacheflush = w3_instance('W3_CacheFlush');
  					      		$w3_cacheflush->pgcache_flush_post($id);
                          		     		$w3_cacheflush->varnish_flush_post($id);
  				  		}
  				  		catch (Exception $e) {
   					 		 WP_CLI::error("Flushing the page from cache failed: $e");
   						}
   						WP_CLI::success(__('The page has been flushed from cache successfully.', 'w3-total-cache'));
					} else {
						WP_CLI::error(__('There is no post with that permalink.', 'w3-total-cache'));
					}
				} else {
					if (isset($flushed_page_cache) && $flushed_page_cache)
						break;

					$flushed_page_cache = true;
					try {
					      $w3_cacheflush = w3_instance('W3_CacheFlush');
					      $w3_cacheflush->pgcache_flush();
					      $w3_cacheflush->varnish_flush();
 					}
 					catch (Exception $e) {
   						WP_CLI::error("Flushing the page cache failed: $e");
   			  		}
 				  	WP_CLI::success(__('The page cache has been flushed successfully.', 'w3-total-cache'));
				}
			}
		} while (!empty($args));
	} 

        /**
	 * Prime the page cache (cache preloader)
	 *
	 * ## OPTIONS
	 *
	 * [<stop>]
	 * : Stop the active page cache priming session.
         *
	 * [--interval=<seconds>]
	 * : Number of seconds to wait before creating another batch. If not set, the 
	 * value given in W3TC's Page Cache > Update Interval field is used.
	 *
	 * [--batch=<size>]
	 * : Max number of pages to create per batch. If not set, the value given in
	 * W3TC's Page Cache > Pages per Interval field is used.
	 *
	 * [--sitemap=<url>]
	 * : The sitemap url specifying the pages to prime cache. If not set, the value
	 * given in W3TC's Page Cache > Sitemap URL field is used.
	 */
	function prime($args = array(), $vars = array())
	{
		try
		{
			$action = array_shift($args);

			if ($action == 'stop') {
				if (clear_hook_crons('w3_pgcache_prime') === false) {
  					WP_CLI::error('No page cache priming session to stop.');
  				}
  				else {
					WP_CLI::success('Page cache priming has been stopped.');
				}
			}
			else
			{
				$user_interval = -1;
				$user_limit = -1;
				$user_sitemap = "";
				
				if (isset($vars['interval']) && is_numeric($vars['interval'])) {
					$user_interval = intval($vars['interval']);
				}
				if (isset($vars['batch']) && is_numeric($vars['batch'])) {
					$user_limit = intval($vars['batch']);
				}
				if (isset($vars['sitemap']) && !empty($vars['sitemap'])) {
					$user_sitemap = trim($vars['sitemap']);
				}
			
  				$w3_prime = w3_instance('W3_Plugin_PgCacheAdmin');
  				if (($res=$w3_prime->prime(0,$user_interval,$user_limit,$user_sitemap))===false) {
  					WP_CLI::error('Page cache currently being primed.');
  				}
  				else if (empty($res)) {
  					WP_CLI::error('No sitemap found in W3TC prefs nor provided. A sitemap is needed to prime the page cache.');
  				}
  				
  				WP_CLI::success("Page cache priming has successfully started $res.");
  			}
  		}
		catch (Exception $e)
		{
  			WP_CLI::error("$e");
		}
	}

	/**
	 * Update query string function
	 */
	function querystring() {

		try {
  		$w3_querystring = w3_instance('W3_CacheFlush');
  		$w3_querystring->browsercache_flush();
  	}
		catch (Exception $e) {
  		WP_CLI::error(__('updating the query string failed. with error %s', 'w3-total-cache'), $e);
		}

		WP_CLI::success(__('The query string was updated successfully.', 'w3-total-cache'));

}

	/**
	 * Purge URL's from cdn and varnish if enabled
	 * @param array $args
	 */
	function cdn_purge($args = array()) {
	    $purgeitems = array();
        foreach ($args as $file) {
            $cdncommon = w3_instance('W3_Plugin_CdnCommon');
            $local_path = WP_ROOT . $file;
            $remote_path = $file;
            $purgeitems[] = $cdncommon->build_file_descriptor($local_path, $remote_path);
        }

   		try {
			  $w3_cdn_purge = w3_instance('W3_CacheFlush');
			  $w3_cdn_purge->cdn_purge_files($purgeitems);
		}
		catch (Exception $e) {
			WP_CLI::error(__('Files did not successfully purge with error %s', 'w3-total-cache'), $e);
		}
		WP_CLI::success(__('Files purged successfully.', 'w3-total-cache'));
		
	}

    /**
     * Tell APC to reload PHP files
     * @param array $args
     */
    function apc_reload_files($args = array()) {
        try {
            $method = array_shift($args);
            if (!in_array($method, array('SNS', 'local')))
                WP_CLI::error($method . __(' is not supported. Change to SNS or local to reload APC files', 'w3-total-cache'));
            if ($method == 'SNS') {
                $w3_cache = w3_instance('W3_CacheFlush');
                $w3_cache->apc_reload_files($args);
            } else {
                $url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/apc.php';
                $path = parse_url($url, PHP_URL_PATH);
                $post = array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'body' => array( 'nonce' => wp_hash($path), 'command' => 'reload_files', 'files' => $args),
                );
                $result = wp_remote_post($url, $post);
                if (is_wp_error($result)) {
                    WP_CLI::error(__('Files did not successfully reload with error %s', 'w3-total-cache'), $result);
                } elseif ($result['response']['code'] != '200') {
                    WP_CLI::error(__('Files did not successfully reload with message: ', 'w3-total-cache') . $result['body']);
                }
            }
        }
        catch (Exception $e) {
            WP_CLI::error(__('Files did not successfully reload with error %s', 'w3-total-cache'), $e);
        }
        WP_CLI::success(__('Files reloaded successfully.', 'w3-total-cache'));

    }
    
    
    /**
     * Tell opcache to reload PHP files
     * @param array $args
     */
    function opcache_reload_files($args = array()) {
    	try {
    		$method = array_shift($args);
    		if (!in_array($method, array('SNS', 'local')))
    			WP_CLI::error($method . __(' is not supported. Change to SNS or local to reload opcache files', 'w3-total-cache'));
    			if ($method == 'SNS') {
    				$w3_cache = w3_instance('W3_CacheFlush');
    				$w3_cache->opcache_reload_files($args);
    			} else {
    				$url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/opcache.php';
    				$path = parse_url($url, PHP_URL_PATH);
    				$post = array(
    						'method' => 'POST',
    						'timeout' => 45,
    						'redirection' => 5,
    						'httpversion' => '1.0',
    						'blocking' => true,
    						'body' => array( 'nonce' => wp_hash($path), 'command' => 'reload_files', 'files' => $args),
    				);
    				$result = wp_remote_post($url, $post);
    				if (is_wp_error($result)) {
    					WP_CLI::error(__('Files did not successfully reload with error %s', 'w3-total-cache'), $result);
    				} elseif ($result['response']['code'] != '200') {
    					WP_CLI::error(__('Files did not successfully reload with message: ', 'w3-total-cache') . $result['body']);
    				}
    			}
    	}
    	catch (Exception $e) {
    		WP_CLI::error(__('Files did not successfully reload with error %s', 'w3-total-cache'), $e);
    	}
    	WP_CLI::success(__('Files reloaded successfully.', 'w3-total-cache'));
    
    }

    /**
     * Tell APC to reload PHP files
     * @param array $args
     */
    function apc_delete_based_on_regex($args = array()) {
        try {
            $method = array_shift($args);
            if (!in_array($method, array('SNS', 'local')))
                WP_CLI::error($method . __(' is not supported. Change to SNS or local to delete APC files', 'w3-total-cache'));

            if ($method == 'SNS') {
                $w3_cache = w3_instance('W3_CacheFlush');
                $w3_cache->apc_delete_files_based_on_regex($args[0]);
            } else {
                $url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/apc.php';
                $path = parse_url($url, PHP_URL_PATH);
                $post = array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'body' => array( 'nonce' => wp_hash($path), 'command' => 'delete_files', 'regex' => $args[0]),
                );
                $result = wp_remote_post($url, $post);
                if (is_wp_error($result)) {
                    WP_CLI::error(__('Files did not successfully delete with error %s', 'w3-total-cache'), $result);
                } elseif ($result['response']['code'] != '200') {
                    WP_CLI::error(__('Files did not successfully delete with message: ', 'w3-total-cache'). $result['body']);
                }
            }
        }
        catch (Exception $e) {
            WP_CLI::error(__('Files did not successfully delete with error %s', 'w3-total-cache'), $e);
        }
        WP_CLI::success(__('Files deleted successfully.', 'w3-total-cache'));

    }
    
    
    /**
     * Tell opcache to reload PHP files
     * @param array $args
     */
    function opcache_delete_based_on_regex($args = array()) {
    	try {
    		$method = array_shift($args);
    		if (!in_array($method, array('SNS', 'local')))
    			WP_CLI::error($method . __(' is not supported. Change to SNS or local to delete opcache files', 'w3-total-cache'));
    
    			if ($method == 'SNS') {
    				$w3_cache = w3_instance('W3_CacheFlush');
    				$w3_cache->apc_delete_files_based_on_regex($args[0]);
    			} else {
    				$url = WP_PLUGIN_URL . '/' . dirname(W3TC_FILE) . '/pub/opcache.php';
    				$path = parse_url($url, PHP_URL_PATH);
    				$post = array(
    						'method' => 'POST',
    						'timeout' => 45,
    						'redirection' => 5,
    						'httpversion' => '1.0',
    						'blocking' => true,
    						'body' => array( 'nonce' => wp_hash($path), 'command' => 'delete_files', 'regex' => $args[0]),
    				);
    				$result = wp_remote_post($url, $post);
    				if (is_wp_error($result)) {
    					WP_CLI::error(__('Files did not successfully delete with error %s', 'w3-total-cache'), $result);
    				} elseif ($result['response']['code'] != '200') {
    					WP_CLI::error(__('Files did not successfully delete with message: ', 'w3-total-cache'). $result['body']);
    				}
    			}
    	}
    	catch (Exception $e) {
    		WP_CLI::error(__('Files did not successfully delete with error %s', 'w3-total-cache'), $e);
    	}
    	WP_CLI::success(__('Files deleted successfully.', 'w3-total-cache'));
    
    }
	
	/**
	 * triggers PgCache Garbage Cleanup 
	 */
	function pgcache_cleanup() {
    try {
        $pgcache_cleanup = w3_instance('W3_Plugin_PgCacheAdmin');
        $pgcache_cleanup->cleanup();
		}
		catch (Exception $e) {
			WP_CLI::error(__('PageCache Garbage cleanup did not start with error %s', 'w3-total-cache'), $e);
		}
		WP_CLI::success(__('PageCache Garbage cleanup triggered successfully.', 'w3-total-cache'));
		
	}

	/**
	 * Help function for this command
	 */
	public function help() {
		WP_CLI::line( <<<EOB

usage: wp w3-total-cache flush [post|database|minify|object] [--postid=<id>] [--permalink=<url>] 
       wp w3-total-cache querystring
       wp w3-total-cache cdn_purge <file> [<file>...]
       wp w3-total-cache pgcache_cleanup
       wp w3-total-cache prime [stop] [--interval=<seconds>] [--batch=<size>] [--sitemap=<url>]
       wp w3-total-cache apc_reload_files (SNS|local) <file.php> [<file.php>...]
       wp w3-total-cache apc_delete_based_on_regex (SNS|local) <expression>
       wp w3-total-cache opcache_reload_files
       wp w3-total-cache opcache_delete_based_on_regex (SNS|local) <expression>
  
Sub-Commands:

  flush                         Flushes the whole cache or specific items based on provided arguments
  
                                post       Flush a post via ... 
                                           --postid=<id>             Flush a specific post ID
                                           --permalink=<url>         Flush a specific permalink
                                           
                                database   Flush the database cache
                                object     Flush the object cache
                                minify     Flush the minify cache
                                
  querystring                   Update query string for all static files
  cdn_purge                     Purges command line provided files from Varnish and the CDN
  pgcache_cleanup               Generally triggered from a cronjob, allows for manual Garbage collection of page cache to be triggered
  
  prime                         Prime the page cache

                                stop                   Stop an active priming session
                                --interval=<seconds>   Number of seconds to wait before creating another batch
                                                       (this defaults to w3tc prefs when missing)
                                --batch=<size>         Max number of pages to create per batch
                                                       (this defaults to w3tc prefs when missing)
                                --sitemap=<url>        The sitemap url specifying the pages to prime cache
                                                       (this defaults to w3tc prefs when missing)                                

  apc_reload_files              Tells apc to compile files
  apc_delete_based_on_regex     Tells apc to delete files that match a RegEx mask
  opcache_reload_files          Tells opcache to compile files
  opcache_delete_based_on_regex Tells opcache to delete files that match a RegEx mask
  
EOB
		);
	}
}

function clear_hook_crons($hook) 
{
    $res = false;    
    $crons = _get_cron_array();
    if ( empty( $crons ) ) {
        return false;
    }
    foreach( $crons as $timestamp => $cron ) {
        if ( ! empty( $cron[$hook] ) )  {
            unset( $crons[$timestamp][$hook] );
            $res = true;
        }

        if ( empty( $crons[$timestamp] ) ) {
            unset( $crons[$timestamp] );
        }
    }
    _set_cron_array( $crons );
    return $res;
}

if (method_exists('WP_CLI','add_command')) {
  WP_CLI::add_command('w3-total-cache', 'W3TotalCache_Command');
  WP_CLI::add_command('total-cache', 'W3TotalCache_Command');
} else {
  // backward compatibility
  WP_CLI::addCommand('w3-total-cache', 'W3TotalCache_Command');
  WP_CLI::addCommand('total-cache', 'W3TotalCache_Command');
}

