
			<div id='admin-privileged-users' class='admin-widget'>
				<h3>All Current Privileged Users</h3>
				{if count($privileged_users) > 0}
				<table id='table-privileged-users'>
					<col class='privileged-userlist-info' width='30%' />
					<col class='privileged-userlist-info' width='30%' />
					<col width='10%' />
					<col width='10%' />
					<col width='10%' />
					<col width='10%' />
					<thead id='table-privileged-users-head'>
						<tr>
							<th>Username</th>
							<th>Name</th>
							<th>Superuser</th>
							<th>Administrator</th>
							<th>Publisher</th>
							<th>Revoke All</th>
						</tr>
					</thead>
					<tbody id='table-privileged-users-body'>
						{foreach key=key item=item from=$privileged_users}
						<tr class='{cycle values="tr-odd,tr-even"}' id='tr-privileged-user-{$item.id}'>
							<td>{$key}</td>
							<td>{$item.name}</td>
							<td class='radio-col'>{if $item.is_superuser == true}Yes{else}No{/if}</td>
							<td class='radio-col'><input type='radio' name='{$item.id}-privs' value='{$item.id}' onclick='this.blur();' onchange='rpcAdmin.grantAdministrator(this.value);' title='Grant administrator privilege' {if $item.is_administrator == true}checked='checked'{/if} {if $item.is_superuser == true or $user.username == $key}disabled='disabled'{/if}/></td>
							<td class='radio-col'><input type='radio' name='{$item.id}-privs' value='{$item.id}' onclick='this.blur();' onchange='rpcAdmin.grantPublisher(this.value);' title='Grant publisher privilege' {if $item.is_publisher == true}checked='checked'{/if} {if $item.is_superuser == true or $user.username == $key}disabled='disabled'{/if}/></td>
							<td class='radio-col'><input type='radio' name='{$item.id}-privs' value='{$item.id}' onclick='this.blur();' onchange='rpcAdmin.revoke(this.value);' title='Revoke all privileges' {if $item.is_superuser == true or $user.username == $key}disabled='disabled'{/if}/></td>
						</tr>
						{/foreach}
					</tbody>
				</table>
				{else}
				<span class='errormsg'>No privileged users are defined.</span>
				{/if}
			</div>
			<div id='admin-perms-message' class='successmsg' style='display: none;'></div>
