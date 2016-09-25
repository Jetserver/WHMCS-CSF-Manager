<?php

if(!defined('CSFMANAGER')) die("This file cannot be accessed directly");

$id = intval($_REQUEST['id']);
$action = $_REQUEST['action'];
$start = intval($_REQUEST['start']);
$limit = 10;

switch($action)
{
	case 'delete':

		$sql = "SELECT a.ip, s.id as server_id, s.name, s.hostname, s.username, s.password, s.accesshash, s.secure
			FROM mod_csfmanager_allow as a
			LEFT JOIN tblservers as s
			ON s.id = a.serverid
			WHERE a.id = '{$id}'";
		$result = mysql_query($sql);
		$allow_details = mysql_fetch_assoc($result);

		if($allow_details)
		{
			$allow_details['password'] = decrypt($allow_details['password'], $cc_encryption_hash);

			$Firewall = new Firewall($LANG);
			$Firewall->setWHMdetails($allow_details);

			// delete this ip
			if($Firewall->setIP($allow_details['ip']))
			{
        	        	if($Firewall->quickUnblock())
				{
					$sql = "DELETE
						FROM mod_csfmanager_allow
						WHERE id = '{$id}'";
					mysql_query($sql);

?>
<div class="successbox">
        <strong><span class="title"><?php echo $LANG['success']; ?></span></strong>
        <br />
        <?php echo $LANG['allowedipremove']; ?>
</div>
<?php
				}
				else
				{
?>
<div class="errorbox">
        <strong><span class="title"><?php echo $LANG['error']; ?></span></strong>
        <br />
        <?php echo $LANG['cantremoveip']; ?>
</div>
<?php   	
				}
			}
			else
			{
?>
<div class="errorbox">
        <strong><span class="title"><?php echo $LANG['error']; ?></span></strong>
        <br />
        <?php echo $LANG['cantsetip']; ?>
</div>
<?php
			}
		}
		else
		{
?>
<div class="errorbox">
        <strong><span class="title"><?php echo $LANG['error']; ?></span></strong>
        <br />
        <?php echo $LANG['ipnotexists']; ?>
</div>
<?php
		}
	break;
}

$search = $_REQUEST['search'];
$search_url = array();

if(is_array($search) && sizeof($search))
{
	foreach($search as $key => $value)
	{
		$search_url[] = "{$key}={$value}";
	}
}

$search_url = sizeof($search_url) ? implode("&", $search_url) : '';

$list = array();

$sql = "SELECT a.*, c.firstname, c.lastname, s.name as server_name
	FROM mod_csfmanager_allow as a
	LEFT JOIN tblclients as c
        ON c.id = a.clientid
	LEFT JOIN tblservers as s
	ON s.id = a.serverid
	WHERE a.expiration > '" . time() . "'
	" . (trim($search['clientname']) ? "AND UPPER(CONCAT_WS(' ', c.firstname, c.lastname)) LIKE UPPER('%" . mysql_real_escape_string(trim($search['clientname'])) . "%')" : '') . "
	" . (intval($search['server']) ? "AND s.id = '" . intval($search['server']) . "'" : '') . "
	" . (trim($search['ip']) ? "AND a.ip LIKE '%" . mysql_real_escape_string(trim($search['ip'])) . "%'" : '') . "
	" . (trim($search['reason']) ? "AND a.reason LIKE '%" . mysql_real_escape_string(trim($search['reason'])) . "%'" : '') . "
	ORDER BY a.time DESC";
$result = mysql_query($sql);
$total = mysql_num_rows($result);

$result = mysql_query($sql . " LIMIT {$start}, {$limit}");
while($allow_details = mysql_fetch_assoc($result))
{
	$list[] = array_merge($allow_details, array('time' => date("d/m/Y H:i", $allow_details['time']), 'expiration' => date("d/m/Y H:i", $allow_details['expiration'])));
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

<form method="post" action="<?php echo $modulelink; ?>&page=allowedlog<?php echo $start ? "&start={$start}" : ''; ?>">

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
        <td class="fieldlabel"><?php echo $LANG['ip']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $search['ip']; ?>" name="search[ip]" /></td>
        <td class="fieldlabel"><?php echo $LANG['reason']; ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $search['reason']; ?>" size="50" name="search[reason]" /></td>
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
                <th><?php echo $LANG['ip']; ?></th>
                <th><?php echo $LANG['time']; ?></th>
                <th><?php echo $LANG['expirationdate']; ?></th>
                <th><?php echo $LANG['reason']; ?></th>
                <th width="20"></th>
        </tr>
        <?php foreach($list as $allowed_ip) { ?>
        <tr>
                <td>
                        <?php if($allowed_ip['firstname']) { ?>
                        <a href="clientssummary.php?userid=<?php echo $allowed_ip['clientid']; ?>"><?php echo $allowed_ip['firstname'] . ' ' . $allowed_ip['lastname']; ?></a>
                        <?php } else { ?>
                        <?php echo sprintf($LANG['clientdeleted'], $allowed_ip['clientid']); ?>
                        <?php } ?>
                </td>
                <td><?php echo $allowed_ip['server_name']; ?></td>
                <td><a href="http://whois.domaintools.com/<?php echo $allowed_ip['ip']; ?>" target="_blank"><?php echo $allowed_ip['ip']; ?></a></td>
                <td><?php echo $allowed_ip['time']; ?></td>
                <td><?php echo $allowed_ip['expiration']; ?></td>
                <td><?php echo $allowed_ip['reason']; ?></td>
                <td>
                        <a title="<?php echo $LANG['removeip']; ?>" onclick="if(!confirm('<?php echo $LANG['sureremove']; ?>')) { return false; }" href="<?php echo $modulelink; ?>&page=allowedlog<?php echo $start ? "&start={$start}" : ''; ?><?php echo $search_url ? "&{$search_url}" : ''; ?>&action=delete&id=<?php echo $allowed_ip['id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="images/delete.gif" />
                        </a>
                </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
</div>

<div class="csf-pagination">
	<?php echo csfPagination($start, $limit, $total, "{$modulelink}&page=allowedlog" . ($search_url ? "&{$search_url}" : '')); ?>
</div>