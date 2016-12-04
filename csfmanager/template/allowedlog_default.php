<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<form method="post" action="<?php echo $modulelink; ?>&pagename=allowedlog<?php echo $action_response['data']['start'] ? "&start={$action_response['data']['start']}" : ''; ?>">

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
        <td class="fieldlabel"><?php echo $instance->lang('ip'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['search']['ip']; ?>" name="search[ip]" /></td>
        <td class="fieldlabel"><?php echo $instance->lang('reason'); ?></td>
        <td class="fieldarea"><input type="text" value="<?php echo $action_response['data']['search']['reason']; ?>" size="50" name="search[reason]" /></td>
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
                <th><?php echo $instance->lang('ip'); ?></th>
                <th><?php echo $instance->lang('time'); ?></th>
                <th><?php echo $instance->lang('expirationdate'); ?></th>
                <th><?php echo $instance->lang('reason'); ?></th>
                <th width="20"></th>
        </tr>
        <?php foreach($action_response['data']['list'] as $allowed_ip) { ?>
        <tr>
                <td>
                        <?php if($allowed_ip['firstname']) { ?>
                        <a href="clientssummary.php?userid=<?php echo $allowed_ip['clientid']; ?>"><?php echo $allowed_ip['firstname'] . ' ' . $allowed_ip['lastname']; ?></a>
                        <?php } else { ?>
                        <?php echo sprintf($instance->lang('clientdeleted'), $allowed_ip['clientid']); ?>
                        <?php } ?>
                </td>
                <td><?php echo $allowed_ip['server_name']; ?></td>
                <td><a href="http://whois.domaintools.com/<?php echo $allowed_ip['ip']; ?>" target="_blank"><?php echo $allowed_ip['ip']; ?></a></td>
                <td><?php echo $allowed_ip['time']; ?></td>
                <td><?php echo $allowed_ip['expiration']; ?></td>
                <td><?php echo $allowed_ip['reason']; ?></td>
                <td>
                        <a title="<?php echo $instance->lang('removeip'); ?>" onclick="if(!confirm('<?php echo $instance->lang('sureremove'); ?>')) { return false; }" href="<?php echo $modulelink; ?>&page=allowedlog<?php echo $action_response['data']['start'] ? "&start={$action_response['data']['start']}" : ''; ?><?php echo $action_response['data']['search_url'] ? "&{$action_response['data']['search_url']}" : ''; ?>&action=delete&id=<?php echo $allowed_ip['id']; ?>">
                                <img width="16" height="16" border="0" alt="" src="images/delete.gif" />
                        </a>
                </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
</div>

<div class="csf-pagination">
	<?php echo csfmanager::csfPagination($action_response['data']['start'], $action_response['data']['limit'], $action_response['data']['total'], "{$modulelink}&pagename=allowedlog" . ($action_response['data']['search_url'] ? "&{$action_response['data']['search_url']}" : '')); ?>
</div>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>