<?php

if(!defined('CSFMANAGER')) die("This file cannot be accessed directly");

$submit = $_REQUEST['submit'] ? true : false;
$action = $_REQUEST['action'];
$oparation = $_REQUEST['oparation'];

$servers = array();

$sql = "SELECT *
	FROM tblservers
	" . (trim($config['servers']) ? "WHERE id IN ({$config['servers']})" : '');
$result = mysql_query($sql);

while($server_details = mysql_fetch_assoc($result))
{
	$servers[$server_details['id']] = array_merge($server_details, array('password' => decrypt($server_details['password'], $cc_encryption_hash)));
}
mysql_free_result($result);

$server_id = intval($_REQUEST['server_id']);

if($server_id && in_array($server_id, array_keys($servers)))
{
	$password = $servers[$server_id]['password'] ? $servers[$server_id]['password'] : $servers[$server_id]['accesshash'];
	$password_type = $servers[$server_id]['password'] ? 'plain' : 'hash';

?>
<?php
	if((!$servers[$server_id]['hostname'] && !$servers[$server_id]['ipaddress']) || !$servers[$server_id]['username'] || !$password)
	{
?>
		<h2>Managing "<?php echo $servers[$server_id]['name']; ?>"</h2>
		<p>Missing Server information</p>
<?php
	}
	else
	{
		$cpanel = new csfmanager_cpanel;

		$cpanel->setServer(($servers[$server_id]['ipaddress'] ? $servers[$server_id]['ipaddress'] : $servers[$server_id]['hostname']), $servers[$server_id]['username'], $password, $password_type);


		$response = $cpanel->whm('create_user_session', array(
			'user'		=> $servers[$server_id]['username'],
			'service'	=> 'whostmgrd',
		));
?>
		<h2 style="position: absolute; top: 0; left: 0; background: #fff; margin: 10px 0; display: block; padding: 9px 19px; width: 100%;">Managing "<?php echo $servers[$server_id]['name']; ?>"</h2>
		<iframe id="jbmFrame" src="<?php echo $response['output']['url'] . '&goto_uri=/cgi/configserver/csf.cgi'; ?>" style="border: 0 none; width: 100%; height: 55000px;"></iframe>

<?php
	}
}
else
{
	sortby($servers, 'name', 'asc');

?>
<form action="" method="post">

	<div style="padding: 20px; text-align: center;">

		<h1 style="margin: 0;">Please select a server to manage.</h1>
		<p></p>
		<select name="server_id" class="form-control select-inline" style="vertical-align: middle;">
			<?php foreach($servers as $server) { ?>
			<?php if((!$server['password'] && !$server['accesshash']) || (!$server['hostname'] || !$server['ipaddress']) || !$server['username'] || $server['type'] != 'cpanel') continue; ?>
			<option value="<?php echo $server['id']; ?>"><?php echo $server['name']; ?></option>
			<?php } ?>
		</select>
		<input type="submit" name="submit" class="btn btn-primary" value="Select Server" />
	</div>
</form>
<?php
}
?>