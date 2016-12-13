{include file='page/rpc_pagestart.tpl'}
	<script type='text/javascript' src='{$application.dojo_path}dojo/rpc-home.js'></script>
	<script type='text/javascript' src='{$application.relative_web_path}js/home.js'></script>
	<p>
		If you already have an account, you may <a href='{$login_handler}?acct=login'>login</a>{if $auth_plugin === 'native'} or <a href='{$application.relative_web_path}account?acct=newacct'>create a new account</a>{/if}.
	</p>
	<p>
		Or, you can get started right away by creating an assignment from one of our templates:
	</p>
	<div class='main-widget' id='new-assignment'>
		<h3>Create a new assignment</h3>
		{include file='forms/assignment_new.tpl'}
		{include file='page/rpc_sidebar.tpl'}
		<div class='dummy'></div>
	</div>

{include file='page/rpc_pageend.tpl'}
