<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<form action="<?php echo $modulelink; ?>&pagename=firewall&view=manage" method="post">

	<div style="padding: 20px; text-align: center;">

		<h1 style="margin: 0;">Please select a server to manage.</h1>
		<p></p>
		<select name="server_id" class="form-control select-inline" style="vertical-align: middle;">
			<?php foreach($action_response['data']['servers'] as $server_id => $server_details) { ?>
			<?php if((!$server_details['password'] && !$server_details['accesshash']) || (!$server_details['hostname'] || !$server_details['ipaddress']) || !$server_details['username'] || $server_details['type'] != 'cpanel') continue; ?>
			<option value="<?php echo $server_details['id']; ?>"><?php echo $server_details['name']; ?></option>
			<?php } ?>
		</select>
		<input type="submit" name="submit" class="btn btn-primary" value="Select Server" />
	</div>
</form>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>