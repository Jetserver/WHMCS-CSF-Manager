<?php

if(!defined('CSFMANAGER')) die("This file cannot be accessed directly");

$servers = array();

$sql = "SELECT *
	FROM tblservers
	WHERE id IN({$config['servers']})
	ORDER BY name ASC";
$result = mysql_query($sql);

while($server_details = mysql_fetch_assoc($result))
{
	$servers[$server_details['id']] = array_merge($server_details, array('password' => decrypt($server_details['password'], $cc_encryption_hash)));
}
mysql_free_result($result);

$action = $_REQUEST['action'];

switch($action)
{
	default:
?>

<div style="padding: 20px; text-align: center;">

	<form action="<?php echo $modulelink; ?>&page=broadcast&action=selectservers" method="post">

	<h1 style="margin: 0;"><?php echo $LANG['selecttmpserver']; ?></h1>
	<p><?php echo $LANG['selecttmpserverdesc']; ?></p>

	<select class="form-control select-inline" style="vertical-align: middle;" name="templateserver">
		<?php foreach($servers as $server_id => $server_details) { ?>
		<?php if($server_details['selected']) continue; ?>
		<option value="<?php echo $server_id; ?>"><?php echo $server_details['name']; ?></option>
		<?php } ?>
	</select>
	<input type="submit" class="btn btn-primary" name="continue" name="submit" value="<?php echo $LANG['continue']; ?>" />
	<br />
	<input type="checkbox" name="dontusevalues" value="1" /> <?php echo $LANG['cleantemp']; ?>

	</form>
</div>


<?php
	break;

	case 'selectservers':

		$templateserver = intval($_REQUEST['templateserver']);
		$dontusevalues = isset($_REQUEST['dontusevalues']) ? 1 : 0;

		if(isset($servers[$templateserver]))
		{
?>

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

<h1 style="text-align: center;"><?php echo $LANG['selectserversupdate']; ?></h1>

<form action="<?php echo $modulelink; ?>&page=broadcast&action=setconfig" method="post">

<table style="width: 100%;">
<tbody>
<tr>
	<td style="text-align: right;">
		<select style="width:200px;" id="serverslist" multiple="multiple" size="10">
			<?php foreach($servers as $server_id => $server_details) { ?>
			<?php if($server_details['selected']) continue; ?>
			<option value="<?php echo $server_id; ?>"><?php echo $server_details['hostname']; ?></option>
			<?php } ?>
		</select>
	</td>
	<td align="center" style="width: 100px;">
		<input type="button" class="btn btn-sm" value="<?php echo $LANG['add']; ?> »" id="serveradd"><br><br>
		<input type="button" class="btn btn-sm" value="« <?php echo $LANG['remove']; ?>" id="serverrem">
	</td>
	<td style="text-align: left;">
		<select style="width:200px;" name="selectedservers[]" id="selectedservers" multiple="multiple" size="10">
		</select>
	</td>
</tr>
</tbody>
</table>

<div style="text-align: center">

	<input type="submit" class="btn btn-primary" class="btn btn-primary" name="submit" value="<?php echo $LANG['continue']; ?>" />
	<div class="preparingicon" style="display: none; padding: 10px 0; font-size: 14px; font-weight: bold;">
		<img src="../modules/addons/csfmanager/images/loader.gif" style="vertical-align: middle;" alt="" /> <?php echo $LANG['preparingconf']; ?>
	</div>
</div>

<input type="hidden" name="templateserver" value="<?php echo $templateserver; ?>" />
<?php if($dontusevalues) { ?><input type="hidden" name="dontusevalues" value="1" /><?php } ?>

</form>

<?php
		}
		else
		{
			$errors[] = $LANG['notemplateserverselected'];
		}
?>

<?php if(sizeof($errors)) { ?>
<div class="errorbox">
        <strong><span class="title"><?php echo $LANG['error']; ?></span></strong>
        <br />
        <?php echo implode('<br />', $errors); ?>
</div>
<?php } ?>

<?php
	break;

	case 'setconfig':

		$selected_servers = $_POST['selectedservers'];
		$templateserver = intval($_REQUEST['templateserver']);
		$dontusevalues = isset($_REQUEST['dontusevalues']) ? 1 : 0;

		if(sizeof($selected_servers))
		{
			if(isset($servers[$templateserver]))
			{
				$response = checkCsfAlive($servers[$templateserver]);

				if($response['success'] && $response['version'])
				{
					$formversion = $response['version'];
					$cgifile = $response['cgifile'];

					$cpanel = new csfmanager_cpanel;

					$password = $servers[$templateserver]['password'] ? $servers[$templateserver]['password'] : $servers[$templateserver]['accesshash'];
					$password_type = $servers[$templateserver]['password'] ? 'plain' : 'hash';

					$cpanel->setServer($servers[$templateserver]['hostname'], $servers[$templateserver]['username'], $password, $password_type);

					$response = $cpanel->request($cgifile, array(
						'action'	=> 'conf',
					));

					if($response['success'])
					{
						$html = str_get_dom($response['output']);

						$configForm = '';

						foreach($html('.virtualpage') as $div)
						{
							foreach($div('input') as $input)
							{
								$input->name = "configVars[{$input->name}]";
								if($dontusevalues) $input->value = '**USE-CURRENT**';
								if(isset($input->size) && intval($input->size) < 20) $input->size = '20';

								unset($input->onkeyup, $input->onfocus, $input->disabled);
							}

							$configForm .= $div->html();
						} 

?>
<h1><?php echo $LANG['changemulticonf']; ?></h1>

<div class="infobox">
        <strong><span class="title"><?php echo $LANG['info']; ?></span></strong>
        <br />
        <?php echo $LANG['changemulticonfdesc']; ?>
</div>

<fieldset>
	<form action="<?php echo $modulelink; ?>&page=broadcast&action=apply" method="post">

		<div style="margin-bottom: 20px;"><?php echo $configForm; ?></div>

		<div style="text-align: center;"><input type="submit" class="btn btn-primary" value="<?php echo $LANG['broadcastchanges']; ?>" /></div>

		<input type="hidden" name="formversion" value="<?php echo $formversion; ?>" />
		<?php foreach($selected_servers as $server_id) { ?>
		<input type="hidden" name="selectedservers[]" value="<?php echo $server_id; ?>" />
		<?php } ?>
	</form>
</fieldset>
<?php

					}
					else
					{
						$errors[] = $response['message'];
					}
				}
				else
				{
					if(!$response['success']) $errors[] = $response['message'];
					if(!$response['version']) $errors[] = $LANG['noversionwasfound'];
				}
			}
			else
			{
				$errors[] = $LANG['notemplateserverselected'];
			}
		}
		else
		{
			$errors[] = $LANG['noserversselected'];
		}
?>

<?php if(sizeof($errors)) { ?>
<div class="errorbox">
        <strong><span class="title"><?php echo $LANG['error']; ?></span></strong>
        <br />
        <?php echo implode('<br />', $errors); ?>
</div>
<?php } ?>

<?php
	break;

	case 'apply':

		$selected_servers = $_POST['selectedservers'];
		$config_vars = $_POST['configVars'];
		$form_version = $_POST['formversion'];
		$new_vars = array();

		foreach($config_vars as $key => $value)
		{
			if(trim($value) != '**USE-CURRENT**') $new_vars[$key] = $value;
		}

?>
<script>
var newVars = <?php echo json_encode($new_vars); ?>;
var servers = <?php echo json_encode($selected_servers); ?>;
var formVersion = '<?php echo $form_version; ?>';
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

<?php foreach($selected_servers as $i => $server_id) { ?>
<div id="server<?php echo $server_id; ?>" style="margin-bottom: 5px; text-align: center;">
	<img src="../modules/addons/csfmanager/images/waiting-icon.png" style="vertical-align: middle;" alt="" />
	<strong><?php echo $servers[$server_id]['name']; ?></strong>
</div>
<?php } ?>

<div style="text-align: center; margin-top: 15px;">
	<input class="abortBtn btn btn-default" type="submit" name="abort" onclick="return false;" value="<?php echo $LANG['abort']; ?>" />
	<input class="finishBtn btn btn-primary" style="display: none;" type="submit" onclick="window.location='<?php echo $modulelink; ?>&page=broadcast'" value="<?php echo $LANG['finish']; ?>" />
</div>

<?php
	break;

	case 'send':

		$server_id = $_REQUEST['server_id'];
		$new_vars = $_REQUEST['new_vars'];
		$form_version = $_REQUEST['formversion'];

		if(!is_array($new_vars) || !sizeof($new_vars))
		{
			echo json_encode(array(
				'success'	=> true,
				'message'	=> $LANG['nochanges'],
				'new_vars'	=> $new_vars,
			));
			exit;
		}
		
		$response = checkCsfAlive($servers[$server_id]);

		if($response['success'] && $response['version'])
		{
			if($form_version == $response['version'])
			{
				$cgifile = $response['cgifile'];

				$cpanel = new csfmanager_cpanel;

				$password = $servers[$server_id]['password'] ? $servers[$server_id]['password'] : $servers[$server_id]['accesshash'];
				$password_type = $servers[$server_id]['password'] ? 'plain' : 'hash';

				$cpanel->setServer($servers[$server_id]['hostname'], $servers[$server_id]['username'], $password, $password_type);

				$response = $cpanel->request($cgifile, array(
					'action'	=> 'conf',
				));

				if($response['success'])
				{
					$oldVars = array();

					$html = str_get_dom($response['output']);

					foreach($html('.virtualpage') as $div)
					{
						foreach($div('input') as $input)
						{
							$oldVars[$input->name] = $input->value;
						}
					} 

					$configVars = array_merge($oldVars, $new_vars);

					$response = $cpanel->request($cgifile, array_merge(array(
						'action'	=> 'saveconf',
					), $configVars));

					if($response['success'])
					{
						$response = $cpanel->request($cgifile, array(
							'action'	=> 'restartboth',
						));

						if($response['success'])
						{
							echo json_encode(array(
								'success'	=> true,
								'message'	=> $LANG['updatedsuccessfully'],
								'new_vars'	=> $new_vars,
							));
						}
						else
						{
							echo json_encode(array(
								'success'	=> false,
								'message'	=> $response['message'],
								'new_vars'	=> $new_vars,
							));
						}
					}
					else
					{
						echo json_encode(array(
							'success'	=> false,
							'message'	=> $response['message'],
							'new_vars'	=> $new_vars,
						));
					}
				}
				else
				{
					echo json_encode(array(
						'success'	=> false,
						'message'	=> $response['message'],
						'new_vars'	=> $new_vars,
					));
				}
			}
			else
			{
				echo json_encode(array(
					'success'	=> false,
					'message'	=> sprintf($LANG['versionmismatch'], $form_version, $response['version']),
					'new_vars'	=> $new_vars,
				));
			}
		}
		else
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> !$response['success'] ? $response['message'] : $LANG['noversionwasfound'],
				'new_vars'	=> $new_vars,
			));
		}

	exit;
}
?>