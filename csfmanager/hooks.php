<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

add_hook("DailyCronJob", 1, function() {

	$sql = "DELETE
		FROM mod_csfmanager_allow
		WHERE expiration <= '" . time() . "'";
	mysql_query($sql);

});

add_hook('ClientAreaPage', 1, function($vars) {

	if($vars['action'] == 'productdetails' && $vars['serviceid'] && $vars['rawstatus'] == 'active')
	{
		require_once(dirname(__FILE__) . '/includes/functions.php');

		$instance = csfmanager::getInstance();
		
		$allowed_servers = explode(',', $instance->getConfig('servers'));

		if(in_array($vars['serverdata']['id'], $allowed_servers))
		{
			$menu = Menu::PrimarySidebar();

			$overviewMenu = $menu->getChild('Service Details Actions');

			$overviewMenu->addChild('Firewall', array(
				'label' 	=> 'Manage Firewall',
				'uri' 		=> 'index.php?m=csfmanager&id=' . $vars['serviceid'],
			));
		}
	}
});

?>