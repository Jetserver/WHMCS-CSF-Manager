{if $package}
<div class="alert alert-info text-center">
        Managing Firewall for: <strong>{$package}</strong> ({$domain})
</div>
{/if}

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

<div id="jserrors" class="alert alert-danger" style="display: none;"></div>

{include file="$modulepath/clientarea/csfmanager$page.tpl" title="test"}
