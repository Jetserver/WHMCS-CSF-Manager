<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<h1><?php echo $instance->lang('changemulticonf'); ?></h1>

<div class="infobox">
        <strong><span class="title"><?php echo $instance->lang('info'); ?></span></strong>
        <br />
        <?php echo $instance->lang('changemulticonfdesc'); ?>
</div>

<fieldset>
	<form action="<?php echo $modulelink; ?>&pagename=broadcast&view=apply" method="post">

		<div style="margin-bottom: 20px;"><?php echo $action_response['data']['configForm']; ?></div>

		<div style="text-align: center;"><input type="submit" class="btn btn-primary" value="<?php echo $instance->lang('broadcastchanges'); ?>" /></div>

		<input type="hidden" name="formversion" value="<?php echo $action_response['data']['formversion']; ?>" />
		<?php foreach($action_response['data']['selectedservers'] as $server_id) { ?>
		<input type="hidden" name="selectedservers[]" value="<?php echo $server_id; ?>" />
		<?php } ?>
	</form>
</fieldset>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>