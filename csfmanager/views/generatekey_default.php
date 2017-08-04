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

class jcsf_generatekey_default
{
	public function _default()
	{	
		global $cc_encryption_hash, $instance;
		
		$output = array('success' => true, 'message' => '', 'data' => array());

		$instance = csfmanager::getInstance();
		
		$output['data']['generate'] = csfmanager::request_var('generate', array());
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
		
		$output['data']['clients'] = array();
		
		$sql = "SELECT c.*, h.domain, p.id as product_id, s.id as server_id, h.id as hosting_id
			FROM tblclients as c
			INNER JOIN tblhosting as h
			ON h.userid = c.id
			INNER JOIN tblproducts as p
			ON p.id = h.packageid
			INNER JOIN tblservers as s
			ON s.id = h.server
			WHERE c.status = 'Active'
			AND h.domainstatus = 'Active'
			" . (trim($instance->getConfig('servers', '')) ? "AND s.id IN (" . trim($instance->getConfig('servers', '')) . ")" : '') . "
			AND p.type IN ('hostingaccount','reselleraccount','server')
			ORDER BY c.firstname ASC, c.lastname ASC, c.id ASC";
		$result = mysql_query($sql);
		
		while($client_details = mysql_fetch_assoc($result))
		{                     
			$output['data']['clients'][$client_details['id']] = $client_details;
		}
		//mysql_free_result($result);
		
		return $output;
	}

	public function create()
	{	
		global $cc_encryption_hash, $instance, $CONFIG;
		
		$output = $this->_default();
		if(!$output['success']) return $output;
		$output['success'] = false;

		$instance = csfmanager::getInstance();

		$client_id = intval($output['data']['generate']['clientid']) ? intval($output['data']['generate']['clientid']) : intval($output['data']['generate']['client']);

		if($output['data']['generate']['recipient'] && $output['data']['generate']['email'] && csfmanager::csfValidateEmail($output['data']['generate']['email']) && $client_id && isset($output['data']['clients'][$client_id]) && intval($output['data']['generate']['server']) && isset($output['data']['servers'][$output['data']['generate']['server']]))
		{
			$hashkey = md5($output['data']['generate']['email'] . rand() . time());
			$sysurl = ($CONFIG["SystemSSLURL"] ? $CONFIG["SystemSSLURL"] : $CONFIG["SystemURL"]);
			$whitelist_url = "{$sysurl}/index.php?m=csfmanager&action=allow&key={$hashkey}";
			$cancel_url = "{$sysurl}/index.php?m=csfmanager&action=cancel&key={$hashkey}";
			$valid_days = 365;
			$valid_clicks = 10;
	
			$sendmail = csfmanager::sendCSFmail('CSF Manager Whitelist by Email', $output['data']['generate']['email'], $output['data']['generate']['recipient'], array(
				'emailfullname'		=> $output['data']['generate']['recipient'],
				'firstname'		=> $output['data']['clients'][$client_id]['firstname'],
				'lastname'		=> $output['data']['clients'][$client_id]['lastname'],
				'whitelist_url'		=> $whitelist_url,
				'valid_days'		=> $valid_days,
				'valid_clicks'		=> $valid_clicks,
				'cancel_url'		=> $cancel_url,
				'signature'		=> nl2br(html_entity_decode($CONFIG['Signature'])),
			));
	
			if($sendmail['success'])
			{
				logActivity("Jetserver CSF Manager :: The admin sent allow ket to the recipient {$email} ({$fullname}) on behalf of <a href=\"clientssummary.php?userid={$uid}\">Client ID: {$uid}</a>");
	
				$sql = "INSERT INTO mod_csfmanager_allow_keys (`user_id`,`server_id`,`product_id`,`key_hash`,`key_recipient`,`key_email`,`key_clicks_remained`,`key_expire`) VALUES
					('{$client_id}','{$output['data']['clients'][$client_id]['server_id']}','{$output['data']['clients'][$client_id]['hosting_id']}','{$hashkey}','{$output['data']['generate']['recipient']}','{$output['data']['generate']['email']}',{$valid_clicks},'" . (time() + (60 * 60 * 24 * $valid_days)) . "')";
				mysql_query($sql);
	
				$output['success'] = true;
				$output['message'] = $instance->lang('emailsent');
			}
			else
			{
				$output['errormessages'][] = $sendmail['message'];
			}
		}
		else
		{
			if(!$output['data']['generate']['recipient']) $output['errormessages'][] = $instance->lang('emptyrecipientname');
			if(!$output['data']['generate']['email']) $output['errormessages'][] = $instance->lang('emptyrecipientemail');
			if($output['data']['generate']['email'] && !csfValidateEmail($generate['email'])) $output['errormessages'][] = $instance->lang('invalidrecipientemail');
			if(!$client_id) $output['errormessages'][] = $instance->lang('emptyclient');
			if($client_id && !isset($output['data']['clients'][$client_id])) $output['errormessages'][] = $instance->lang('invalidclient');
			if(!intval($output['data']['generate']['server'])) $output['errormessages'][] = $instance->lang('emptyserver');
			if(intval($output['data']['generate']['server']) && !isset($output['data']['servers'][$output['data']['generate']['server']])) $output['errormessages'][] = $instance->lang('invalidserver');
		}
			
		return $output;
	}
}

?>