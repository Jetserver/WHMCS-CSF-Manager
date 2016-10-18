{include file="$template/includes/tablelist.tpl" tableName="Whitelisted" filterColumn="3"}

<script type="text/javascript">
jQuery(document).ready(function () {
	var tableWhitelisted = $('#tableWhitelisted').DataTable();
	tableWhitelisted.order([0, 'asc'], [3, 'asc']);
	tableWhitelisted.draw();
});
</script>

<div class="table-container clearfix">
	<table id="tableWhitelisted" class="table table-list">
	<thead>
	<tr>
		<th>{$ADDONLANG.ip}</th>
		<th>{$ADDONLANG.time}</th>
		<th>{$ADDONLANG.expiration}</th>
		<th>{$ADDONLANG.reason}</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
	{foreach item=allowedip from=$allowedips}
	<tr>
		<td>{$allowedip.ip}</td>
		<td>{$allowedip.time}</td>
		<td>{$allowedip.expiration}</td>
		<td>{$allowedip.reason}</td>
		<td>
			<a href="{$modulelink}&page={$page}&id={$pid}&remove={$allowedip.id}" class="btn btn-info">{$ADDONLANG.remove}</a>
		</td>
	</tr>
	{/foreach}
	</tbody>
	</table>
</div>