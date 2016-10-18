<?php

if(!defined('CSFMANAGER')) die("This file cannot be accessed directly");

$servers = array();

$sql = "SELECT *
	FROM tblservers
	" . (trim($config['servers']) ? "WHERE id IN ({$config['servers']})" : '') . "
	ORDER BY name ASC";
$result = mysql_query($sql);

while($server_details = mysql_fetch_assoc($result))
{
	$servers[$server_details['id']] = array_merge($server_details, array('password' => decrypt($server_details['password'], $cc_encryption_hash)));
}
mysql_free_result($result);

$clients = array();

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
	" . (trim($config['servers']) ? "AND s.id IN ({$config['servers']})" : '') . "
	AND p.type IN ('hostingaccount','reselleraccount','server')
	ORDER BY c.firstname ASC, c.lastname ASC, c.id ASC";
$result = mysql_query($sql);

while($client_details = mysql_fetch_assoc($result))
{                     
	$clients[$client_details['id']] = $client_details;
}
mysql_free_result($result);

if(isset($_POST['submit']))
{
	$generate = $_REQUEST['generate'];

	$client_id = intval($generate['clientid']) ? intval($generate['clientid']) : intval($generate['client']);

	if($generate['recipient'] && $generate['email'] && csfValidateEmail($generate['email']) && $client_id && isset($clients[$client_id]) && intval($generate['server']) && isset($servers[$generate['server']]))
	{
		$hashkey = md5($generate['email'] . rand() . time());
		$sysurl = ($CONFIG["SystemSSLURL"] ? $CONFIG["SystemSSLURL"] : $CONFIG["SystemURL"]);
		$whitelist_url = "{$sysurl}/index.php?m=csfmanager&action=allow&key={$hashkey}";
		$cancel_url = "{$sysurl}/index.php?m=csfmanager&action=cancel&key={$hashkey}";
		$valid_days = 365;
		$valid_clicks = 10;

		$sendmail = sendCSFmail('CSF Manager Whitelist by Email', $generate['email'], $generate['recipient'], array(
			'emailfullname'		=> $generate['recipient'],
			'firstname'		=> $clients[$client_id]['firstname'],
			'lastname'		=> $clients[$client_id]['lastname'],
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
				('{$client_id}','{$clients[$client_id]['server_id']}','{$clients[$client_id]['hosting_id']}','{$hashkey}','{$generate['recipient']}','{$generate['email']}',{$valid_clicks},'" . (time() + (60 * 60 * 24 * $valid_days)) . "')";
			mysql_query($sql);

			$successes[] = $LANG['emailsent'];
		}
		else
		{
			$errors[] = $sendmail['message'];
		}
	}
	else
	{
		if(!$generate['recipient']) $errors[] = $LANG['emptyrecipientname'];
		if(!$generate['email']) $errors[] = $LANG['emptyrecipientemail'];
		if($generate['email'] && !csfValidateEmail($generate['email'])) $errors[] = $LANG['invalidrecipientemail'];
		if(!$client_id) $errors[] = $LANG['emptyclient'];
		if($client_id && !isset($clients[$client_id])) $errors[] = $LANG['invalidclient'];
		if(!intval($generate['server'])) $errors[] = $LANG['emptyserver'];
		if(intval($generate['server']) && !isset($servers[$generate['server']])) $errors[] = $LANG['invalidserver'];
	}
}

?>

<?php if(sizeof($successes)) { ?>
<div class="successbox">
        <strong><span class="title"><?php echo $LANG['success']; ?></span></strong>
        <br />
        <?php echo implode('<br />', $successes); ?>
</div>
<?php } ?>

<?php if(sizeof($errors)) { ?>
<div class="errorbox">
        <strong><span class="title"><?php echo $LANG['error']; ?></span></strong>
        <br />
        <?php echo implode('<br />', $errors); ?>
</div>
<?php } ?>

<form method="post" action="<?php echo $modulelink; ?>&page=generatekey<?php echo $start ? "&start={$start}" : ''; ?>">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
        <td width="15%" class="fieldlabel"><?php echo $LANG['recipientname']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $generate['recipient']; ?>" size="25" name="generate[recipient]" /></td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $LANG['recipientemail']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $generate['email']; ?>" size="25" name="generate[email]" /></td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $LANG['client']; ?></td>
        <td class="fieldarea">
		<?php echo $LANG['clientid']; ?>:
        	<input type="text" value="<?php echo $generate['clientid']; ?>" size="5" name="generate[clientid]" />
		<?php echo $LANG['or']; ?>
                <select name="generate[client]" class="form-control select-inline">
                        <option value="">- <?php echo $LANG['selectclient']; ?> -</option>
                        <?php foreach($clients as $client) { ?>
                        <option <?php if($generate['client'] == $client['id']) { ?>selected="selected" <?php } ?>value="<?php echo $client['id']; ?>"><?php echo $client['firstname']; ?> <?php echo $client['lastname']; ?> (<?php echo $client['domain']; ?>)</option>
                        <?php } ?>
                </select>
	</td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $LANG['server']; ?></td>
        <td class="fieldarea">
                <select name="generate[server]" class="form-control select-inline">
                        <option value="">- <?php echo $LANG['selectserver']; ?> -</option>
                        <?php foreach($servers as $server) { ?>
                        <option <?php if($generate['server'] == $server['id']) { ?>selected="selected" <?php } ?>value="<?php echo $server['id']; ?>"><?php echo $server['name']; ?></option>
                        <?php } ?>
                </select>
        </td>
</tbody>
</table>

<img width="1" height="5" src="images/spacer.gif"><br>

<div align="center">
        <input type="submit" name="submit" class="btn btn-primary" value="<?php echo $LANG['createkey']; ?>" />
</div>
</form>
