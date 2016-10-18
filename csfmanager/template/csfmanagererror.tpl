{if $errors}
<div class="alert alert-danger">
{foreach from=$errors item=error}
	<p>{$error}</p>
{/foreach}
</div>
{/if}

{if $successes}
<div class="alert alert-success">
	{foreach key=num item=success from=$successes}
	<p>{$success}</p>
	{/foreach}
</div>
{/if}