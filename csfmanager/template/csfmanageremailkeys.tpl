{include file="$template/includes/tablelist.tpl" tableName="Emails" filterColumn="3"}

<script type="text/javascript">
jQuery(document).ready(function () {
	var tableEmails = $('#tableEmails').DataTable();
	tableEmails.order([0, 'asc'], [3, 'asc']);
	tableEmails.draw();
});
</script>

<div class="table-container clearfix">
	<table id="tableEmails" class="table table-list">
	<thead>
	<tr>
		<th>{$ADDONLANG.recipient}</th>
		<th>{$ADDONLANG.expiration}</th>
		<th>{$ADDONLANG.remained_clicks}</th>
		<th>{$ADDONLANG.status}</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
	{foreach item=allowkey from=$allowkeys}
	<tr>
		<td>{$allowkey.key_recipient}<br />{$allowkey.key_email}</td>
		{if $allowkey.key_cancelled or $allowkey.key_clicks_remained lte 0 or $allowkey.key_expired}
		<td>-</td>
		<td>-</td>
		<td><span class="label status status-pending">{if $allowkey.key_cancelled}{$ADDONLANG.cancelled}{elseif $allowkey.key_expired}{$ADDONLANG.expired}{elseif $allowkey.key_clicks_remained <= 0}{$ADDONLANG.fullyused}{/if}</span></td>
		<td></td>
		{else}
		<td>{$allowkey.key_expire}</td>
		<td>{$allowkey.key_clicks_remained}</td>
		<td><span class="label status status-active">Active</span></td>
		<td>
			<a href="{$modulelink}&page={$page}&id={$pid}&cancel={$allowkey.key_id}" class="btn btn-info">{$ADDONLANG.cancel}</a>
			<a href="{$modulelink}&page={$page}&id={$pid}&resend={$allowkey.key_id}" class="btn btn-info">{$ADDONLANG.resend}</a>
		</td>
		{/if}
	</tr>
	{/foreach}
	</tbody>
	</table>
</div>