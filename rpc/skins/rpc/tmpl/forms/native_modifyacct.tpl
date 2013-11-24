{include file='page/rpc_pagestart.tpl'}
		<div id='modifyacct-form'>
			{if isset($acct_error)}
			<div class='errormsg'>{$acct_error}</div>
			{/if}
			{if isset($acct_success)}
			<div id='acct-success' class='successmsg'>{$acct_success}</div>
			{/if}
			{if $user && $user.id}
			<div id='modifyacct-settings' class='acct-widget'>
				<h3> Change my account settings </h3>
				<form id='form-modifyacct-settings' method='post' action='{$acct_handler}'>
					<fieldset id='fieldset-modifyacct-form'>
						<legend> Change my account settings: </legend>
						<p class='instructions'>
							<span class='assignment-label fixed-label'></span>
							<span class='required'>*</span> indicates a required field
						</p>
						<input type='hidden' name='transid' value='{$transid}' />
						<ol class='field-list'>
							<li>
								<label class='assignment-label fixed-label' for='modifyacct-name'>Name:</label>
								<input type='text' class='modifyacct-text' id='modifyacct-name' name='name' value='{$user.name}' title='Enter your name (optional)'/>
							</li>
							<li class='{if isset($resubmit.email)}resubmit{/if}'>
								<label class='assignment-label fixed-label' for='modifyacct-user'><span class='required'>*</span> Email address:</label>
								<input type='text' class='modifyacct-text' id='modifyacct-user' maxlength='320' name='email' title='Enter your email address (requried)' value='{$user.email}' {if $user.is_superuser}readonly='readonly'{/if} />
								{if $user.is_superuser} <span class='field-instructions'>(Superuser cannot change email address)</span>{/if}
							</li>
							<li class='{if isset($resubmit.usertype)}resubmit{/if}'>
								<label class='assignment-label fixed-label' for='modifyacct-usertype'><span class='required'>*</span> Account type:</label>
								<ol class='field-list radio-list' id='modifyacct-usertype' title='Choose your account type (required)'>
									<li>
										<span class='assignment-label fixed-label label-spacer'></span>
										<input type='radio' id='modifyacct-usertype-student' name='usertype' value='STUDENT' {if $user.type == "STUDENT"}checked='checked'{/if} />
										<label class='radio-label' for='modifyacct-usertype-student'>I am a student</label>
									</li>
									<li>
										<span class='assignment-label fixed-label label-spacer'></span>
										<input type='radio' id='modifyacct-usertype-teacher' name='usertype' value='TEACHER' {if $user.type == "TEACHER"}checked='checked'{/if} />
										<label class='radio-label' for='modifyacct-usertype-teacher'>I am a teacher / instructor</label>
									</li>
								</ol>
							</li>
							<li>
								<span class='assignment-label fixed-label label-spacer'></span>
								<input type='submit' class='submit' id='modifyacct-submit' value='Save settings' />
							</li>
						</ol>
					</fieldset>
				</form>
			</div>
			{* Native authentication users can also set their passwords *}
			{if $auth_plugin == 'native'}{include file='forms/native_changepw.tpl'}{/if}
			{* Option to delete account *}
			{* Superusers can't do this! *}
			{if $user.is_superuser != 1}
			<div id='acct-delete' class='acct-widget'>
				<h3> Delete my account </h3>
				<p class='instructions'>
					You may delete your account and all your saved assignments.
				</p>
				<form id='form-deleteacct' method='post' action='{$acct_handler}'>
					<fieldset id='fieldset-deleteacct'>
						<legend> Delete my account </legend>
						<input type='hidden' name='transid' value='{$transid}' />
						<ol class='field-list'>
							<li>
								<span class='assignment-label fixed-label label-spacer'></span>
								<input type='checkbox' id='deleteacct-confirm' name='delete-confirm' title='Confirm account deletion' onclick='toggleAcctConfirmDelete(this.checked);' />
								<label class='radio-label' for='deleteacct-confirm'>Delete my account and all saved assignments.  I understand assignments cannot be recovered.</label>
							</li>
							<li>
								<span class='assignment-label fixed-label label-spacer'></span>
								<input type='submit' class='submit' id='deleteacct-submit' value='Delete account' disabled='disabled' />
							</li>
						</ol>
					</fieldset>
				</form>
			</div>
			{/if}
			{/if}
		</div>
{include file='page/rpc_pageend.tpl'}
