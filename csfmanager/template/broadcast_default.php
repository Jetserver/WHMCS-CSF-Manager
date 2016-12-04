<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<div style="padding: 20px; text-align: center;">

	<form action="<?php echo $modulelink; ?>&pagename=broadcast&view=selectservers" method="post">

	<h1 style="margin: 0;"><?php echo $instance->lang('selecttmpserver'); ?></h1>
	<p><?php echo $instance->lang('selecttmpserverdesc'); ?></p>

	<select class="form-control select-inline" style="vertical-align: middle;" name="templateserver">
		<?php foreach($action_response['data']['servers'] as $server_id => $server_details) { ?>
		<?php if($server_details['selected']) continue; ?>
		<option value="<?php echo $server_details['id']; ?>"><?php echo $server_details['name']; ?></option>
		<?php } ?>
	</select>
	<input type="submit" class="btn btn-primary" name="continue" name="submit" value="<?php echo $instance->lang('continue'); ?>" />
	<br />
	<input type="checkbox" name="dontusevalues" value="1" /> <?php echo $instance->lang('cleantemp'); ?>

	</form>
</div>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>