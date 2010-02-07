<?php

    /**
	 * Elgg Metaweblog API methods
	 * 
	 * @package ElggMetaWeblog
	 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
	 * @author Misja Hoebe <misja@elgg.com>
	 * @copyright Curverider Ltd 2008-2009
	 * @link http://elgg.com
	 * @see http://www.xmlrpc.com/metaWeblogApi
	 */

    /**
     * Get a list of user's blogs
     * 
     * Currently returns the user's blog
     *
     * @param XMLRPCCall $data
     * @return array
     */
    function blogger_getUsersBlogs(XMLRPCCall $data)
    {
        global $CONFIG;
        
        $parameters  = xmlrpc_parse_params($data->getParameters());
        
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);

        if (!pam_authenticate($credentials))
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }

        $blog             = array();
        $blog['url']      = new XMLRPCStringParameter($CONFIG->url."pg/blog/".$parameters[1]);
        $blog['blogid']   = new XMLRPCStringParameter($user->getGUID());
        $blog['blogName'] = new XMLRPCStringParameter($user->name."'s blog");

        $blogs = new XMLRPCArrayParameter(array(new XMLRPCStructParameter($blog)));
        
        $response = new XMLRPCSuccessResponse();
        
        $response->addParameter($blogs);
        
        return $response;
    }
    
    register_xmlrpc_handler('blogger.getUsersBlogs', 'blogger_getUsersBlogs');
    
    /**
     * Delete a blog post
     *
     * @param XMLRPCCall $data
     * @return boolean
     */
    function blogger_deletePost(XMLRPCCall $data)
    {
        $parameters  = xmlrpc_parse_params($data->getParameters());

        $post        = get_entity($parameters[1]);
        $user        = get_user_by_username($parameters[2]);
        $credentials = array('username' => $parameters[2],
                             'password' => $parameters[3]);

        if ($post->getSubType() != 'blog')
        {
            return new XMLRPCErrorResponse('Incorrect entity type', -32502);
        }
        
        if (!pam_authenticate($credentials) || $user->getGUID() != $post->getOwner())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }
        
        // We need a session to be able to save an existing entity, contrary to creating one
        $_SESSION['user'] = $user;
        
        $result = $post->delete();
        
        if ($result > 0)
        {
            $response = new XMLRPCSuccessResponse();
            
            $response->addBoolean(true);
            
            return $response;
        }
        else
        {
            return new XMLRPCErrorResponse('Unable to delete post', -32506);
        }
    }
    
    register_xmlrpc_handler('blogger.deletePost', 'blogger_deletePost');
    
    /**
     * Create a new post
     *
     * @param XMLRPCCall $data
     * @return string The post ID
     */
    function metaWeblog_newPost(XMLRPCCall $data)
    {
        $parameters  = xmlrpc_parse_params($data->getParameters());
        
        $user_guid   = $parameters[0];
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);
        $title       = $parameters[3]['title'];
        $content     = $parameters[3]['description'];
        
        (isset($parameters[3]['mt_keywords'])) ? $tags = array_map('trim', explode(',', $parameters[3]['mt_keywords'])) : $tags = array();

        $parameters[4] ? $access = ACCESS_PUBLIC : $access = ACCESS_PRIVATE;
        
        if (!pam_authenticate($credentials) || $user_guid != $user->getGUID())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }
        
        $_SESSION['user'] = $user;
        
        $post              = new ElggObject();
        $post->subtype     = "blog";
        $post->owner_guid  = $user->getGUID();
        $post->access_id   = $access;
        $post->title       = $title;
        $post->description = $content;

        if (!$post->save())
        {
            return new XMLRPCErrorResponse('Unable to save post', -32501);
        }
        else
        {
            $post->tags = $tags;
            
            $response = new XMLRPCSuccessResponse();
            
            $response->addString($post->getGUID());
            
            return $response;
        }
    }
    
    register_xmlrpc_handler('metaWeblog.newPost', 'metaWeblog_newPost');
    
    /**
     * Edit a post
     *
     * @param XMLRPCCall $data
     * @return boolean
     */
    function metaWeblog_editPost(XMLRPCCall $data)
    {
        $parameters  = xmlrpc_parse_params($data->getParameters());
        
        $post        = get_entity($parameters[0]);
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);
        $title       = $parameters[3]['title'];
        $content     = $parameters[3]['description'];
        
        (isset($parameters[3]['mt_keywords'])) ? $tags = array_map('trim', explode(',', $parameters[3]['mt_keywords'])) : $tags = array();
        
        $parameters[4] ? $access = ACCESS_PUBLIC : $access = ACCESS_PRIVATE;

        if ($post->getSubType() != 'blog')
        {
            return new XMLRPCErrorResponse('Incorrect entity type', -32502);
        }
        
        if (!pam_authenticate($credentials) || $user->getGUID() != $post->getOwner())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }

        // We need a session to be able to save an existing entity, contrary to creating one
        $_SESSION['user'] = $user;

        $post->access_id   = $access;
        $post->title       = $title;
        $post->description = $content;
        
        if (!$post->save())
        {
            return new XMLRPCErrorResponse('Unable to save post', -32501);
        }
        else
        {
            $post->clearMetadata('tags');
            
            $post->tags = $tags;
            
            $response = new XMLRPCSuccessResponse();
            
            $response->addBoolean(true);
            
            return $response;
        }
    }

    register_xmlrpc_handler('metaWeblog.editPost', 'metaWeblog_editPost');
        
    /**
     * Get a single post
     *
     * @param XMLRPCCall $data
     * @return array
     */
    function metaWeblog_getPost(XMLRPCCall $data)
    {
        $parameters  = xmlrpc_parse_params($data->getParameters());

        $post        = get_entity($parameters[0]);
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);

        if ($post->getSubType() != 'blog')
        {
            return new XMLRPCErrorResponse('Incorrect entity type', -32502);
        }

        if (!pam_authenticate($credentials) || $user->getGUID() != $post->getOwner())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }
                
        $response = new XMLRPCSuccessResponse();

        $response->addParameter(metaweblog_post_struct($post));

        return $response;
    }
    
    register_xmlrpc_handler('metaWeblog.getPost', 'metaWeblog_getPost');
    
    /**
     * Add a media object
     *
     * @param XMLRPCCall $data
     * @return array
     * @todo Further Integration with the file plugin, thumbnail actions, etc.
     */
    function metaWeblog_newMediaObject(XMLRPCCall $data)
    {
        if (!is_plugin_enabled('file'))
        {
            return new XMLRPCErrorResponse('File plugin is missing or not enabled', -32505);
        }
        
        $parameters  = xmlrpc_parse_params($data->getParameters());

        $user_guid   = $parameters[0];
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);
        $name        = $parameters[3]['name'];
        $type        = $parameters[3]['type'];
        $bits        = $parameters[3]['bits'];

        if (!pam_authenticate($credentials) || $user_guid != $user->getGUID())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }
        
        // File store reads from the session
        $_SESSION['user'] = $user;
        
    	$prefix = "file/";
    	
    	$file = new FilePluginFile();
    	$filestorename = strtolower(time().$name);
    	$file->setFilename($prefix.$filestorename);
    	$file->setMimeType($type);
    	
    	$file->originalfilename = $name;    	
    	$file->subtype = "file";
    	$file->access_id = ACCESS_PUBLIC;
    	
    	$file->open("write");
    	$file->write($bits);
    	$file->close();
    	
    	$file->title = $name;
    	$file->description = 'Uploaded with the Elgg Metaweblog plugin';
    	
    	$file->simpletype = get_general_file_type($type);
    
    	if ($result = $file->save())
    	{
    	    $response = new XMLRPCSuccessResponse();
    	    
    	    // return a struct containing at least key 'url'
    	    $params = array();
    	    $params['url'] = get_entity_url($file);
    	    
    	    $response->addParameter(new XMLRPCStructParameter($params));
    	    
    	    return $response;
    	}
    	else
    	{
    	    return new XMLRPCErrorResponse('File save failed', -32508);
    	}
    }

    register_xmlrpc_handler('metaWeblog.newMediaObject', 'metaWeblog_newMediaObject');

    /**
     * Get blog categories
     *
     * @param XMLRPCCall $data
     * @return array
     * @todo Implement this feature
     */
    function metaWeblog_getCategories(XMLRPCCall $data)
    {
        // From the API spec:
        // The struct returned contains one struct for each category, containing the following elements: 
        // description, htmlUrl and rssUrl.
        // This entry-point allows editing tools to offer category-routing as a feature.
        
        $parameters  = xmlrpc_parse_params($data->getParameters());
        
        $user_guid   = $parameters[0];
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);
        
        if (!pam_authenticate($credentials) || $user_guid != $user->getGUID())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }

        return new XMLRPCErrorResponse('Not implemented', -32505);
    }
    
    register_xmlrpc_handler('metaWeblog.getCategories', 'metaWeblog_getCategories');

    /**
     * Get recent posts
     *
     * @param XMLRPCCall $data
     * @return array An array of structs
     */
    function metaWeblog_getRecentPosts(XMLRPCCall $data)
    {
        $parameters  = xmlrpc_parse_params($data->getParameters());

        $user_guid   = $parameters[0];
        $user        = get_user_by_username($parameters[1]);
        $credentials = array('username' => $parameters[1],
                             'password' => $parameters[2]);
        $number      = $parameters[3];

        (!empty($number)) ? $number : $number = 1;
        
        if (!pam_authenticate($credentials) || $user_guid != $user->getGUID())
        {
            return new XMLRPCErrorResponse('Access denied', -32500);
        }
        
        $posts = get_user_objects($user_guid, 'blog', $number);
        
        $elements = array();
        
        foreach ($posts as $post)
        {
            $elements[] = metaweblog_post_struct($post);
        }
        
        $response = new XMLRPCSuccessResponse();
        
        $response->addParameter(new XMLRPCArrayParameter($elements));
        
        return $response;
    }
    
    register_xmlrpc_handler('metaWeblog.getRecentPosts', 'metaWeblog_getRecentPosts');
    
    /**
     * Create a struct suitable for passing to an XMLRPCResponse
     *
     * @param ElggObject $post
     * @return XMLRPCStructParameter
     */
    function metaweblog_post_struct($post)
    {
        $user = get_user($post->getOwner());
        
        $params                = array();
        $params['dateCreated'] = new XMLRPCDateParameter($post->getTimeCreated());
        $params['userid']      = new XMLRPCStringParameter($user->username);
        $params['postid']      = new XMLRPCIntParameter($post->getGUID());
        $params['title']       = new XMLRPCStringParameter($post->title);
        $params['description'] = new XMLRPCStringParameter($post->description);
        $params['url']         = new XMLRPCStringParameter(get_entity_url($post->getGUID()));
        $params['permalink']   = new XMLRPCStringParameter(get_entity_url($post->getGUID()));
        $params['mt_keywords'] = new XMLRPCStringParameter(implode(',', $post->tags));

        return new XMLRPCStructParameter($params);
    }
?>