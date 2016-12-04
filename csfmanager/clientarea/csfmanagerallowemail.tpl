<form action="{$modulelink}&page={$page}&id={$pid}" method="post">

<div class="row">
	<div class="col-sm-6">
		<div class="form-group">
			<label class="control-label" for="fullname">{$ADDONLANG.recipientname}</label>
			<input type="text" class="form-control" value="{$fullname}" id="fullname" name="fullname" />
		</div>
	</div>
	<div class="col-sm-6 col-xs-12 pull-right">
		<div class="form-group">
			<label class="control-label" for="email">{$ADDONLANG.recipientemail}</label>
			<input type="text" class="form-control" value="{$email}" id="email" name="email" />
		</div>
	</div>
</div>

<div class="form-group text-center">
	<input type="submit" value="{$ADDONLANG.sendemail}" name="submit" class="btn btn-primary" />
	<input type="reset" value="Cancel" class="btn btn-default" />
</div>

</form>