<?php
/*
*
* CSF Manager @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ Jetserver Web Hosting
* http://jetserver.co.il
*
**/

class csfmanager_cpanel
{
	var $apioutput = 'json';
	var $hostname = null; 
	var $username = null; 
	var $password = null; 
	var $password_type = null; 
	var $secure = true; 
	var $port = '2087'; 

	function setOutput($output) { $this->apioutput = $output; }
	function getOutput() { return $this->apioutput; }

	function setServer($hostname, $username, $password, $password_type, $secure = true, $port = '2087') 
	{ 
		$this->hostname = $hostname; 
		$this->username = $username; 
		$this->password = $password; 
		$this->password_type = $password_type; 
		$this->secure = $secure; 
		$this->port = $port ? $port : ($secure ? '2087' : '2086'); 
	}

	function exec($module, $func, $username, $args = array(), $version = 2)
	{
		$params = array();

		if(sizeof($args))
		{
			if($version == 1)
			{
				$count = 0;

				foreach($args as $arg)
				{
					$params['arg-' . $count] = $arg;
					$count++;
				}
			}
			else
			{
				$params = $args;
			}
		}

		return $this->whm('cpanel', array_merge(array(
			'cpanel_' . $this->apioutput . 'api_module'		=> $module,
			'cpanel_' . $this->apioutput . 'api_func'		=> $func,
			'cpanel_' . $this->apioutput . 'api_apiversion'		=> $version,
			'cpanel_' . $this->apioutput . 'api_user'		=> $username,
		), $params), true);
	}

	function whm($function, $params = array(), $cpanel = false, $version = 1)
	{
		$output = array('success' => false, 'message' => '', 'output' => '');

		$postfields = array();

		if(!$cpanel) $postfields[] = "api.version={$version}";

		foreach($params as $key => $value)
		{
			$postfields[] = "{$key}={$value}";
		}

		$result = $this->request("{$this->apioutput}-api/{$function}", (sizeof($postfields) ? implode('&', $postfields) : ''));

		if(!$result['success']) return $result;

		if($result['output'])
		{
			switch($this->apioutput)
			{
				case 'xml': $result = simplexml_load_string($result['output']); break;
				case 'json': $result = json_decode($result['output'], true); break;
			}

			if($cpanel)
			{
				switch($params['cpanel_' . $this->apioutput . 'api_apiversion'])
				{
					case 1:
						$output['success'] = isset($result['error']) ? false : true;
						$output['message'] = isset($result['error']) ? trim($result['error']) : '';
						$output['output'] = $result['data']['result'];
					break;

					case 2:
						$result = $result['cpanelresult'];

						if(isset($result['data'][0]['status']))
						{
							$output['success'] = $result['data'][0]['status'] ? true : false;
							$output['message'] = isset($result['data'][0]['statusmsg']) ? trim($result['data'][0]['statusmsg']) : '';
						}
						elseif(isset($result['data'][0]['result']['status']))
						{
							$output['success'] = $result['data'][0]['result']['status'] ? true : false;
							$output['message'] = isset($result['data'][0]['result']['statusmsg']) ? trim($result['data'][0]['result']['statusmsg']) : '';
						}
						elseif(isset($result['error']))
						{
							$output['success'] = false;
							$output['message'] = $result['error'];
						}
						else
						{
							$output['success'] = $result['event']['result'] ? true : false;
							//$output['message'] = 'Unknown Error';
						}

						$output['output'] = $result['data'];
					break;
				}
			}
			else
			{
				switch($version)
				{
					case 0:
						$output['success'] = $result['result']['status'] ? true : false;
						$output['message'] = $result['result']['statusmsg'];
						$output['output'] = $result['result']['options'];
					break;

					case 1:
						$output['success'] = $result['metadata']['result'] ? true : false;
						$output['message'] = preg_replace("'(\r|\n)'", "", $result['metadata']['reason']);
						$output['output'] = $result['data'];
					break;
				}
			}
		}
		else
		{
			$output['message'] = "No result";
		}

		return $output;
	}

	function request($url, $params = null)
	{
		$output = array('success' => true, 'message' => '', 'output' => '');

		if($this->password_type == 'hash')
		{
			$authorization = "Authorization: WHM {$this->username}:" . preg_replace("'(\r|\n)'", "", $this->password);
		}
		else
		{
			$authorization = "Authorization: Basic " . base64_encode("{$this->username}:{$this->password}");
		}

		if(isset($params) && is_array($params))
		{
			$params = http_build_query($params);
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "http" . ($this->secure ? "s" : '') . "://{$this->hostname}:{$this->port}/{$url}");
		if($params) curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization));

		$output['output'] = curl_exec($ch);

		curl_close($ch);

		return $output;
	}
}

?>