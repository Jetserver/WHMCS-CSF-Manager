<?php
/*
 *
 * JetBackupManager @ whmcs module package
 * Created By Idan Ben-Ezra
 *
 * Copyrights @ Jetserver Web Hosting
 * http://jetserver.net
 *
 **/

if (!defined("JETCSFMANAGER"))
	die("This file cannot be accessed directly");

class jcsf_firewall_manage extends jcsf_firewall_default
{
	public function _default()
	{	
		global $instance;
		
		$output = array('success' => true, 'message' => '', 'data' => array());
		
		$instance = csfmanager::getInstance();
		
		$server_id = csfmanager::request_var('server_id', 0);
		
		$servers = array();
		
		$sql = "SELECT *
			FROM tblservers
			" . (trim($instance->getConfig('servers', '')) ? "WHERE id IN (" . trim($instance->getConfig('servers', '')) . ")" : '');
		$result = mysql_query($sql);
		
		while($server_details = mysql_fetch_assoc($result))
		{
			$servers[$server_details['id']] = array_merge($server_details, array('password' => decrypt($server_details['password'], $cc_encryption_hash)));
		}
		mysql_free_result($result);
		
		$server_details = $servers[$server_id];
		
		if(!isset($server_details))
		{
			$output['success'] = false;
			$output['message'] = "The provided server not exists";
			return $output;
		}
				
		$password = $server_details['password'] ? $server_details['password'] : $server_details['accesshash'];
		$password_type = $server_details['password'] ? 'plain' : 'hash';
		
		$cpanel = new csfmanager_cpanel;
		
		$cpanel->setServer(($server_details['ipaddress'] ? $server_details['ipaddress'] : $server_details['hostname']), $server_details['username'], $password, $password_type);
		
		$response = $cpanel->whm('create_user_session', array(
			'user'		=> $server_details['username'],
			'service'	=> 'whostmgrd',
		));

		if(!$response['success'] || !$response['output']['url'])
		{
			$output['success'] = false;
			$output['message'] = "Unable to create cPanel session." . ($response['message'] ? " cPanel Error: {$response['success']}" : '');
			return $output;
		}

		$output['data']['iframe_url'] = $response['output']['url'];
		$output['data']['server_details'] = $server_details;
		
		return $output;
	}
}

?>