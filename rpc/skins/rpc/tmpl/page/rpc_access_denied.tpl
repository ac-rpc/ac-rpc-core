{* Template to display when user does not have access to the current resource *}
{include file='page/rpc_pagestart.tpl'}
		<div class='errormsg'>
				You are not permitted to access this resource! <br />
				<a href='{$application.fixed_web_path}'>Return to the home page</a>.
		</div>
{include file='page/rpc_pageend.tpl'}
