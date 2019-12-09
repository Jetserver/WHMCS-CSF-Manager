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

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set("default_charset", "UTF-8");

if (!defined("JETCSFMANAGER"))
	die("This file cannot be accessed directly");

class jcsf_broadcast_setconfig extends jcsf_broadcast_default
{
	public function _default()
	{	
		global $cc_encryption_hash, $instance;
		
		$output = array('success' => true, 'message' => '', 'data' => array());

		$instance = csfmanager::getInstance();
		
		$output['data']['selectedservers'] = csfmanager::request_var('selectedservers', array());
		
		if(!sizeof($output['data']['selectedservers']))
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

		$templateserver = csfmanager::request_var('templateserver', 0);

		if(!isset($output['data']['servers'][$templateserver]))
		{
			$output['success'] = false;
			$output['message'] = $instance->lang('notemplateserverselected');
			return $output;
		}
		
		$output['data']['server_details'] = $output['data']['servers'][$templateserver];
		$output['data']['dontusevalues'] = csfmanager::request_var('dontusevalues', 0, array(0,1));
		
		$response = csfmanager::checkCsfAlive($output['data']['server_details']);

		if(!$response['success'] || !$response['version'])
		{
			$output['success'] = false;
			if(!$response['success']) $output['message'] = $response['message'];
			if(!$response['version']) $output['message'] = $instance->lang('noversionwasfound');
			return $output;
		}

		$output['data']['formversion'] = $response['version'];
		$cgifile = $response['cgifile'];

		$cpanel = new csfmanager_cpanel;

		$password = $output['data']['server_details']['password'] ? $output['data']['server_details']['password'] : $output['data']['server_details']['accesshash'];
		$password_type = $output['data']['server_details']['password'] ? 'plain' : 'hash';

		$cpanel->setServer($output['data']['server_details']['hostname'], $output['data']['server_details']['username'], $password, $password_type);

		$response = $cpanel->request($cgifile, array(
			'action'	=> 'conf',
		));

		if(!$response['success'])
		{
			$output['success'] = false;
			$output['message'] = $response['message'];
			return $output;
		}

		$html = str_get_dom($response['output']);

		$output['data']['configForm'] = '';

		foreach($html('.virtualpage') as $div)
		{
			foreach($div('input') as $input)
			{
				$input->name = "configVars[{$input->name}]";

				if($input->type == 'text')
				{
					if($output['data']['dontusevalues']) $input->value = '**USE-CURRENT**';
					if(isset($input->size) && intval($input->size) < 20) $input->size = '20';
				}

				unset($input->onkeyup, $input->onfocus, $input->disabled);
			}

			foreach($div('select') as $select)
			{
				echo "<pre>";
				print_r($select('option'));
				exit;
			}
			
			$output['data']['configForm'] .= $div->html();
		} 

		return $output;
	}
}

?>