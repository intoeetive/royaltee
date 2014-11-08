<?php

if ( ! defined('ROYALTEE_ADDON_NAME'))
{
	define('ROYALTEE_ADDON_NAME',         'RoyaltEE');
	define('ROYALTEE_ADDON_VERSION',      '0.1');
}

$config['name']=ROYALTEE_ADDON_NAME;
$config['version']=ROYALTEE_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/316';