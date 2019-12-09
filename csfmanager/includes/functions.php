<?php

if (!defined("WHMCS"))
	die("This file cannot be accessed directly");

class csfmanager
{
	var $lang;
	private $config = array();

	function __construct()
	{
		global $LANG;

		$sql = "SELECT *
			FROM mod_csfmanager_config";
		$result = mysql_query($sql);

		while($config_details = mysql_fetch_assoc($result))
		{
			if(preg_match("/^a:\d+:{.*?}$/", $config_details['value'])) 
			{
				$config_details['value'] = @unserialize($config_details['value']);
			}

			$this->config[$config_details['name']] = $config_details['value'];
		}
		mysql_free_result($result);

		$this->_loadLanguage();
	}
	
	public static function getInstance()
	{
		static $i;
		if(!$i) $i = new csfmanager();
		return $i;
	}
	
	public function getConfig($key, $default = null)
	{
		return isset($this->config[$key]) ? $this->config[$key] : $default;
	}
	
	function setConfig($key, $value)
	{
		if(isset($this->config[$key]))
		{
			$sql = "UPDATE mod_csfmanager_config
				SET value = '" . mysql_real_escape_string($value) . "'
				WHERE name = '" . mysql_real_escape_string($key) . "'";
			$result = mysql_query($sql);
		}
		else
		{
			$sql = "INSERT INTO mod_csfmanager_config (`name`,`value`) VALUES
				('" . mysql_real_escape_string($key) . "', '" . mysql_real_escape_string($value) . "')";
			$result = mysql_query($sql);
		}
	
		$this->config[$key] = $value;
	
		if(preg_match("/^a:\d+:{.*?}$/", $value))
		{
			$value = @unserialize($value);
		}
	
		if($result)
		{
			$this->config[$key] = $value;
			return true;
		}
	
		return false;
	}
	
	static public function request_var($name, $default = null, $options = array())
	{
		if(is_array($name))
		{
			$var = $_REQUEST;
			foreach($name as $key) $var = isset($var[$key]) ? $var[$key] : null;
		}
		else
		{
			$var = isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
		}
	
		$value = null;
	
		if((!isset($var)) || sizeof($options) && !in_array($var, $options))
		{
			$value = $default;
		}
		elseif((!sizeof($options)) || sizeof($options) && in_array($var, $options))
		{
			$value = $var;
		}
	
		if(isset($default))
		{
			switch(gettype($default))
			{
				case 'integer': return intval($value); break;
				case 'double': return floatval($value); break;
				default: return $value; break;
			}
		}
	
		return $value;
	}
	
	static public function trigger_message($success, $message)
	{
		define('JCSF_TRIGGER', true);
		define('JCSF_TRIGGER_TYPE', ($success ? 'success' : 'error'));
		define('JCSF_TRIGGER_TITLE', ($success ? 'Success!' : 'Error!'));
		define('JCSF_TRIGGER_MESSAGE', $message);
	}
	
	function lang($key, $default = null)
	{
		return isset($this->lang[$key]) ? $this->lang[$key] : (isset($default) ? $default : $key);
	}	
	
	private function _loadLanguage()
	{
		global $CONFIG;
	
		$sql = "SELECT language
		FROM tbladmins
		WHERE id = '{$_SESSION['adminid']}'";
		$result = mysql_query($sql);
		$admin_details = mysql_fetch_assoc($result);
	
		$default = 'english';
		$language = strtolower($admin_details['language']);
	
		$admin_lang_file = JCSF_ROOT_PATH . "/lang/{$language}.php";
		$default_lang_file = JCSF_ROOT_PATH . "/lang/{$default}.php";
	
		if(file_exists($admin_lang_file))
		{
			require($admin_lang_file);
		}
		elseif(file_exists($default_lang_file))
		{
			require($default_lang_file);
		}
	
		$this->lang = isset($_ADDONLANG) ? $_ADDONLANG : array();
	}
	
	static public function serverResponse($server, $url = '', $vars = array())
	{
		$output = array('success' => false, 'data' => null, 'message' => 'unknown error');

		$password = $server['password'] ? $server['password'] : $server['accesshash'];
		$auth = $server['password'] ? 'pass' : 'hash';
		$url = "https://{$server['hostname']}:2087/{$url}";
		$postdata = http_build_query($vars, '', '&');

		$authstr;

		if ($auth == 'hash') 
		{
			$authstr = 'Authorization: WHM ' . $server['username'] . ':' . preg_replace("/(\n|\r|\s)/", '', $password) . "\r\n";
		} 
		elseif ($auth == 'pass') 
		{
			$authstr = 'Authorization: Basic ' . base64_encode($server['username'] .':'. $password) . "\r\n";
		} 

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_BUFFERSIZE, 131072);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($authstr . "Content-Type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($postdata) . "\r\n" . "\r\n" . $postdata));
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);

		$result = curl_exec($curl);

		if ($result == false) 
		{
			$output['message'] = 'curl_exec threw error "' . curl_error($curl) . '" for ' . $url . '?' . $postdata;
			$output['error_type'] = 1;
			return $output;
		}

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

		curl_close($curl);

		$headers = substr($result, 0, $header_size);
		$headers = explode("\n", $headers);
		$body = substr($result, $header_size);

		$http_response = '';

		foreach($headers as $header)
		{
			if(strpos($header, 'HTTP') !== false)
			{
				$http_response = $header;
				break;
			}
		}

		if(strpos($http_response, '200 OK') !== false)
		{
			$output['success'] = true;
			$output['data'] = $body;
		}
		else
		{
			if(trim($body) == 'Access denied')
			{
				$output['message'] = "username or/and password is invalid";
			}
			elseif(strpos($body, '<div id="error-wrapper">') !== false)
			{
				preg_match("/<div id=\"error-wrapper\">.*<p>(.*?)<\/p>/msU", $body, $matches);
				$output['message'] = trim($matches[1]);
			}
			else
			{
				$output['message'] = "We got the following HTTP response: {$http_response}";
			}
		}

		return $output;
	}

	static public function checkCsfAlive($server)
	{
		$output = array('success' => false, 'version' => '', 'cgifile' => '', 'message' => '', 'data' => '');

		$files = array('cgi/configserver/csf.cgi','cgi/addon_csf.cgi');

		$message = '';

		foreach($files as $file)
		{
			$response = csfmanager::serverResponse($server, $file);

			if($response['success'])
			{
				preg_match("/v([0-9\.]+)/", $response['data'], $ver_matches);

				$output['version'] = $ver_matches[1];
				$output['cgifile'] = $file;
				$output['success'] = true;
				$output['data'] = $response['data'];

				return $output;
			}

			if(!$output['message'] || strlen($output['message']) > strlen($response['message']))
			{
				$output['message'] = $response['message'];
			}
		}
	
		return $output;
	}
	
	static public function csfPagination($start, $per_page, $num_items, $base_url)
	{
		$instance = csfmanager::getInstance();
		
		$output = '';
		
		$base_url = preg_replace("/&amp;start=[\d]+/", '', $base_url);
		$url_delim = (strpos($base_url, '?') === false) ? '?' : ((strpos($base_url, '?') === strlen($base_url) - 1) ? '' : '&amp;');
		
		$per_page = ($per_page <= 0) ? 1 : $per_page;
	
		$current_page = floor($start / $per_page) + 1;
	
		$prev_disabled = ($current_page == 1) ? true : false;
		$next_disabled = ($current_page < ceil($num_items / $per_page)) ? false : true;
	
		$output .= "<ul class=\"pager\">";
		if($prev_disabled) $output .= "<li class=\"previous disabled\"><a href='#'>&laquo; " . $instance->lang('prevpage') . "</a></li>";
		else $output .= "<li class=\"previous\"><a href='{$base_url}{$url_delim}start=" . (($current_page - 2) * $per_page) . "'>&laquo; " . $instance->lang('prevpage') . "</a></li>";
		if($next_disabled) $output .= "<li class=\"next disabled\"><a href='#'>" . $instance->lang('nextpage') . " &raquo;</a></li>";
		else $output .= "<li class=\"next\"><a href='{$base_url}{$url_delim}start=" . ($current_page * $per_page) . "'>" . $instance->lang('nextpage') . " &raquo;</a></li>";
		$output .= "</ul>";
	
		return $output;
	}

	static public function csfOnpage($num_items, $per_page, $start)
	{
		$instance = csfmanager::getInstance();
		
		// Make sure $per_page is a valid value
		$per_page = ($per_page <= 0) ? 1 : $per_page;
	
		$on_page = floor($start / $per_page) + 1;
	
		return sprintf($instance->lang('onpage'), $num_items,$on_page, max(ceil($num_items / $per_page), 1));
	}
	
	static public function sortby(&$data, $keyname, $order = 'ASC')
	{
		$order = strtoupper($order);
	
		$bykey = array();
	
		foreach($data as $key => $row)
		{
			$bykey[$key] = $row[$keyname];
		}
	
		$order = ($order == 'ASC') ? SORT_ASC : SORT_DESC;
	
		array_multisort($bykey, $order, $data);
	}
	
	static public function sendCSFmail($messagename, $email, $fullname, $email_fields = array())
	{
		global $CONFIG, $whmcs, $templates_compiledir;
	
		$output = array('success' => false, 'message' => null);
	
		if(!$messagename)
		{
			$output['message'] = "No message name was provided";
			return $output;
		}
	
		$sql = "SELECT *
			FROM tblemailtemplates
			WHERE name = '{$messagename}'
			AND language = ''";
		$result = mysql_query($sql);
		$email_data = mysql_fetch_assoc($result);
	
		if(!$email_data)
		{
			$output['message'] = "Message template not exists";
			return $output;
		}
	
		if(!class_exists("Smarty")) 
		{
			require(ROOTDIR . "/includes/smarty/Smarty.class.php");
		}
	
		$smarty = new Smarty();
		$smarty->caching = 0;
		$smarty->compile_dir = $templates_compiledir;
		$smarty->compile_id = md5($email_data['subject'] . $email_data['message']);
	
		foreach($email_fields as $mergefield => $mergevalue) 
		{
			$smarty->assign($mergefield, $mergevalue);
		}
	
		$subject = $smarty->fetch('eval:'.$email_data['subject']);
		$message = $smarty->fetch('eval:'.$email_data['message']);
	
		if(!trim($subject) && !trim($message)) 
		{
			$output['message'] = "EMAILERROR: Email Message Empty so Aborting Sending";
			return $output;
		}
	
		//if(method_exists($whmcs, 'load_class')) $whmcs->load_class("phpmailer");
		include_once(ROOTDIR . "/vendor/phpmailer/phpmailer/PHPMailerAutoload.php");
		$mail = new PHPMailer(true);
												
		try 
		{
			$mail->From = $CONFIG['Email'];
			$mail->FromName = str_replace("&amp;", "&", $CONFIG['CompanyName']);
	
			switch($CONFIG['MailType'])
			{
				case 'mail': 
					$mail->Mailer = "mail";
				break;
	
				case 'smtp': 
					$mail->IsSMTP();
					$mail->Host = $CONFIG['SMTPHost'];
					$mail->Port = $CONFIG['SMTPPort'];
					$mail->Hostname = $_SERVER['SERVER_NAME'];
	
					if ($CONFIG['SMTPSSL']) 
					{
						$mail->SMTPSecure = $CONFIG['SMTPSSL'];
					}
	
					if ($CONFIG['SMTPUsername']) 
					{
						$mail->SMTPAuth = true;
						$mail->Username = $CONFIG['SMTPUsername'];
						$mail->Password = decrypt($CONFIG['SMTPPassword']);
					}
	
					$mail->Sender = $mail->From;
	
					if ($CONFIG['Email'] != $CONFIG['SMTPUsername']) 
					{
						$mail->AddReplyTo($CONFIG['Email'], $CONFIG['CompanyName']);
					}
				break;
			}
	
			$mail->XMailer = $whmcs->get_config('CompanyName');
			$mail->CharSet = $CONFIG['Charset'];
			$mail->AddAddress(trim($email), $fullname);
	
			if($CONFIG['BCCMessages']) 
			{
				$bcc = $CONFIG['BCCMessages'] . ',';
				$bcc = explode(",", $bcc);
	
				foreach ($bcc as $value) 
				{
					$ccaddress = trim($value);
	
					if ($ccaddress)
					{
						$mail->AddBCC($ccaddress);
						continue;
					}
				}
			}
	
			$mail->Subject = $subject;
	
			if($email_data['copyto']) 
			{
				$copytoarray = explode(',', $email_data['copyto']);
	
				if ($CONFIG['MailType'] == 'mail') 
				{
					foreach ($copytoarray as $copytoemail) 
					{
						$mail->AddBCC(trim($copytoemail));
					}
				}
				else 
				{
					foreach ($copytoarray as $copytoemail) 
					{
						$mail->AddCC(trim($copytoemail));
					}
				}
			}
	
			if($email_data['plaintext']) 
			{
				$message = str_replace("<br>", "", $message);
				$message = str_replace("<br />", "", $message);
				$message = strip_tags($message);
				$mail->Body = html_entity_decode($message);
				$message = nl2br($message);
			}
			else 
			{
				$message_text = str_replace("<p>", "", $message);
				$message_text = str_replace("</p>", "\n\n", $message_text);
				$message_text = str_replace("<br>", "\n", $message_text);
				$message_text = str_replace("<br />", "\n", $message_text);
				$message_text = strip_tags($message_text);
	
				$cssdata = "";
	
				if ($CONFIG['EmailCSS']) 
				{
					$cssdata = "<style>\n" . $CONFIG['EmailCSS'] . "\n</style>";
				}
	
				$message = $cssdata . "\n" . $message;
				$mail->Body = $message;
				$mail->AltBody = $message_text;
			}
	
			if($email_data['attachments']) 
			{
				$tplattachments = explode(',', $email_data['attachments']);
	
				foreach($email_data['attachments'] as $attachment) 
				{
					$filename = $downloads_dir . $attachment;
					$displayname = substr($attachment, 7);
					$mail->AddAttachment($filename, $displayname);
				}
			}
	
			$mail->Send();
			$mail->ClearAddresses();
	
			$output['success'] = true;
	
			return $output;
		} 
		catch (phpmailerException $e) 
		{
			$output['message'] = "Email Sending Failed - ".$e->getMessage();
			return $output;
		}
		catch (Exception $e) 
		{
			$output['message'] = "Email Sending Failed - ".$e->getMessage();
			return $output;
		}
	}
	
	static public function csfValidateEmail($email)
	{
		return preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', $email);
	}
}

?>
