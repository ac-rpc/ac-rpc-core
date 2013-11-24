			<div id='admin-searchusers' class='admin-widget'>
				<h3>Add Privileges</h3>
				<div id='admin-searchusers-form'>
					<form id='form-admin-searchusers' method='post' action='{$admin_handler}'>
						<fieldset id='fieldset-admin-searchusers'>
							<legend>Search for a user</legend>
							<ol class='field-list-inline'>
								<li>
									<label class='assignment-label fixed-label' for='admin-searchusers-username'>Search {if $auth_plugin == 'native'}email address{else}Username{/if}:</label>
									<input type='text' class='admin-text' id='admin-searchusers-username' value='{$username}' />
								</li>
								<li>
									<button type='button' class='submit' id='admin-searchusers-submit' name='admin-searchusers-submit' onclick='rpcAdmin.searchUser(); this.blur();'>Search</button>
								</li>
								<li>
									<button type='button' class='submit' id='admin-searchusers-clear' name='admin-searchusers-clear' onclick='rpcAdmin.clearSearch(); this.blur();'>Clear</button>
								</li>
							</ol>
							<div class='errormsg' id='admin-searchusers-result'></div>
						</fieldset>
					</form>
				</div>
			</div>
