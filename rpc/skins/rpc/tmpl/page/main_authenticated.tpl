{include file='page/rpc_pagestart.tpl'}

	<script type='text/javascript' src='{$application.dojo_path}dojo/rpc-home.js'></script>
	<script type='text/javascript' src='{$application.relative_web_path}js/home.js'></script>
	<div id='welcome'>
		Welcome back, {$user.name}.
		{if $auth_plugin !== 'shibboleth' || $shib_mode === 'passive'}
			<br />
			<span class='not-me'>(<a href='{$application.fixed_web_path}account?acct=logout'>Click here if you're not {$user.name}</a>)</span>
		{/if}
	</div>
	<div class='main-widget' id='new-assignment'>
		<h3>Create a new assignment</h3>
		{include file='forms/assignment_new.tpl'}
		{include file='page/rpc_sidebar.tpl'}
		<div class='dummy'></div>
	</div>

	{include file='page/assignment_list_todo.tpl'}
	{include file='page/assignment_list_pending.tpl'}
	{include file='page/assignment_list_old.tpl'}

{include file='page/rpc_pageend.tpl'}
