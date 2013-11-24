{include file='page/rpc_pagestart.tpl'}
		<div id='login-form' class='acct-widget'>
		<h3> {$application.long_name}: Login </h3>
			{if isset($login_error)}
			<div class='errormsg'>{$login_error}</div>
			{/if}
			<form id='form-login-form' method='post' action='{$login_handler}'>
				<fieldset id='fieldset-login-form'>
					<input type='hidden' name='transid' value='{$transid}' />
					<legend> Login: </legend>
					<ol class='field-list'>
						<li>
							<label class='assignment-label fixed-label' for='login-user'>Email address: </label>
							<input type='text' class='login-text' id='login-user' maxlength='320' name='username' title='Enter your email address' value='{if isset($login_user)}{$login_user}{/if}' />
						</li>
						<li>
							<label class='assignment-label fixed-label' for='login-pass'>Password: </label>
							<input type='password' class='login-text' id='login-pass' maxlength='256' name='password' title='Enter your password' />
						</li>
						<li title='Stay logged in until you click &quot;Logout&quot;'>
							<span class='assignment-label fixed-label label-spacer'></span>
							<input type='checkbox' id='login-persist' name='persist' />
							<label class='assignment-label' for='login-persist'>Keep me logged in</label>
						</li>
						<li>
							<span class='assignment-label fixed-label label-spacer'></span>
							<input type='submit' class='submit' id='login-submit' value='Login' />
						</li>
					</ol>
					<p>
						<span class='assignment-label fixed-label label-spacer'></span>
						<a href='{$application.fixed_web_path}account?acct=resetpw'>Forgot password</a> | 
						<a href='{$application.fixed_web_path}account?acct=newacct'>Create account</a>
					</p>
				</fieldset>
			</form>
			{literal}
			<script type='text/javascript'>
				dojo.addOnLoad(function(){
					dojo.byId("login-user").focus();
				});	
			</script>
			{/literal}
		</div>
{include file='page/rpc_pageend.tpl'}
