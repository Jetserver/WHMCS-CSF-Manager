{include file="$template/includes/tablelist.tpl" tableName="ServicesList" filterColumn="3"}

<script type="text/javascript">
jQuery(document).ready(function() {

	var table = $('#tableServicesList').DataTable();
	tableEmails.order([0, 'asc'], [3, 'asc']);
	table.draw();
});
</script>
<div class="table-container clearfix">
	<table id="tableServicesList" class="table table-list">
        <thead>
	<tr>
		<th>{$LANG.orderproduct}</th>
                <th class="responsive-edit-button" style="display: none;"></th>
	</tr>
	</thead>
	<tbody>
	{foreach item=service from=$services}
	<tr onclick="clickableSafeRedirect(event, '{$smarty.server.PHP_SELF}?m=csfmanager&amp;id={$service.id}', false)">
		<td>{$service.group} - {$service.product}{if $service.domain}<br /><a href="http://{$service.domain}" target="_blank">{$service.domain}</a>{/if}</td>
		<td style="display: none;"></td>
	</tr>
	{/foreach}
	</tbody>
	</table>
</div>