<?php

class Firewall
{
	var $clientid;
	var $lang;
	var $server;
	var $username;
	var $password;
	var $ip;
	var $port = '2087';
	var $reseller = false;
	private $accountTypes = array(
		'server' 		=> 4, 
		'reselleraccount' 	=> 3, 
		'hostingaccount' 	=> 2, 
		'other' 		=> 1
	);

	function __construct($lang)
	{
		$this->lang = $lang;
	}

	function validateIP($ip_addr) 
	{
		if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $ip_addr)) 
		{
			$parts = explode(".",$ip_addr);

			foreach ($parts as $ip_parts) 
			{
				if (intval($ip_parts) > 255 || intval($ip_parts) < 0) 
				{
					return false;
				}
			}

			return true;
		} 

		return false;
	}

	function setIP($ip)
	{
		if($this->validateIP($ip))
		{
			$this->ip = $ip;
			return true;
		}

		return false;
	}

	function setWHMdetails($server)
	{
		$this->whm_details = $server;
		$this->cpanel = new csfmanager_cpanel;

		$password = $server['password'] ? $server['password'] : $server['accesshash'];
		$password_type = $server['password'] ? 'plain' : 'hash';

		$this->cpanel->setServer($server['hostname'], $server['username'], $password, $password_type);

		$response = $this->cpanel->whm('myprivs');
		$this->reseller = $response['output']['privileges'][0]['all'] ? false : true;
	}

	public function isReseller($servers = null)
	{
		$output = array();

		foreach ($servers as $server => $data)
		{
			if($data['type'] > 2)
			{
				return true;
			}
		}

		return false;
	}

	public function getResellerServers($servers = null)
	{
		$output = array();

		foreach ($servers as $server => $data)
		{
			if($data['type'] > 2) $output[] = $server;
		}

		return $output;
	}

	function temporaryAllow($length, $type, $comment = '')
	{
		if($this->reseller) array('success' => 0, 'message' => "Temporary Allow is not supported with a CSF Manager reseller user defined only root user", 'data' => "");

		$response = csfmanager::checkCsfAlive($this->whm_details);

		if($response['success'])
		{
			$response = $this->cpanel->request($response['cgifile'], array(
				'action'	=> 'tempdeny',
				'do'		=> 'allow',
				'ip'		=> $this->ip,
				'timeout'	=> intval($length),
				'dur'		=> $type,
				'comment'	=> $comment,
			));
		}

		return $response;
	}

	function checkIP($ip = null, $checkbrute = true)
	{
		$output = array('success' => false, 'message' => '', 'data' => '');

		if($ip && !$this->setIP($ip))
		{
			$output['message'] = sprintf($this->lang['cantsetip'], $ip);
			return $output;
		}

		$csf_data = $this->checkCsfRecord();

		if($csf_data['success'] && $csf_data['blocked'])
		{
			$message = $csf_data['message'][0];

			switch($csf_data['type'])
			{
				default:
				case 1:
					$time = strtotime(substr(trim(substr($message, strrpos($message, ' - '))), 2));
					$message = substr($message, 0, strrpos($message, ' - '));
				break;

				case 2:
					$time = 0;

					preg_match("/\((.*?)\)/", $message, $message_match);
					$message = trim($message_match[1]);
				break;

				case 3:
					$time = 0;
				break;
			}

			$output['data']['csf'][] = array(
				'IP'		=> $this->ip,
				'Notes'		=> $message,
				'Date'		=> $time ? date("d/m/Y H:i", $time) : 'Unknown',
			);

			$output['success'] = true;
		}
		elseif(!$csf_data['success'])
		{
			$output['message'] = $brute_data['message'];
			return $output;
		}

		if($checkbrute)
		{
			$brute_notes = array(
				'ftp'		=> $this->lang['bruteerrorftp'],
				'system'	=> $this->lang['bruteerrorsystem'],
				'mail'		=> $this->lang['bruteerrormail'],
			);

			$brute_data = $this->getBruteRecords();

			if($brute_data['success'] && sizeof($brute_data['data']))
			{
				if(in_array($this->ip, $brute_data['data']['ips']))
				{
					foreach($brute_data['data']['items'] as $val_count => $val_data)
					{
						if($this->ip == $val_data['ip'])
						{
							$addl = array();

							if(isset($val_data['logintime'])) $addl['Date'] = date("d/m/Y H:i", $val_data['logintime']);
							if(isset($val_data['exptime'])) $addl['Expiration'] = date("d/m/Y H:i", $val_data['exptime']);
							if(isset($val_data['notes'])) $addl['Notes'] = $val_data['notes'];

							$output['data']['brutes'][] = array_merge($addl, array(
								'IP' 		=> $val_data['ip'],
							));

							break;
						}
					}
				}

				$output['success'] = true;
			}
			elseif(!$brute_data['success'])
			{
				$output['message'] = $brute_data['message'];
			}
		}

		return $output;
	}

	function releaseIP($checkbrute = true)
	{
		$output = array('csf' => array(), 'brute' => array());
		
		$output['csf'] = $this->releaseCsfRecord();
		$output['brute'] = $checkbrute ? $this->flushBruteDB() : array('success' => true, 'message' => '');

		return $output;
	}

	function releaseCsfRecord()
	{
		$output = array('success' => false, 'message' => '');
		
		$response = csfmanager::checkCsfAlive($this->whm_details);

		if($response['success'])
		{
			$response = $this->cpanel->request($response['cgifile'], array(
				'action'	=> $this->reseller ? 'qkill' : 'kill',
				'ip'		=> $this->ip,
			));

			$output['success'] = (strpos($response['output'], 'Removing rule...') !== false || strpos($response['output'], 'temporary block removed') !== false) ? true : false;
			$output['message'] = !$output['success'] ? 'CSF Failed to remove the record' : '';
		}
		else
		{
			$output = $response; 
		}
		
		return $output;
	}

	function quickUnblock()
	{
		$response = csfmanager::checkCsfAlive($this->whm_details);

		if($response['success'])
		{
			$response = $this->cpanel->request($response['cgifile'], array(
				'action'	=> $this->reseller ? 'qkill' : 'kill',
				'ip'		=> $this->ip,
			));

			return $response['success'];
		}

		return false;
	}

	function checkCsfRecord()
	{
		$output = array('success' => false, 'message' => array(), 'type' => null, 'blocked' => false);

		$response = csfmanager::checkCsfAlive($this->whm_details);

		if($response['success'])
		{
			$response = $this->cpanel->request($response['cgifile'], array(
				'action'	=> 'grep',
				'ip'		=> $this->ip,
			));

			if($response['success'])
			{
				$response['output'] = str_replace("\n", "**R**", $response['output']);
				$html = str_get_dom($response['output']);

				$response = $html('pre', 0)->getPlainText();
				$response = trim(str_replace("**R**", "\n", $response));
				$response = explode("\n", $response);	

				$start = (count($response)-5);

				for($i = $start; $i < count($response); $i++)
				{
					if(strpos($response[$i], 'csf.deny') !== false)
					{
						list($dump, $reason) = explode("#", $response[$i]);

						$output['success'] = true;
						$output['message'][] = trim($reason);
						$output['type'] = 1;
						$output['blocked'] = true;
						break;
					}
					elseif(strpos($response[$i], 'Temporary Blocks') !== false)
					{
						list($dump, $reason) = explode("Temporary Blocks:", $response[$i]);

						$output['success'] = true;
						$output['message'][] = trim($reason);
						$output['type'] = 2;
						$output['blocked'] = true;
						break;
					}
					// added on version 1.0.13
					elseif(strpos($response[$i], 'DENYIN') !== false)
					{
						$response = preg_replace('/\s+/', ' ', $response[$i]);
						$response_ary = explode(" ", $response);

						$reason = $response_ary[10] . ' ' . $response_ary[11] . ' ' . $response_ary[12];

						$output['success'] = true;
						$output['message'][] = trim($reason);
						$output['type'] = 3;
						$output['blocked'] = true;
						break;
					}
				}

				if(!$output['blocked'])
				{
					$output['success'] = true;
					$output['message'][] = 4;
				}
			}
			else
			{
				$output['message'][] = $response['message'];
			}
		}
		else
		{
			$output['message'][] = $response['message'];
		}

		return $output;
	}

	function flushBruteDB()
	{
		$response = $this->cpanel->whm('flush_cphulk_login_history_for_ips', array(
			'ip'		=> $this->ip,
		));

		return $response;
	}

	function getBruteRecords()
	{
		$output = array('success' => false, 'data' => array(), 'message' => 'unknown error');

		$data = $this->cpanel->whm('cphulk_status');

		if($data['success'])
		{
			$data = json_decode($data['output'], true);

			if(!$data['data']['is_enabled'])
			{
				$output['success'] = true;
				return $output;
			}
		}
		else
		{
			$output['message'] = 'cant connect to WHM: ' . $data['message'];
			return $output;
		}

		$data = $this->cpanel->whm('get_cphulk_brutes');

		if($data['success'])
		{
			$data = json_decode($data['output'], true);

			foreach($data['data']['brutes'] as $details)
			{
				$details['exptime'] = strtotime($details['exptime']);
				$details['logintime'] = strtotime($details['logintime']);

				$output['data']['ips'][] = $details['ip'];
				$output['data']['items'][] = $details;
			}
		}
		else
		{
			$output['message'] = 'cant connect to WHM: ' . $data['message'];
			return $output;
		}

		$output['success'] = true;

		return $output;
	}
}

?>