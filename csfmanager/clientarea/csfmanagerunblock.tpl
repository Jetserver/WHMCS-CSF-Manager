{include file="$template/includes/tablelist.tpl" tableName="Unblock" filterColumn="3"}

<script type="text/javascript">
jQuery(document).ready(function () {
	var tableUnblock = $('#tableUnblock').DataTable();
	tableUnblock.order([0, 'asc'], [3, 'asc']);
	tableUnblock.draw();
});
</script>

{literal}
<script type="text/javascript">
function validateIp()
{
	var errors = document.getElementById('jserrors');

	errors.style.display = 'none';
	errors.innerHTML = '';

	var value = document.getElementById('ip').value;
	value = value.replace(' ', '');

	if(/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/g.test(value))
	{
		return true;
	}

	errors.innerHTML = '<p>{/literal}{$ADDONLANG.invalidip}{literal}</p>';
	errors.style.display = 'block';

	return false;
}
</script>
{/literal}

{if $blockedreasons_csf or $blockedreasons_logins or $blockedreasons_brutes}
<div class="alert alert-danger">
	<p>{$ADDONLANG.youripblocked|sprintf:$cip}</p>
</div>

<div class="table-container clearfix">
	<table id="tableUnblock" class="table table-list">
	<thead>
	<tr>
		<th>{$ADDONLANG.blockreason}</th>
		<th>{$ADDONLANG.blockdate}</th>
		<th>{$ADDONLANG.expirationdate}</th>
	</tr>
	</thead>
	<tbody>
	{if $blockedreasons_csf}
	{foreach key=num item=reason from=$blockedreasons_csf}
	<tr>
		<td dir="ltr">{$reason.Notes}</td>
		<td dir="ltr">{$reason.Date}</td>
		<td>{$ADDONLANG.never}</td>
	</tr>
	{/foreach}
	{/if}
	{if $blockedreasons_logins or $blockedreasons_brutes}
	{foreach key=num item=reason from=$blockedreasons_logins}
	<tr>
		<td dir="ltr">{$reason.Notes}</td>
		<td dir="ltr">{$reason.Date}</td>
		<td>{$ADDONLANG.never}</td>
	</tr>
	{/foreach}
	{foreach key=num item=reason from=$blockedreasons_brutes}
	<tr>
		<td dir="ltr">{$reason.Notes}</td>
		<td dir="ltr">{$reason.Date}</td>
		<td dir="ltr">{$reason.Expiration}</td>
	</tr>
	{/foreach}
	{/if}
	</tbody>
	</table>
</div>

<form action="{$modulelink}&page={$page}&id={$pid}&action=unblock" method="post">
<div class="form-group text-center">
	<input type="hidden" name="ip" value="{$cip}" />
	<input type="submit" value="{$ADDONLANG.unblock}" class="btn btn-primary" />
</div>
</form>
{else}
<div class="alert alert-success warning">
	<p>{$ADDONLANG.youripok|sprintf:$cip}</p>
</div>
{/if}

	{if $canrelease and $config.permission_aunblock}
	<form action="{$modulelink}&page={$page}&id={$pid}" method="post" onsubmit="return validateIp();">
		<div class="styled_title">
			<h3>{$ADDONLANG.clientsunblockgui}</h3>
		</div>

		<div id="jserrors" class="alert alert-danger" style="display: none;"></div>


		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<label class="control-label" for="ip">{$ADDONLANG.ipaddress}</label>
					<input type="text" class="form-control" value="{$ip}" id="ip" name="ip" />
				</div>
			</div>
		</div>

		<div class="form-group text-center">
			<input type="submit" value="{$ADDONLANG.checkipblock}" name="submit" class="btn btn-primary" />
			<input type="reset" value="Cancel" class="btn btn-default" />
		</div>
	</form>
	{/if}
