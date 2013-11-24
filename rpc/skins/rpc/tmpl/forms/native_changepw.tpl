			<div id='modifyacct-password' class='acct-widget'>
				<h3> Change my password </h3>
				<form id='form-modifyacct-pass' method='post' action='{$login_handler}'>
					<fieldset id='fieldset-modifyacct-pass-form'>
						<legend> Change my password: </legend>
						<p class='instructions'>
							<span class='assignment-label fixed-label'></span>
							<span class='required'>*</span> indicates a required field
						</p>
						<input type='hidden' name='transid' value='{$transid}' />
						<ol class='field-list'>
							<li class='{if isset($resubmit.password)}resubmit{/if}'>
								<label class='assignment-label fixed-label' for='modifyacct-oldpass'><span class='required'>*</span> Enter old password:</label>
								<input type='password' class='modifyacct-text' id='modifyacct-oldpass' maxlength='256' name='oldpassword' title='Enter your old password' />
							</li>
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
								<input type='submit' class='submit' id='modifyacct-pass-submit' value='Change password' />
							</li>
						</ol>
					</fieldset>
				</form>
			</div>
