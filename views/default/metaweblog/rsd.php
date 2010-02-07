<?
    /**
     * Elgg Metaweblog API
     * 
     * @package ElggMetaWeblog
     * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
     * @author Misja Hoebe <misja@elgg.com>
     * @copyright Curverider Ltd 2008-2009
     * @link http://elgg.com
     */
    $owner = page_owner_entity();
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>';?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd" >
    <service>
        <engineName>Elgg Social Application Framework</engineName>
        <engineLink>http://elgg.org</engineLink>
        <homePageLink><?php echo $vars['user_homepage'];?></homePageLink>
        <apis>
            <api name="MetaWeblog"
                    preferred="true"
                    apiLink="<?php echo $vars['service_url'];?>"
                    blogID="<?php echo $vars['blog_id'];?>" />
        </apis>
    </service>
</rsd>