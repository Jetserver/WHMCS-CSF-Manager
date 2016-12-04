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

	$('.continue').click(function() {
		$('#selectedservers *').attr('selected','selected'); 
		$('.preparingicon').css('display', '');
		$(this).css('display', 'none');
	});
});
</script>

<h1 style="text-align: center;"><?php echo $instance->lang('selectserversupdate'); ?></h1>

<form action="<?php echo $modulelink; ?>&pagename=broadcast&view=setconfig" method="post">

<table style="width: 100%;">
<tbody>
<tr>
	<td style="text-align: right;">
		<select style="width:200px;" id="serverslist" multiple="multiple" size="10">
			<?php foreach($action_response['data']['servers'] as $server_id => $server_details) { ?>
			<?php if($server_details['selected']) continue; ?>
			<option value="<?php echo $server_details['id']; ?>"><?php echo $server_details['name']; ?></option>
			<?php } ?>
		</select>
	</td>
	<td align="center" style="width: 100px;">
		<input type="button" class="btn btn-sm" value="<?php echo $instance->lang('add'); ?> »" id="serveradd"><br><br>
		<input type="button" class="btn btn-sm" value="« <?php echo $instance->lang('remove'); ?>" id="serverrem">
	</td>
	<td style="text-align: left;">
		<select style="width:200px;" name="selectedservers[]" id="selectedservers" multiple="multiple" size="10">
		</select>
	</td>
</tr>
</tbody>
</table>

<div style="text-align: center">

	<input type="submit" class="btn btn-primary" class="btn btn-primary" name="submit" value="<?php echo $instance->lang('continue'); ?>" />
	<div class="preparingicon" style="display: none; padding: 10px 0; font-size: 14px; font-weight: bold;">
		<img src="../modules/addons/csfmanager/images/loader.gif" style="vertical-align: middle;" alt="" /> <?php echo $instance->lang('preparingconf'); ?>
	</div>
</div>

<input type="hidden" name="templateserver" value="<?php echo $action_response['data']['server_details']['id']; ?>" />
<?php if($action_response['data']['dontusevalues']) { ?><input type="hidden" name="dontusevalues" value="1" /><?php } ?>

</form>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>