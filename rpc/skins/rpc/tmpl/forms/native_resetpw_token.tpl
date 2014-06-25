{include file='page/rpc_pagestart.tpl'}
			<div id='modifyacct-password' class='acct-widget'>
			{if isset($acct_error)}
			<div class='errormsg'>{$acct_error}</div>
			{/if}
			{if isset($acct_success)}
			<div class='successmsg'>{$acct_success}</div>
			{/if}
				<h3> Set a New Password </h3>
				<form id='form-modifyacct-pass' method='post' action='{$acct_handler}'>
					<fieldset id='fieldset-modifyacct-pass-form'>
						<legend> Set a new password: </legend>
						<p class='instructions'>
							<span class='assignment-label fixed-label'></span>
							<span class='required'>*</span> indicates a required field
						</p>
						<input type='hidden' name='transid' value='{$transid}' />
						<input type='hidden' name='token' value='{$token}' />
						<ol class='field-list'>
							<li class='{if isset($resubmit.password)}resubmit{/if}'>
								<label class='assignment-label fixed-label' for='modifyacct-pass'><span class='required'>*</span> Choose a new password:</label>
								<input type='password' class='modifyacct-text' id='modifyacct-pass' maxlength='256' name='password' title='Choose a password' />
								<span class='field-instructions'>(minimum 6 characters, with at least one number)</span>
							</li>
							<li class='{if isset($resubmit.password)}resubmit{/if}'>
								<label class='assignment-label fixed-label' for='modifyacct-pass-confirm'><span class='required'>*</span> Retype new password:</label>
								<input type='password' class='modifyacct-text' id='modifyacct-pass-confirm' maxlength='256' name='password-confirm' title='Retype password' />
							</li>
							<li>
								<span class='assignment-label fixed-label label-spacer'></span>
								<input type='submit' class='submit' id='modifyacct-pass-submit' value='Set password' />
							</li>
						</ol>
					</fieldset>
				</form>
			</div>
{include file='page/rpc_pageend.tpl'}
