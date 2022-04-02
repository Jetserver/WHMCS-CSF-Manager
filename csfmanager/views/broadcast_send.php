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

class jcsf_broadcast_send extends jcsf_broadcast_default
{
	public function _default()
	{	
		global $instance, $cc_encryption_hash;
		
		$output = array('success' => true, 'message' => '', 'data' => array());

		$instance = csfmanager::getInstance();
		
		$server_id = csfmanager::request_var('server_id', 0);
		$new_vars = csfmanager::request_var('new_vars', array());
		
		if(!is_array($new_vars) || !sizeof($new_vars))
		{
			echo json_encode(array(
				'success'	=> true,
				'message'	=> $instance->lang('nochanges'),
				'new_vars'	=> $new_vars,
			));
			exit;
		}
		
		$form_version = csfmanager::request_var('formversion', '');
		
		$output['data']['selectedservers'] = csfmanager::request_var('selectedservers', array());
		
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
		mysqli_fetch_assoc($result);

		if(!isset($output['data']['servers'][$server_id]))
		{
			$output['success'] = false;
			$output['message'] = $instance->lang('notemplateserverselected');
			return $output;
		}
		
		$output['data']['server_details'] = $output['data']['servers'][$server_id];
		
		$response = csfmanager::checkCsfAlive($output['data']['server_details']);
		
		if(!$response['success'] || !$response['version'])
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> !$response['success'] ? $response['message'] : $instance->lang('noversionwasfound'),
				'new_vars'	=> $new_vars,
			));
		}

		if($form_version != $response['version'])
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> sprintf($instance->lang('versionmismatch'), $form_version, $response['version']),
				'new_vars'	=> $new_vars,
			));
		}
		
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
			echo json_encode(array(
				'success'	=> false,
				'message'	=> $response['message'],
				'new_vars'	=> $new_vars,
			));
		}

		$oldVars = array();

		$html = str_get_dom($response['output']);

		foreach($html('.virtualpage') as $div)
		{
			foreach($div('input') as $input)
			{
				$oldVars[$input->name] = $input->value;
			}
		} 

		$configVars = array_merge($oldVars, $new_vars);

		$response = $cpanel->request($cgifile, array_merge(array(
			'action'	=> 'saveconf',
		), $configVars));

		if($response['success'])
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> $response['message'],
				'new_vars'	=> $new_vars,
			));
		}

		$response = $cpanel->request($cgifile, array(
			'action'	=> 'restartboth',
		));

		if($response['success'])
		{
			echo json_encode(array(
				'success'	=> true,
				'message'	=> $instance->lang('updatedsuccessfully'),
				'new_vars'	=> $new_vars,
			));
		}
		else
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> $response['message'],
				'new_vars'	=> $new_vars,
			));
		}

		return $output;
	}
}

?>