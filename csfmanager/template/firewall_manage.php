<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php include_once(JCSF_ROOT_PATH . "/template/overall_header.php"); ?>

<h2 style="position: absolute; top: 0; left: 0; background: #fff; margin: 10px 0; display: block; padding: 9px 19px; width: 100%;">
	Managing "<?php echo $action_response['data']['server_details']['name']; ?>"
</h2>
<iframe id="jbmFrame" src="<?php echo $action_response['data']['iframe_url'] . '&goto_uri=/cgi/configserver/csf.cgi'; ?>" style="border: 0 none; width: 100%; height: 55000px;"></iframe>

<?php include_once(JCSF_ROOT_PATH . "/template/overall_footer.php"); ?>