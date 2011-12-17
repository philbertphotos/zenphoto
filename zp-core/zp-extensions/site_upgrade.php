<?php
/**
 * Switches site into "update" mode for Zenphoto upgrades.
 *
 * Requires mod_rewrite to be active and that the .htaccess file exists
 *
 * Change the files in plugins/site_upgrade to meet your needs. (Note these files will
 * be copied to that folder the first time the plugin runs.)
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 */

if (defined('OFFSET_PATH')) {

	$plugin_is_filter = 5|ADMIN_PLUGIN;
	$plugin_description = gettext('Utility to divert access to the gallery to an screen saying the site is upgrading.');
	$plugin_author = "Stephen Billard (sbillard)";
	$plugin_version = '1.4.3';
	$plugin_disable = (MOD_REWRITE) ? false : gettext('The <em>mod_rewrite</em> must be enabled');

	zp_register_filter('admin_utilities_buttons', 'site_upgrade_button');

	if (!file_exists(SERVERPATH.'/'.USER_PLUGIN_FOLDER.'/site_upgrade/close.html')) {
		mkdir_recursive(SERVERPATH.'/'.USER_PLUGIN_FOLDER.'/site_upgrade/', FOLDER_MOD);
		copy(SERVERPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/site_upgrade/closed.html', SERVERPATH.'/'.USER_PLUGIN_FOLDER.'/site_upgrade/closed.html');
		copy(SERVERPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/site_upgrade/closed.png', SERVERPATH.'/'.USER_PLUGIN_FOLDER.'/site_upgrade/closed.png');
	}

	function site_upgrade_button($buttons) {
		$enable = 1;
		$hidden = '';
		$ht = @file_get_contents(SERVERPATH.'/.htaccess');
		if (empty($ht)) {
			$title = gettext('There is no .htaccess file');
			$enable = 0;
			$button_text = gettext('Close the site.');
			$image = 'images/action.png';
		} else {
			preg_match('|[#\s]\sRewriteRule(.*)plugins/site_upgrade/closed|',$ht,$matches);
			if (strpos($matches[0],'#')===0) {
				$button_text = gettext('Close site');
				$title = gettext('Make site unavialable for viewing, redirect to closed sign.');
				$image = 'images/lock.png';
			} else {
				$button_text = gettext('Open the site');
				$title = gettext('Mark site available for viewing.');
				$image = 'images/lock_open.png';
			}
		}
		$buttons[] = array(
											'XSRFTag'=>'site_upgrade',
											'category'=>gettext('admin'),
											'enable'=>$enable,
											'button_text'=>$button_text,
											'formname'=>'site_upgrade.php',
											'action'=>WEBPATH.'/'.ZENFOLDER.'/'.PLUGIN_FOLDER.'/site_upgrade.php',
											'icon'=>$image,
											'title'=>$title,
											'alt'=>$title,
											'hidden'=>$hidden,
											'rights'=> ADMIN_RIGHTS
		);
		return $buttons;
	}

} else {

	define('OFFSET_PATH', 3);
	require_once(dirname(dirname(__FILE__)).'/admin-globals.php');

	admin_securityChecks(ALBUM_RIGHTS, currentRelativeURL(__FILE__));

	$htpath = SERVERPATH.'/.htaccess';
	$ht = file_get_contents($htpath);

	preg_match_all('|[#\s]\sRewriteRule(.*)plugins/site_upgrade/closed|',$ht,$matches);
	if (strpos($matches[0][1],'#')===0) {
		$ht = str_replace($matches[0][0], substr($matches[0][0],1), $ht);
		$ht = str_replace($matches[0][1], substr($matches[0][1],1),$ht);
		@chmod($htpath, 0777);
		file_put_contents($htpath, $ht);
		@chmod($htpath,0444);
		$report = gettext('Site is now marked in upgrade.');
	} else {
		$ht = str_replace($matches[0][0], str_replace("\n","\n#",$matches[0][0]), $ht);
		$ht = str_replace($matches[0][1], str_replace("\n","\n#",$matches[0][1]), $ht);
		@chmod($htpath, 0777);
		file_put_contents($htpath, $ht);
		@chmod($htpath, 0444);
		$report = gettext('Site is viewable.');
	}
	header('Location: ' . FULLWEBPATH . '/' . ZENFOLDER . '/admin.php?report='.$report);
}

?>