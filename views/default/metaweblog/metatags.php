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
<link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo $vars['url'];?>pg/rsd/user/<?php echo $owner->username;?>" />
