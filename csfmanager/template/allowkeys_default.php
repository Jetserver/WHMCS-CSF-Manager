<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<form method="post" action="<?php echo $modulelink; ?>&pagename=allowkeys<?php echo $action_response['data']['start'] ? "&start={$action_response['data']['start']}" : ''; ?>">

<table width="100%" cellspacing="2" cellpadding="3" border="0" class="form">
<tbody>
<tr>
        <td width="15%" class="fieldlabel"><?php echo $instance->lang('clientname'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['search']['clientname']; ?>" size="25" name="search[clientname]" /></td>
        <td class="fieldlabel"><?php echo $instance->lang('server'); ?></td>
        <td class="fieldarea">
                <select name="search[server]" class="form-control select-inline">
                        <option value="">- <?php echo $instance->lang('any'); ?> -</option>
                        <?php foreach($action_response['data']['servers'] as $server) { ?>
                        <option <?php if($action_response['data']['search']['server'] == $server['id']) { ?>selected="selected" <?php } ?>value="<?php echo $server['id']; ?>"><?php echo $server['name']; ?></option>
                        <?php } ?>
                </select>
        </td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $instance->lang('recipientname'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['search']['recipient']; ?>" size="25" name="search[recipient]" /></td>
        <td class="fieldlabel"><?php echo $instance->lang('recipientemail'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['search']['email']; ?>" size="25" name="search[email]" /></td>
</tr>
<tr>
        <td class="fieldlabel"><?php echo $instance->lang('key'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['search']['key']; ?>" size="40" name="search[key]" /></td>
        <td class="fieldlabel"><?php echo $instance->lang('status'); ?></td>
        <td class="fieldarea">
                <select name="search[status]" class="form-control select-inline">
                        <option value=""<?php if(!trim($action_response['data']['search']['status'])) { ?> selected="selected"<?php } ?>>- <?php echo $instance->lang('allkeys'); ?> -</option>
                        <option value="valid"<?php if(trim($action_response['data']['search']['status']) == 'valid') { ?> selected="selected"<?php } ?>><?php echo $instance->lang('activekeys'); ?></option>
                        <option value="invalid"<?php if(trim($action_response['data']['search']['status']) == 'invalid') { ?> selected="selected"<?php } ?>><?php echo $instance->lang('expiredkeys'); ?></option>
                </select>
        </td>
</tr>
</tbody>
</table>

<img width="1" height="5" src="images/spacer.gif"><br>

<div align="center">
        <input type="submit" class="btn btn-primary" value="<?php echo $instance->lang('search'); ?>" />
</div>
</form>

<?php echo csfmanager::csfOnpage($action_response['data']['total'], $action_response['data']['limit'], $action_response['data']['start']); ?>

<div class="tablebg">
        <table width="100%" cellspacing="1" cellpadding="3" border="0" class="datatable">
        <tbody>
        <tr>
                <th><?php echo $instance->lang('client'); ?></th>
                <th><?php echo $instance->lang('server'); ?></th>
                <th><?php echo $instance->lang('recipientname'); ?></th>
                <th><?php echo $instance->lang('recipientemail'); ?></th>
                <th><?php echo $instance->lang('key'); ?></th>
                <th><?php echo $instance->lang('expirationdate'); ?></th>
                <th><?php echo $instance->lang('remained'); ?></th>
                <th width="20"></th>
                <th width="20"></th>
        </tr>
        <?php foreach($action_response['data']['list'] as $allow_key) { ?>
        <tr>
                <td>
                        <?php if($allow_key['firstname']) { ?>
                        <a href="clientssummary.php?userid=<?php echo $allow_key['user_id']; ?>"><?php echo $allow_key['firstname'] . ' ' . $allow_key['lastname']; ?></a>
                        <?php } else { ?>
                        <?php echo sprintf($instance->lang('clientdeleted'), $allow_key['user_id']); ?>
                        <?php } ?>
                </td>
                <td><?php echo $allow_key['server_name']; ?></td>
                <td><?php echo $allow_key['key_recipient']; ?></td>
                <td><?php echo $allow_key['key_email']; ?></td>
                <td><a href="../index.php?m=csfmanager&action=allow&key=<?php echo $allow_key['key_hash']; ?>" onclick="if(!confirm('<?php echo $instance->lang('surekey'); ?>')) { return false; }" target="_blank"><?php echo $allow_key['key_hash']; ?></a></td>
	<?php if($allow_key['key_cancelled']) { ?>
                <td colspan="2" style="text-align: center !important;"><strong style="color: #CC0000;"><?php echo $instance->lang('cancelled'); ?></strong></td>
	<?php } else { ?>
                <td style="text-align: center !important;">
		<?php if($allow_key['key_expire'] > time()) { ?>
			<?php echo $allow_key['key_expire_date']; ?>
		<?php } else { ?>
			<strong style="color: #CC0000;"><?php echo $instance->lang('expired'); ?></strong>
		<?php } ?>
		</td>
                <td style="text-align: center !important;">
		<?php if($allow_key['key_clicks_remained'] > 0) { ?>
			<?php echo $allow_key['key_clicks_remained']; ?>
		<?php } else { ?>
			<strong style="color: #CC0000;"><?php echo $instance->lang('fullyused'); ?></strong>
		<?php } ?>
		</td>
	<?php } ?>
                <td>
			<?php if(!$allow_key['key_cancelled'] && $allow_key['key_expire'] > time() && $allow_key['key_clicks_remained'] > 0) { ?>
                        <a title="<?php echo $instance->lang('resendkey'); ?>" href="<?php echo $modulelink; ?>&pagename=allowkeys<?php echo $action_response['data']['start'] ? "&start={$action_response['data']['start']}" : ''; ?><?php echo $action_response['data']['search_url'] ? "&{$action_response['data']['search_url']}" : ''; ?>&action=resend&id=<?php echo $allow_key['key_id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="images/icons/resendemail.png" />
                        </a>
			<?php } ?>
                </td>
                <td>
			<?php if(!$allow_key['key_cancelled'] && $allow_key['key_expire'] > time() && $allow_key['key_clicks_remained'] > 0) { ?>
                        <a title="<?php echo $instance->lang('cancelkey'); ?>" onclick="if(!confirm('<?php echo $instance->lang['surerecancelkey']; ?>')) { return false; }" href="<?php echo $modulelink; ?>&pagename=allowkeys<?php echo $action_response['data']['start'] ? "&start={$action_response['data']['start']}" : ''; ?><?php echo $action_response['data']['search_url'] ? "&{$action_response['data']['search_url']}" : ''; ?>&action=cancel&id=<?php echo $allow_key['key_id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="images/delete.gif" />
                        </a>
			<?php } elseif($allow_key['key_cancelled'] && $allow_key['key_expire'] > time() && $allow_key['key_clicks_remained'] > 0) { ?>
                        <a title="<?php echo $instance->lang('reactivatekey'); ?>" onclick="if(!confirm('<?php echo $instance->lang['surerereactivatekey']; ?>')) { return false; }" href="<?php echo $modulelink; ?>&pagename=allowkeys<?php echo $action_response['data']['start'] ? "&start={$action_response['data']['start']}" : ''; ?><?php echo $action_response['data']['search_url'] ? "&{$action_response['data']['search_url']}" : ''; ?>&action=reactivate&id=<?php echo $allow_key['key_id']; ?>">
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
	<?php echo csfmanager::csfPagination($action_response['data']['start'], $action_response['data']['limit'], $action_response['data']['total'], "{$modulelink}&pagename=allowkeys" . ($action_response['data']['search_url'] ? "&{$action_response['data']['search_url']}" : '')); ?>
</div>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>