<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<form method="post" action="<?php echo $modulelink; ?>&pagename=generatekey&action=create">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
        <td width="15%" class="fieldlabel"><?php echo $instance->lang('recipientname'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['generate']['recipient']; ?>" size="25" name="generate[recipient]" /></td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $instance->lang('recipientemail'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['generate']['email']; ?>" size="25" name="generate[email]" /></td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $instance->lang('client'); ?></td>
        <td class="fieldarea">
		<?php echo $instance->lang('clientid'); ?>:
        	<input type="text" value="<?php echo $action_response['data']['generate']['clientid']; ?>" size="5" name="generate[clientid]" />
		<?php echo $instance->lang('or'); ?>
                <select name="generate[client]" class="form-control select-inline">
                        <option value="">- <?php echo $instance->lang('selectclient'); ?> -</option>
                        <?php foreach($action_response['data']['clients'] as $client) { ?>
                        <option <?php if($action_response['data']['generate']['client'] == $client['id']) { ?>selected="selected" <?php } ?>value="<?php echo $client['id']; ?>"><?php echo $client['firstname']; ?> <?php echo $client['lastname']; ?> (<?php echo $client['domain']; ?>)</option>
                        <?php } ?>
                </select>
	</td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $instance->lang('server'); ?></td>
        <td class="fieldarea">
                <select name="generate[server]" class="form-control select-inline">
                        <option value="">- <?php echo $instance->lang('selectserver'); ?> -</option>
                        <?php foreach($action_response['data']['servers'] as $server) { ?>
                        <option <?php if($action_response['data']['generate']['server'] == $server['id']) { ?>selected="selected" <?php } ?>value="<?php echo $server['id']; ?>"><?php echo $server['name']; ?></option>
                        <?php } ?>
                </select>
        </td>
</tbody>
</table>

<img width="1" height="5" src="images/spacer.gif"><br>

<div align="center">
        <input type="submit" name="submit" class="btn btn-primary" value="<?php echo $instance->lang('createkey'); ?>" />
</div>
</form>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>