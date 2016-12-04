<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<script type="text/javascript">
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

<form action="<?php echo $modulelink; ?>&pagename=settings&action=save" method="post">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
	<td class="fieldlabel" style="width: 30%;"><?php echo $instance->lang('canviewfirewalltab'); ?></td>
	<td class="fieldarea">
		<label><input type="radio" value="1"<?php if($instance->getConfig('permission_firewall')) { ?> checked="checked"<?php } ?> name="config[permission_firewall]" /> Yes</label>
		<label><input type="radio" value="0"<?php if(!$instance->getConfig('permission_firewall')) { ?> checked="checked"<?php } ?> name="config[permission_firewall]" /> No</label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('canviewunblocktab'); ?></td>
	<td class="fieldarea">
		<label><input type="radio" value="1"<?php if($instance->getConfig('permission_unblock')) { ?> checked="checked"<?php } ?> name="config[permission_unblock]" /> Yes</label>
		<label><input type="radio" value="0"<?php if(!$instance->getConfig('permission_unblock')) { ?> checked="checked"<?php } ?> name="config[permission_unblock]" /> No</label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('canunblockclients'); ?></td>
	<td class="fieldarea">
		<label><input type="radio" value="1"<?php if($instance->getConfig('permission_aunblock')) { ?> checked="checked"<?php } ?> name="config[permission_aunblock]" /> Yes</label>
		<label><input type="radio" value="0"<?php if(!$instance->getConfig('permission_aunblock')) { ?> checked="checked"<?php } ?> name="config[permission_aunblock]" /> No</label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('canviewallowtab'); ?></td>
	<td class="fieldarea">
		<label><input type="radio" value="1"<?php if($instance->getConfig('permission_allow')) { ?> checked="checked"<?php } ?> name="config[permission_allow]" /> Yes</label>
		<label><input type="radio" value="0"<?php if(!$instance->getConfig('permission_allow')) { ?> checked="checked"<?php } ?> name="config[permission_allow]" /> No</label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('canviewallowemailtab'); ?></td>
	<td class="fieldarea">
		<label><input type="radio" value="1"<?php if($instance->getConfig('permission_allowemail')) { ?> checked="checked"<?php } ?> name="config[permission_allowemail]" /> Yes</label>
		<label><input type="radio" value="0"<?php if(!$instance->getConfig('permission_allowemail')) { ?> checked="checked"<?php } ?> name="config[permission_allowemail]" /> No</label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('brutecheck'); ?></td>
	<td class="fieldarea">
		<label><input type="radio" value="1"<?php if($instance->getConfig('checkbrute')) { ?> checked="checked"<?php } ?> name="config[checkbrute]" /> Yes</label>
		<label><input type="radio" value="0"<?php if(!$instance->getConfig('checkbrute')) { ?> checked="checked"<?php } ?> name="config[checkbrute]" /> No</label>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('allowlength'); ?></td>
	<td class="fieldarea">
		<input name="config[allowlength]" value="<?php echo $instance->getConfig('allowlength'); ?>" size="4" /> 

		<select name="config[allowlength_type]" class="form-control select-inline">
			<option<?php if($instance->getConfig('allowlength_type') == 'seconds') { ?> selected="selected"<?php } ?> value="seconds"><?php echo $instance->lang('seconds'); ?></option>
			<option<?php if($instance->getConfig('allowlength_type') == 'minutes') { ?> selected="selected"<?php } ?> value="minutes"><?php echo $instance->lang('minutes'); ?></option>
			<option<?php if($instance->getConfig('allowlength_type') == 'hours') { ?> selected="selected"<?php } ?> value="hours"><?php echo $instance->lang('hours'); ?></option>
			<option<?php if($instance->getConfig('allowlength_type') == 'days') { ?> selected="selected"<?php } ?> value="days"><?php echo $instance->lang('days'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td class="fieldlabel"><?php echo $instance->lang('servers'); ?></td>
	<td class="fieldarea">
		<table>
		<tbody>
		<tr>
			<td>
				<select style="width:200px;" id="serverslist" multiple="multiple" size="10">
					<?php foreach($action_response['data']['servers'] as $server_id => $server_details) { ?>
					<?php if($server_details['selected']) continue; ?>
					<option value="<?php echo $server_id; ?>"><?php echo $server_details['name']; ?></option>
					<?php } ?>
				</select>
			</td>
			<td align="center">
				<input type="button" value="<?php echo $instance->lang('add'); ?> »" id="serveradd" class="btn btn-xs" /><br><br>
				<input type="button" value="« <?php echo $instance->lang('remove'); ?>" id="serverrem" class="btn btn-xs" />
			</td>
			<td>
				<select style="width:200px;" name="selectedservers[]" id="selectedservers" multiple="multiple" size="10">
					<?php foreach($action_response['data']['servers'] as $server_id => $server_details) { ?>
					<?php if(!$server_details['selected']) continue; ?>
					<option value="<?php echo $server_id; ?>"><?php echo $server_details['name']; ?></option>
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

<p align="center">
	<input type="submit" class="btn btn-primary" name="submit" onclick="$('#selectedservers *').attr('selected','selected')" value="<?php echo $instance->lang('savechanges'); ?>" />
</p>

</form>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>