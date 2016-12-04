<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<script type="text/javascript">
var newVars = <?php echo json_encode($action_response['data']['vars']); ?>;
var servers = <?php echo json_encode($action_response['data']['selectedservers']); ?>;
var formVersion = '<?php echo $action_response['data']['form_version']; ?>';
var aborting = 0;
var abortcount = 0;
var successcount = 0;
var failedcount = 0;

$(document).ready(function() {

	setTimeout(function() { 

		$('.broadcastMessage').html('<img src="../modules/addons/csfmanager/images/loader.gif" alt="" style="vertical-align: middle;" /> Broadcasting... (1 servers of ' + servers.length + ' updated)');
		updateServer(0); 

	}, 10000);

	$('input[name=abort]').click(function() { 

		$('.broadcastMessage').html('<img src="../modules/addons/csfmanager/images/loader.gif" alt="" style="vertical-align: middle;" /> Aborting... (0 servers of ' + servers.length + ' aborted)');
		aborting = 1;
	});
});

function updateServer(server_num)
{
	var server_id = servers[server_num];

	$('#server' + server_id + ' img').attr('src', '../modules/addons/csfmanager/images/loader.gif');

	if(aborting)
	{
		abortcount++;

		$('.broadcastMessage').html('<img src="../modules/addons/csfmanager/images/loader.gif" alt="" style="vertical-align: middle;" /> Aborting... (' + abortcount + ' servers of ' + servers.length + ' aborted)');

		$('#server' + server_id + ' img').attr('src', '../modules/addons/csfmanager/images/delete.png');
		$('#server' + server_id + ' strong').after(' - <span style="color: #CC0000;">Aborted</span>');

		if(servers[server_num+1] !== undefined)
		{
			setTimeout(function() { updateServer(server_num+1); }, 1000);
		}
		else
		{
			$('.broadcastMessage').html('Finished! - ' + abortcount + ' aborted, ' + failedcount + ' failed and ' + successcount + ' updated from total of ' + servers.length + ' servers');
			$('.finishBtn').css('display', 'inline');
			$('.abortBtn').css('display', 'none');
		}

		return;
	}
	else
	{
		$('.broadcastMessage').html('<img src="../modules/addons/csfmanager/images/loader.gif" alt="" style="vertical-align: middle;" /> Broadcasting... (' + (server_num+1) + ' servers of ' + servers.length + ' updated)');
	}

	$.ajax({
		url: '<?php echo $modulelink; ?>',
		data: {
			page: 'broadcast',
			action: 'send',
			ajax: '1',
			server_id: server_id,
			new_vars: newVars,
			formversion: formVersion
		},
		success: function(data) {

			data = eval('(' + data + ')');

			if(data.success)
			{
				successcount++;
			}
			else
			{
				failedcount++;
			}

			$('#server' + server_id + ' img').attr('src', '../modules/addons/csfmanager/images/' + (data.success ? 'success' : 'delete') + '.png');
			if(data.message) $('#server' + server_id + ' strong').after(' - <span style="color: #' + (data.success ? "23a700" : "CC0000") + ';">' + data.message + '</span>');

			if(servers[server_num+1] !== undefined)
			{
				setTimeout(function() { updateServer(server_num+1); }, 1000);
			}
			else
			{
				$('.broadcastMessage').html('Finished! - ' + abortcount + ' aborted, ' + failedcount + ' failed and ' + successcount + ' updated from total of ' + servers.length + ' servers');
				$('.finishBtn').css('display', 'inline');
				$('.abortBtn').css('display', 'none');
			}
		},
		error: function() {

			failedcount++;

			$('#server' + server_id + ' img').attr('src', '../modules/addons/csfmanager/images/delete.png');
			$('#server' + server_id + ' strong').after(' - <span style="color: #CC0000;">Ajax Error</span>');

			if(servers[server_num+1] !== undefined)
			{
				setTimeout(function() { updateServer(server_num+1); }, 1000);
			}
			else
			{
				$('.broadcastMessage').html('Finished! - ' + abortcount + ' aborted, ' + failedcount + ' failed and ' + successcount + ' updated from total of ' + servers.length + ' servers');
				$('.finishBtn').css('display', 'inline');
				$('.abortBtn').css('display', 'none');
			}
		}
	});
}
</script>

<div style="text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 15px;" class="broadcastMessage">
	<img src="../modules/addons/csfmanager/images/loader.gif" alt="" style="vertical-align: middle;" /> Broadcast will start in 10 seconds...
</div>

<?php foreach($action_response['data']['selectedservers'] as $i => $server_id) { ?>
<div id="server<?php echo $server_id; ?>" style="margin-bottom: 5px; text-align: center;">
	<img src="../modules/addons/csfmanager/images/waiting-icon.png" style="vertical-align: middle;" alt="" />
	<strong><?php echo $action_response['data']['servers'][$server_id]['name']; ?></strong>
</div>
<?php } ?>

<div style="text-align: center; margin-top: 15px;">
	<input class="abortBtn btn btn-default" type="submit" name="abort" onclick="return false;" value="<?php echo $instance->lang('abort'); ?>" />
	<input class="finishBtn btn btn-primary" style="display: none;" type="submit" onclick="window.location='<?php echo $modulelink; ?>&pagename=broadcast'" value="<?php echo $instance->lang('finish'); ?>" />
</div>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>