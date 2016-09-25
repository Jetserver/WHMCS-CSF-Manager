<?php

if(!defined('CSFMANAGER')) die("This file cannot be accessed directly");

$id = intval($_REQUEST['id']);
$action = $_REQUEST['action'];
$start = intval($_REQUEST['start']);
$limit = 10;

$errors = $successes = array();

switch($action)
{
	case 'cancel':

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

				$successes[] = $LANG['keycancelled'];
			}
			else
			{
				if($key_details['key_cancelled']) $errors[] = $LANG['keyalreadycancelled'];
				if(!$key_details['key_clicks_remained']) $errors[] = $LANG['keyfullyused'];
				if($key_details['key_expire'] <= time()) $errors[] = $LANG['keyexpired'];
			}
		}
		else
		{
			$errors[] = $LANG['invalidkey'];
		}

	break;

	case 'reactivate':

		$sql = "SELECT *
			FROM mod_csfmanager_allow_keys
			WHERE key_id = '{$id}'";
		$result = mysql_query($sql);
		$key_details = mysql_fetch_assoc($result);

		if($key_details)
		{
			if($key_details['key_cancelled'] && $key_details['key_clicks_remained'] && $key_details['key_expire'] > time())
			{
				$sql = "UPDATE mod_csfmanager_allow_keys
					SET key_cancelled = 0
					WHERE key_id = '{$key_details['key_id']}'";
				mysql_query($sql);

				$successes[] = $LANG['keyreactivated'];
			}
			else
			{
				if(!$key_details['key_cancelled']) $errors[] = $LANG['keynotcancelled'];
				if(!$key_details['key_clicks_remained']) $errors[] = $LANG['keyfullyused'];
				if($key_details['key_expire'] <= time()) $errors[] = $LANG['keyexpired'];
			}
		}
		else
		{
			$errors[] = $LANG['invalidkey'];
		}

	break;

	case 'resend':

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

			$sendmail = sendCSFmail('CSF Manager Whitelist by Email', $key_details['key_email'], $key_details['key_recipient'], array(
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
				$successes[] = $LANG['emailsent'];
			}
			else
			{
				$errors[] = $sendmail['message'];
			}
		}
		else
		{
			$errors[] = $LANG['invalidkey'];
		}
	break;
}

$search = $_REQUEST['search'];
$search_url = array();

if(!isset($_REQUEST['search'])) $search['status'] = 'valid';

if(is_array($search) && sizeof($search))
{
	foreach($search as $key => $value)
	{
		$search_url[] = "search[{$key}]={$value}";
	}
}

$search_url = sizeof($search_url) ? implode("&", $search_url) : '';

$list = array();

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
	" . (trim($search['clientname']) ? "AND UPPER(CONCAT_WS(' ', c.firstname, c.lastname)) LIKE UPPER('%" . mysql_real_escape_string(trim($search['clientname'])) . "%')" : '') . "
	" . (intval($search['server']) ? "AND s.id = '" . intval($search['server']) . "'" : '') . "
	" . (trim($search['recipient']) ? "AND k.key_recipient LIKE '%" . mysql_real_escape_string(trim($search['recipient'])) . "%'" : '') . "
	" . (trim($search['email']) ? "AND k.key_email LIKE '%" . mysql_real_escape_string(trim($search['email'])) . "%'" : '') . "
	" . (trim($search['key']) ? "AND k.key_hash LIKE '%" . mysql_real_escape_string(trim($search['key'])) . "%'" : '') . "
	ORDER BY k.key_id DESC";
$result = mysql_query($sql);
$total = mysql_num_rows($result);

$result = mysql_query($sql . " LIMIT {$start}, {$limit}");
while($key_details = mysql_fetch_assoc($result))
{
	$list[] = array_merge($key_details, array('key_expire_date' => date("d/m/Y H:i", $key_details['key_expire'])));
}
mysql_free_result($result);

$current_page = (($start / $limit) + 1);
$total_pages = ceil(abs($total / $limit));

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

<form method="post" action="<?php echo $modulelink; ?>&page=allowkeys<?php echo $start ? "&start={$start}" : ''; ?>">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
        <td width="15%" class="fieldlabel"><?php echo $LANG['clientname']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $search['clientname']; ?>" size="25" name="search[clientname]" /></td>
        <td class="fieldlabel"><?php echo $LANG['server']; ?></td>
        <td class="fieldarea">
                <select name="search[server]" class="form-control select-inline">
                        <option value="">- <?php echo $LANG['any']; ?> -</option>
                        <?php foreach($servers as $server) { ?>
                        <option <?php if($search['server'] == $server['id']) { ?>selected="selected" <?php } ?>value="<?php echo $server['id']; ?>"><?php echo $server['name']; ?></option>
                        <?php } ?>
                </select>
        </td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $LANG['recipientname']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $search['recipient']; ?>" size="25" name="search[recipient]" /></td>
        <td class="fieldlabel"><?php echo $LANG['recipientemail']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $search['email']; ?>" size="25" name="search[email]" /></td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $LANG['key']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $search['key']; ?>" size="40" name="search[key]" /></td>
        <td class="fieldlabel"><?php echo $LANG['status']; ?></td>
        <td class="fieldarea">
                <select name="search[status]" class="form-control select-inline">
                        <option value=""<?php if(!trim($search['status'])) { ?> selected="selected"<?php } ?>>- <?php echo $LANG['allkeys']; ?> -</option>
                        <option value="valid"<?php if(trim($search['status']) == 'valid') { ?> selected="selected"<?php } ?>><?php echo $LANG['activekeys']; ?></option>
                        <option value="invalid"<?php if(trim($search['status']) == 'invalid') { ?> selected="selected"<?php } ?>><?php echo $LANG['expiredkeys']; ?></option>
                </select>
        </td>
</tr>
</tbody>
</table>

<img width="1" height="5" src="images/spacer.gif"><br>

<div align="center">
        <input type="submit" class="btn btn-primary" value="<?php echo $LANG['search']; ?>" />
</div>
</form>

<?php echo csfOnpage($total, $limit, $start); ?>

<div class="tablebg">
        <table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
        <tbody>
        <tr>
                <th><?php echo $LANG['client']; ?></th>
                <th><?php echo $LANG['server']; ?></th>
                <th><?php echo $LANG['recipientname']; ?></th>
                <th><?php echo $LANG['recipientemail']; ?></th>
                <th><?php echo $LANG['key']; ?></th>
                <th><?php echo $LANG['expirationdate']; ?></th>
                <th><?php echo $LANG['remained']; ?></th>
                <th width="20"></th>
                <th width="20"></th>
        </tr>
        <?php foreach($list as $allow_key) { ?>
        <tr>
                <td>
                        <?php if($allow_key['firstname']) { ?>
                        <a href="clientssummary.php?userid=<?php echo $allow_key['user_id']; ?>"><?php echo $allow_key['firstname'] . ' ' . $allow_key['lastname']; ?></a>
                        <?php } else { ?>
                        <?php echo sprintf($LANG['clientdeleted'], $allow_key['user_id']); ?>
                        <?php } ?>
                </td>
                <td><?php echo $allow_key['server_name']; ?></td>
                <td><?php echo $allow_key['key_recipient']; ?></td>
                <td><?php echo $allow_key['key_email']; ?></td>
                <td><a href="../index.php?m=csfmanager&action=allow&key=<?php echo $allow_key['key_hash']; ?>" onclick="if(!confirm('<?php echo $LANG['surekey']; ?>')) { return false; }" target="_blank"><?php echo $allow_key['key_hash']; ?></a></td>
	<?php if($allow_key['key_cancelled']) { ?>
                <td colspan="2" style="text-align: center !important;"><strong style="color: #CC0000;"><?php echo $LANG['cancelled']; ?></strong></td>
	<?php } else { ?>
                <td style="text-align: center !important;">
		<?php if($allow_key['key_expire'] > time()) { ?>
			<?php echo $allow_key['key_expire_date']; ?>
		<?php } else { ?>
			<strong style="color: #CC0000;"><?php echo $LANG['expired']; ?></strong>
		<?php } ?>
		</td>
                <td style="text-align: center !important;">
		<?php if($allow_key['key_clicks_remained'] > 0) { ?>
			<?php echo $allow_key['key_clicks_remained']; ?>
		<?php } else { ?>
			<strong style="color: #CC0000;"><?php echo $LANG['fullyused']; ?></strong>
		<?php } ?>
		</td>
	<?php } ?>
                <td>
			<?php if(!$allow_key['key_cancelled'] && $allow_key['key_expire'] > time() && $allow_key['key_clicks_remained'] > 0) { ?>
                        <a title="<?php echo $LANG['resendkey']; ?>" href="<?php echo $modulelink; ?>&page=allowkeys<?php echo $start ? "&start={$start}" : ''; ?><?php echo $search_url ? "&{$search_url}" : ''; ?>&action=resend&id=<?php echo $allow_key['key_id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="images/icons/resendemail.png" />
                        </a>
			<?php } ?>
                </td>
                <td>
			<?php if(!$allow_key['key_cancelled'] && $allow_key['key_expire'] > time() && $allow_key['key_clicks_remained'] > 0) { ?>
                        <a title="<?php echo $LANG['cancelkey']; ?>" onclick="if(!confirm('<?php echo $LANG['surerecancelkey']; ?>')) { return false; }" href="<?php echo $modulelink; ?>&page=allowkeys<?php echo $start ? "&start={$start}" : ''; ?><?php echo $search_url ? "&{$search_url}" : ''; ?>&action=cancel&id=<?php echo $allow_key['key_id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="images/delete.gif" />
                        </a>
			<?php } elseif($allow_key['key_cancelled'] && $allow_key['key_expire'] > time() && $allow_key['key_clicks_remained'] > 0) { ?>
                        <a title="<?php echo $LANG['reactivatekey']; ?>" onclick="if(!confirm('<?php echo $LANG['surerereactivatekey']; ?>')) { return false; }" href="<?php echo $modulelink; ?>&page=allowkeys<?php echo $start ? "&start={$start}" : ''; ?><?php echo $search_url ? "&{$search_url}" : ''; ?>&action=reactivate&id=<?php echo $allow_key['key_id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="../images/statusok.gif" />
                        </a>
			<?php } ?>
                </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
</div>

<div class="csf-pagination">
	<?php echo csfPagination($start, $limit, $total, "{$modulelink}&page=allowkeys" . ($search_url ? "&{$search_url}" : '')); ?>
</div>