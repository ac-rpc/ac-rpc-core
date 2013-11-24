{include file='page/rpc_pagestart.tpl'}
		<div id='createacct-form' class='acct-widget'>
			<h3> {$application.long_name}: Create your account </h3>
			{if isset($acct_error)}
			<div class='errormsg'>{$acct_error}</div>
			{/if}
			{if not isset($acct_success)}
			<form id='form-createacct-form' method='post' action='{$login_handler}'>
				<fieldset id='fieldset-createacct-form'>
					<p class='instructions'>
						<span class='assignment-label fixed-label'></span>
						<span class='required'>*</span> indicates a required field
					</p>
					<input type='hidden' name='transid' value='{$transid}' />
					<legend> Create your account: </legend>
					<ol class='field-list'>
						<li>
							<label class='assignment-label fixed-label' for='createacct-name'>Name:</label>
							<input type='text' class='createacct-text' id='createacct-name' name='name' value='{$createacct_name}' title='Enter your name (optional)'/>
						</li>
						<li class='{if isset($resubmit.username)}resubmit{/if}'>
							<label class='assignment-label fixed-label' for='createacct-user'><span class='required'>*</span> Email address:</label>
							<input type='text' class='createacct-text' id='createacct-user' maxlength='320' name='username' title='Enter your email address (required)' value='{$createacct_username}' />
						</li>
						<li class='{if isset($resubmit.usertype)}resubmit{/if}'>
							<label class='assignment-label fixed-label' for='createacct-usertype'><span class='required'>*</span> Account type:</label>
							<ol class='field-list radio-list' id='createacct-usertype' title='Choose your account type (required)'>
								<li>
									<span class='assignment-label fixed-label label-spacer'></span>
									<input type='radio' id='createacct-usertype-student' name='usertype' value='STUDENT' {if $createacct_usertype == "STUDENT"}checked='checked'{/if} />
									<label for='createacct-usertype-student'>I am a student</label>
								</li>
								<li>
									<span class='assignment-label fixed-label label-spacer'></span>
									<input type='radio' id='createacct-usertype-teacher' name='usertype' value='TEACHER' {if $createacct_usertype == "TEACHER"}checked='checked'{/if}/>
									<label for='createacct-usertype-teacher'>I am a teacher/instructor</label>
								</li>
							</ol>
						</li>
						<li class='{if isset($resubmit.password)}resubmit{/if}'>
							<label class='assignment-label fixed-label' for='createacct-pass'><span class='required'>*</span> Choose a password:</label>
							<input type='password' class='createacct-text' id='createacct-pass' maxlength='256' name='password' title='Choose a password' />
								<span class='field-instructions'>(minimum 6 characters, with at least one number)</span>
						</li>
						<li class='{if isset($resubmit.password)}resubmit{/if}'>
							<label class='assignment-label fixed-label' for='createacct-pass-confirm'><span class='required'>*</span> Retype password:</label>
							<input type='password' class='createacct-text' id='createacct-pass-confirm' maxlength='256' name='password-confirm' title='Retype password' />
						</li>
						<li>
							<span class='assignment-label fixed-label label-spacer'></span>
							<input type='submit' class='submit' id='createacct-submit' value='Create account' />
						</li>
					</ol>
				</fieldset>
			</form>
			{else}
			<div id='acct-success' class='successmsg'>{$acct_success}</div>
			{/if}
		</div>
{include file='page/rpc_pageend.tpl'}
