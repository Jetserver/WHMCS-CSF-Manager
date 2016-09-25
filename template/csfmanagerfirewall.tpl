{if ! $errors}

	<table class="table table-striped table-framed data clientareatable" width="100%">
	<thead>
	<tr class="clientareatableheading">
		<th>{$ADDONLANG.firewalldetails}</th>
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><strong>{$ADDONLANG.server}:</strong></td>
		<td>{$server}</td>
	</tr>
	<tr>
		<td><strong>{$ADDONLANG.firewallstatus}:</strong></td>
		<td>{if $status}{$status}{else}{$ADDONLANG.nostatus}{/if}</td>
	</tr>
	<tr>
		<td><strong>{$ADDONLANG.openedportsall}:</strong></td>
		<td>{if $open_ports}{$open_ports}{else}{$ADDONLANG.noports}{/if}</td>
	</tr>
	<tr>
		<td><strong>{$ADDONLANG.blockedcountries}:</strong></td>
		<td>
			{foreach from=$denied_countries item=country}
			<img src="{$country.flag}" title="{$country.name}" alt="{$country.name}" style="vertical-align: middle;" />
			{foreachelse}
			{$ADDONLANG.noblockedcountries}
			{/foreach}
		</td>
	</tr>
	<tr>
		<td><strong>{$ADDONLANG.openedportscountries}:</strong></td>
		<td>
			<strong>{$ADDONLANG.ports}:</strong> {if $allowed_countries_ports}{$allowed_countries_ports}{else}{$ADDONLANG.noports}{/if}<br />
			<strong>{$ADDONLANG.countries}:</strong> 
			{foreach from=$allowed_countries item=country}
			<img src="{$country.flag}" title="{$country.name}" alt="{$country.name}" style="vertical-align: middle;" />
			{foreachelse}
			{$ADDONLANG.nocountries}
			{/foreach}
		</td>
	</tr>
	</tbody>
	</table>
{/if}
