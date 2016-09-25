<?php

if(!defined('CSFMANAGER')) die("This file cannot be accessed directly");


$servers = array();

$sql = "SELECT *
	FROM tblservers
	WHERE hostname != ''
	ORDER BY hostname ASC";
$result = mysql_query($sql);

while($server_details = mysql_fetch_assoc($result))
{
	$servers[$server_details['id']] = array_merge(array('selected' => in_array($server_details['id'], explode(',', $config['servers'])) ? true : false), $server_details);
}
mysql_free_result($result);

if($submit)
{
	$config_values = $_REQUEST['config'];

	if(is_array($config_values) && sizeof($config_values))
	{
		foreach($config_values as $config_name => $config_value)
		{
			if(!isset($config[$config_name])) continue;

			if($config_name == 'allowlength') $config_value = intval($config_value);
			if($config_name == 'allowlength_type') $config_value = in_array($config_value, array('seconds','minutes','hours','days')) ? $config_value : 'days';

			setConfig($config_name, $config_value);
			$config[$config_name] = $config_value;
		}

		$selectedservers = $_REQUEST['selectedservers'];
		$newypes = array();

		if(is_array($selectedservers) && sizeof($selectedservers))
		{
			foreach($selectedservers as $server_id)
			{
				if(in_array($server_id, array_keys($servers))) $newservers[] = $server_id;
			}

			setConfig('servers', (sizeof($newservers) ? implode(',', $newservers) : ''));
			$config['servers'] = (sizeof($newservers) ? implode(',', $newservers) : '');
		}
		else
		{
			setConfig('servers', '');
			$config['servers'] = '';
		}

?>

<div class="successbox">
	<strong><span class="title"><?php echo $LANG['info']; ?></span></strong>
	<br />
	<?php echo $LANG['chagessaved']; ?>
</div>
<?php
	}

	foreach($servers as $server_id => $server_details)
	{
		$servers[$server_id]['selected'] = in_array($server_id, $newservers) ? true : false;
	}
}

?>

<script>
$(document).ready(function(){

	$("#serveradd").click(function () {
		$("#serverslist option:selected").appendTo("#selectedservers");
		return false;
	});

	$("#serverrem").click(function () {
		$("#selectedservers option:selected").appendTo("#serverslist");
		return false;
	});
});
</script>

<form action="" method="post">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
	<td class="fieldlabel" style="width: 30%;"><?php echo $LANG['canviewfirewalltab']; ?></td>
	<td class="fieldarea">
		<input type="radio" value="1"<?php if($config['permission_firewall']) { ?> checked="checked"<?php } ?> name="config[permission_firewall]"> Yes
		<input type="radio" value="0"<?php if(!$config['permission_firewall']) { ?> checked="checked"<?php } ?> name="config[permission_firewall]"> No
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['canviewunblocktab']; ?></td>
	<td class="fieldarea">
		<input type="radio" value="1"<?php if($config['permission_unblock']) { ?> checked="checked"<?php } ?> name="config[permission_unblock]"> Yes
		<input type="radio" value="0"<?php if(!$config['permission_unblock']) { ?> checked="checked"<?php } ?> name="config[permission_unblock]"> No
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['canunblockclients']; ?></td>
	<td class="fieldarea">
		<input type="radio" value="1"<?php if($config['permission_aunblock']) { ?> checked="checked"<?php } ?> name="config[permission_aunblock]"> Yes
		<input type="radio" value="0"<?php if(!$config['permission_aunblock']) { ?> checked="checked"<?php } ?> name="config[permission_aunblock]"> No
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['canviewallowtab']; ?></td>
	<td class="fieldarea">
		<input type="radio" value="1"<?php if($config['permission_allow']) { ?> checked="checked"<?php } ?> name="config[permission_allow]"> Yes
		<input type="radio" value="0"<?php if(!$config['permission_allow']) { ?> checked="checked"<?php } ?> name="config[permission_allow]"> No
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['canviewallowemailtab']; ?></td>
	<td class="fieldarea">
		<input type="radio" value="1"<?php if($config['permission_allowemail']) { ?> checked="checked"<?php } ?> name="config[permission_allowemail]"> Yes
		<input type="radio" value="0"<?php if(!$config['permission_allowemail']) { ?> checked="checked"<?php } ?> name="config[permission_allowemail]"> No
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['brutecheck']; ?></td>
	<td class="fieldarea">
		<input type="radio" value="1"<?php if($config['checkbrute']) { ?> checked="checked"<?php } ?> name="config[checkbrute]"> Yes
		<input type="radio" value="0"<?php if(!$config['checkbrute']) { ?> checked="checked"<?php } ?> name="config[checkbrute]"> No
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['allowlength']; ?></td>
	<td class="fieldarea">
		<input name="config[allowlength]" value="<?php echo $config['allowlength']; ?>" size="4" /> 

		<select name="config[allowlength_type]" class="form-control select-inline">
			<option<?php if($config['allowlength_type'] == 'seconds') { ?> selected="selected"<?php } ?> value="seconds"><?php echo $LANG['seconds']; ?></option>
			<option<?php if($config['allowlength_type'] == 'minutes') { ?> selected="selected"<?php } ?> value="minutes"><?php echo $LANG['minutes']; ?></option>
			<option<?php if($config['allowlength_type'] == 'hours') { ?> selected="selected"<?php } ?> value="hours"><?php echo $LANG['hours']; ?></option>
			<option<?php if($config['allowlength_type'] == 'days') { ?> selected="selected"<?php } ?> value="days"><?php echo $LANG['days']; ?></option>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $LANG['servers']; ?></td>
	<td class="fieldarea">
		<table>
		<tbody>
		<tr>
			<td>
				<select style="width:200px;" id="serverslist" multiple="multiple" size="10">
					<?php foreach($servers as $server_id => $server_details) { ?>
					<?php if($server_details['selected']) continue; ?>
					<option value="<?php echo $server_id; ?>"><?php echo $server_details['hostname']; ?></option>
					<?php } ?>
				</select>
			</td>
			<td align="center">
				<input type="button" value="<?php echo $LANG['add']; ?> »" id="serveradd" class="btn btn-xs" /><br><br>
				<input type="button" value="« <?php echo $LANG['remove']; ?>" id="serverrem" class="btn btn-xs" />
			</td>
			<td>
				<select style="width:200px;" name="selectedservers[]" id="selectedservers" multiple="multiple" size="10">
					<?php foreach($servers as $server_id => $server_details) { ?>
					<?php if(!$server_details['selected']) continue; ?>
					<option value="<?php echo $server_id; ?>"><?php echo $server_details['hostname']; ?></option>
					<?php } ?>
				</select>
			</td>
		</tr>
		</tbody>
		</table>
	</td>
</tr>
</tbody>
</table>

<p align="center"><input type="submit" class="btn btn-primary" name="submit" onclick="$('#selectedservers *').attr('selected','selected')" value="<?php echo $LANG['savechanges']; ?>" /></p>

</form>