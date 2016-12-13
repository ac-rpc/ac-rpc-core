			{* Actions shown for accounts, depending on what page is currently active *}
			<div class='account-options'>
				<ul>
					<li><span class='a-acct-home'><a href='{$application.fixed_web_path}'>Home</a></span></li>
					{if not isset($user.username)}
					{if $acct_action != 'newacct' && $auth_plugin === 'native'}
					<li><span class='a-acct-create'><a href='{$application.fixed_web_path}account?acct=newacct'>Create account</a></span></li>
					{/if}
					{if $acct_action != 'resetpw'}
					<li><span class='a-acct-forgot-pass'><a href='{$application.fixed_web_path}account?acct=resetpw'>Forgot password</a></span></li>
					{/if}
					{* Login link if user not logged in... *}
					<li><span class='a-acct-login'><a href='{$login_handler}?acct=login'>Login</a></span></li>
					{* Logout link if user logged in... *}
					{else}
					{if $user.is_administrator == 1 or $user.is_superuser == 1}
					<li><span class='a-acct-admin'><a href='{$application.fixed_web_path}admin' title='Change site settings'>Administration</a></span> </li>
					{/if}
					<li><span class='a-acct-settings'><a href='{$application.fixed_web_path}account' title='Change my account settings'>My Settings</a></span> </li>
					<li><span class='a-acct-logout'><a href='{$application.fixed_web_path}account?acct=logout' title='Logout of the {$application.short_name}'>Logout</a></span></li>
					{/if}
				</ul>
			</div>
