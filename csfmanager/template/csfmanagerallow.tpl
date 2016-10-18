{literal}
<script type="text/javascript">
$(document).ready(function() {
	$('img.allowreason').tooltip({ showURL: false, showBody: " :: " });
});

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

<form action="{$modulelink}&page={$page}&id={$pid}" method="post" onsubmit="return validateIp();">

<div class="row">
	<div class="col-sm-6">
		<div class="form-group">
			<label class="control-label" for="ip">{$ADDONLANG.ipaddress}</label>
			<input type="text" class="form-control" value="{$ip}" id="ip" name="ip" />
		</div>
	</div>
	<div class="col-sm-6 col-xs-12 pull-right">
		<div class="form-group">
			<label class="control-label" for="reason">{$ADDONLANG.allowreason}</label>
			<input type="text" class="form-control" value="{$reason}" id="reason" name="reason" />
		</div>
	</div>
</div>

<div class="form-group text-center">
	<input type="submit" value="{$ADDONLANG.tempallowbutton}" name="submit" class="btn btn-primary" />
	<input type="reset" value="Cancel" class="btn btn-default" />
</div>

</form>
