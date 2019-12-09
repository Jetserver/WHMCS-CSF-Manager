<?php
/*
 *
 * JetCSFManager @ whmcs module package
 * Created By Idan Ben-Ezra
 *
 * Copyrights @ Jetserver Web Hosting
 * http://jetserver.net
 *
 **/

if (!defined("JETCSFMANAGER"))
	die("This file cannot be accessed directly");

class jcsf_broadcast_apply extends jcsf_broadcast_default
{
	public function _default()
	{	
		global $instance, $cc_encryption_hash;
		
		$output = array('success' => true, 'message' => '', 'data' => array());
		
		$instance = csfmanager::getInstance();
		
		$output['data']['selectedservers'] = csfmanager::request_var('selectedservers', array());
		$output['data']['form_version'] = csfmanager::request_var('formversion');
		
		if(sizeof($output['data']['selectedservers']))
		{
			$output['success'] = false;
			$output['message'] = $instance->lang('noserversselected');
			return $output;
		}
		
		$output['data']['servers'] = array();
		
		$sql = "SELECT *
			FROM tblservers
			" . (trim($instance->getConfig('servers', '')) ? "WHERE id IN (" . trim($instance->getConfig('servers', '')) . ")" : '');
		$result = mysql_query($sql);
		
		while($server_details = mysql_fetch_assoc($result))
		{
			$output['data']['servers'][$server_details['id']] = array_merge($server_details, array('password' => decrypt($server_details['password'], $cc_encryption_hash)));
		}
		//mysql_free_result($result);

		$config_vars = csfmanager::request_var('configVars', array());

		$output['data']['vars'] = array();
		
		foreach($config_vars as $key => $value)
		{
			if(trim($value) != '**USE-CURRENT**') $output['data']['vars'][$key] = $value;
		}

		$templateserver = csfmanager::request_var('templateserver', 0);
		
		if(!isset($output['data']['servers'][$templateserver]))
		{
			$output['success'] = false;
			$output['message'] = $instance->lang('notemplateserverselected');
			return $output;
		}
		
		$output['data']['server_details'] = $output['data']['servers'][$templateserver];
		
		return $output;
	}
}

?>