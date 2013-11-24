{include file='page/rpc_pagestart.tpl'}
		<div id='acct-action' class='acct-widget'>
			<h3> {$application.long_name}: Reset Password </h3>
			{if isset($acct_error)}
			<div id='acct-errmsg' class='errormsg'>{$acct_error}</div>
			{/if}
			{if not isset($acct_success)}
			<p class='instructions'>
				Enter your email address below and a new randomly selected password will be emailed to you.
			</p>
			<form id='form-acct-resetpw' method='post' action='{$acct_handler}'>
				<fieldset id='fieldset-acct-resetpw'>
					<legend>Reset your password:</legend>
					<input type='hidden' name='transid' value='{$transid}' />
					<ol class='field-list'>
						<li>
							<label class='assignment-label fixed-label' for='resetpw-username'><span class='required'>*</span> Email address:</label>
							<input type='text' name='username' class='login-text' id='resetpw-username' title='Enter your email address'/>
						</li>
						<li>
							<span class='assignment-label fixed-label label-spacer'></span>
							<input type='submit' name='submit' value='Email me a new password' id='resetpw-submit' />
						</li>
					</ol>
				</fieldset>
			</form>
			{else}
			<div id='acct-success' class='successmsg'>{$acct_success}</div>
			{/if}
		</div>
{include file='page/rpc_pageend.tpl'}
