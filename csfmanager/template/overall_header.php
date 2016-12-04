<?php if (!defined("JETCSFMANAGER")) die("This file cannot be accessed directly"); ?>
<?php if($new_version) { ?>
<div class="infobox">
	<strong><span class="title">Information</span></strong>
	<br />
	<?php echo sprintf($instance->lang('version_check'), $new_version); ?>
</div>
<?php } ?>

<?php if($global_success) { ?>
<div class="successbox">
	<strong><span class="title">Success!</span></strong><br />
	<?php echo $global_success; ?>
</div>
<?php } ?>

<?php if($global_error) { ?>
<div class="errorbox">
	<strong><span class="title">Error!</span></strong><br />
	<?php echo $global_error; ?>
</div>
<?php } ?>

<?php if(isset($action_response['errormessages']) && sizeof($action_response['errormessages'])) { ?>
<div class="errorbox">
	<strong><span class="title">Error!</span></strong><br />
	<?php echo implode("<br />", $action_response['errormessages']); ?>
</div>
<?php } ?>

<ul class="nav nav-tabs admin-tabs">
	<?php foreach($pages as $page_name) { ?>
	<?php $selected = ($page_name == $pagename) ? true : false; ?>
	<li class="<?php echo ($selected ? 'active' : ''); ?>"><a href="<?php echo $modulelink; ?>&pagename=<?php echo $page_name; ?>"><?php echo $LANG['page_' . strtolower($page_name)]; ?></a></li>
	<?php } ?>
</ul>

<div class="tab-content client-tabs">
	<div class="tab-pane active" style="position: relative;">
