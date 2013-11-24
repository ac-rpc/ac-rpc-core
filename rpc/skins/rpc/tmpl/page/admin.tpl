{include file='page/rpc_pagestart.tpl'}
{include file='page/admin_list_privileged_users.tpl'}
{include file='forms/admin_searchuser.tpl'}
{include file='util/admin_util.tpl'}
<script type='text/javascript' src='{$application.dojo_path}dojo/rpc-admin.js'></script>
<script type='text/javascript' src='{$application.fixed_web_path}js/admin.js'></script>
<div id='version'>Version: {$application.version}-{$application.builddate}</div>
{include file='page/rpc_pageend.tpl'}
