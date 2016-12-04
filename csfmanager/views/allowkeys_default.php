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

class jcsf_allowkeys_default
{
	public function _default()
	{	
		global $cc_encryption_hash, $instance;
		
		$output = array('success' => true, 'message' => '', 'data' => array());

		$instance = csfmanager::getInstance();
		
		$id = csfmanager::request_var('id', 0);
		$start = csfmanager::request_var('start', 0);
		$search = csfmanager::request_var('search', array('status' => 'valid'));
		
		$limit = 10;

		$output['data']['search_url'] = sizeof($search) ? http_build_query($search) : '';
		
		$output['data']['list'] = array();
		
		$status_query = "";
		
		if($search['status'] == 'valid')
		{
			$status_query = "k.key_expire > '" . time() . "' AND k.key_clicks_remained > 0 AND k.key_cancelled = 0";
		}
		elseif($search['status'] == 'invalid')
		{
			$status_query = "(k.key_expire <= '" . time() . "' OR k.key_clicks_remained <= 0 OR k.key_cancelled = 1)";
		}

		$sql = "SELECT k.*, c.firstname, c.lastname, s.name as server_name
			FROM mod_csfmanager_allow_keys as k
			LEFT JOIN tblclients as c
		        ON c.id = k.user_id
			LEFT JOIN tblservers as s
			ON s.id = k.server_id
			WHERE key_id > 0 
			" . ($status_query ? "AND {$status_query}" : '') . "
			" . (trim($search['clientname']) ? "AND UPPER(CONCAT_WS(' ', c.firstname, c.lastname)) LIKE UPPER('%" . mysql_escape_string(trim($search['clientname'])) . "%')" : '') . "
			" . (intval($search['server']) ? "AND s.id = '" . intval($search['server']) . "'" : '') . "
			" . (trim($search['recipient']) ? "AND k.key_recipient LIKE '%" . mysql_escape_string(trim($search['recipient'])) . "%'" : '') . "
			" . (trim($search['email']) ? "AND k.key_email LIKE '%" . mysql_escape_string(trim($search['email'])) . "%'" : '') . "
			" . (trim($search['key']) ? "AND k.key_hash LIKE '%" . mysql_escape_string(trim($search['key'])) . "%'" : '') . "
			ORDER BY k.key_id DESC";
		$result = mysql_query($sql);

		$output['data']['total'] = mysql_num_rows($result);
		
		$result = mysql_query($sql . " LIMIT {$start}, {$limit}");

		while($key_details = mysql_fetch_assoc($result))
		{
			$output['data']['list'][] = array_merge($key_details, array('key_expire_date' => date("d/m/Y H:i", $key_details['key_expire'])));
		}
		mysql_free_result($result);
		
		$output['data']['current_page'] = (($start / $limit) + 1);
		$output['data']['total_pages'] = ceil(abs($output['data']['total'] / $limit));
		$output['data']['start'] = $start;
		$output['data']['limit'] = $limit;
		
		$output['data']['servers'] = array();
		
		$sql = "SELECT *
			FROM tblservers
			" . (trim($instance->getConfig('servers', '')) ? "WHERE id IN (" . trim($instance->getConfig('servers', '')) . ")" : '');
		$result = mysql_query($sql);
		
		while($server_details = mysql_fetch_assoc($result))
		{
			$output['data']['servers'][$server_details['id']] = array_merge($server_details, array('password' => decrypt($server_details['password'], $cc_encryption_hash)));
		}
		mysql_free_result($result);
		
		return $output;
	}

	public function cancel()
	{	
		$output = array('success' => false, 'message' => '', 'data' => array());

		$id = csfmanager::request_var('id', 0);
		
		$instance = csfmanager::getInstance();
		
		$sql = "SELECT *
			FROM mod_csfmanager_allow_keys
			WHERE key_id = '{$id}'";
		$result = mysql_query($sql);
		$key_details = mysql_fetch_assoc($result);

		if($key_details)
		{
			if(!$key_details['key_cancelled'] && $key_details['key_clicks_remained'] && $key_details['key_expire'] > time())
			{
				$sql = "UPDATE mod_csfmanager_allow_keys
					SET key_cancelled = 1
					WHERE key_id = '{$key_details['key_id']}'";
				mysql_query($sql);

				$output['success'] = true;
				$output['message'] = $instance->lang('keycancelled');
			}
			else
			{
				if($key_details['key_cancelled']) $errors[] = $instance->lang('keyalreadycancelled');
				if(!$key_details['key_clicks_remained']) $errors[] = $instance->lang('keyfullyused');
				if($key_details['key_expire'] <= time()) $errors[] = $instance->lang('keyexpired');
			}
		}
		else
		{
			$output['errormessages'][] = $instance->lang('invalidkey');
		}
		
		return $output;
	}

	public function reactivate()
	{	
		$output = array('success' => false, 'message' => '', 'data' => array());

		$id = csfmanager::request_var('id', 0);
		
		$instance = csfmanager::getInstance();
		
		$sql = "SELECT *
			FROM mod_csfmanager_allow_keys
			WHERE key_id = '{$id}'";
		$result = mysql_query($sql);
		$key_details = mysql_fetch_assoc($result);

		if($key_details)
		{
			if(!$key_details['key_cancelled'] && $key_details['key_clicks_remained'] && $key_details['key_expire'] > time())
			{
				$sql = "UPDATE mod_csfmanager_allow_keys
					SET key_cancelled = 0
					WHERE key_id = '{$key_details['key_id']}'";
				mysql_query($sql);
				
				$output['success'] = true;
				$output['message'] = $instance->lang('keyreactivated');
			}
			else
			{
				if($key_details['key_cancelled']) $errors[] = $instance->lang('keyalreadycancelled');
				if(!$key_details['key_clicks_remained']) $errors[] = $instance->lang('keyfullyused');
				if($key_details['key_expire'] <= time()) $errors[] = $instance->lang('keyexpired');
			}
		}
		else
		{
			$output['errormessages'][] = $instance->lang('invalidkey');
		}
		
		return $output;
	}

	public function resend()
	{	
		global $CONFIG;
		
		$output = array('success' => false, 'message' => '', 'data' => array());

		$id = csfmanager::request_var('id', 0);
		
		$instance = csfmanager::getInstance();

		$sql = "SELECT k.*, c.firstname, c.lastname
			FROM mod_csfmanager_allow_keys as k
			INNER JOIN tblclients as c
			ON c.id = k.user_id
			WHERE k.key_id = '{$id}'
			AND k.key_cancelled = 0
			AND k.key_clicks_remained > 0
			AND k.key_expire > '" . time() . "'";
		$result = mysql_query($sql);
		$key_details = mysql_fetch_assoc($result);
		
		if($key_details)
		{
			$sysurl = ($CONFIG["SystemSSLURL"] ? $CONFIG["SystemSSLURL"] : $CONFIG["SystemURL"]);
			$whitelist_url = "{$sysurl}/index.php?m=csfmanager&action=allow&key={$key_details['key_hash']}";
			$cancel_url = "{$sysurl}/index.php?m=csfmanager&action=cancel&key={$key_details['key_hash']}";
		
			$sendmail = csfmanager::sendCSFmail('CSF Manager Whitelist by Email', $key_details['key_email'], $key_details['key_recipient'], array(
				'emailfullname'		=> $key_details['key_recipient'],
				'firstname'		=> $key_details['firstname'],
				'lastname'		=> $key_details['lastname'],
				'whitelist_url'		=> $whitelist_url,
				'valid_days'		=> ceil(($key_details['key_expire'] - time()) / (60 * 60 * 24)),
				'valid_clicks'		=> $key_details['key_clicks_remained'],
				'cancel_url'		=> $cancel_url,
				'signature'		=> nl2br(html_entity_decode($CONFIG['Signature'])),
			));
		
			if($sendmail['success'])
			{
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
			$output['errormessages'][] = $instance->lang('invalidkey');
		}
		
		return $output;
	}
}

?>