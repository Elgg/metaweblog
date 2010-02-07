<?php
    /**
	 * Elgg Metaweblog API
	 * 
	 * @package ElggMetaWeblog
	 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
	 * @author Misja Hoebe <misja@elgg.com>
	 * @copyright Curverider Ltd 2008-2009
	 * @link http://elgg.com
	 */

	/**
	 * MetaWeblog init
	 */
	function metaweblog_init()
	{
	    global $CONFIG;
	    
	    // check if the weblog plugin is present and enabled
	    if (!is_plugin_enabled('blog'))
	    {
	        // not available, disable
	        if (is_plugin_enabled('metaweblog'))
	        {
	            disable_plugin('metaweblog');
	            
	            register_error(elgg_echo('metaweblog:blog_missing'));
	        }
	    }

	    // metaweblog API
	    include $CONFIG->pluginspath . 'metaweblog/lib/metaweblog.php';

	    // Add an EditURI when in profile or blog
	    $context = get_context();
	    
	    if ($context == 'blog' || $context == 'profile')
	    {
		    extend_view('metatags', 'metaweblog/metatags');
	    }
	}

	function metaweblog_page_handler($page)
	{
	    global $CONFIG;

	    // Handle RSD request
	    if ($page[0] == 'user')
	    {
	        $user = get_user_by_username($page[1]);

	        echo elgg_view("metaweblog/rsd", 
	                       array('user_homepage' => get_entity_url($user->getGUID()), 
                  				 'service_url' => $CONFIG->url . 'xml-rpc.php', 
                  				 'blog_id' => $user->getGUID()));

            return true;
	    }
	}
	
	// Page handlers
    register_page_handler('rsd','metaweblog_page_handler');
	
    register_elgg_event_handler('init', 'system', 'metaweblog_init');	
?>