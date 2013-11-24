<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title> {$application.long_name} </title>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
		<link rel='stylesheet' type='text/css' media='screen' href='{$application.dojo_path}dojo/resources/dojo.css' />
		<link rel='stylesheet' type='text/css' media='screen' href='{$application.dojo_path}dijit/themes/tundra/tundra.css' />
		<link rel='stylesheet' type='text/css' media='screen' href='{$application.skin_path}screen.css' />
		<link rel='stylesheet' type='text/css' media='print' href='{$application.skin_path}print.css' />
		<!--[if lt IE 7]>
			<style type='text/css' media='screen'>
				@import "{$application.skin_path}ie6.css";
			</style>
		<![endif]-->
		<script type='text/javascript' src='{$application.dojo_path}dojo/dojo.js'></script>
		<script type='text/javascript' src='{$application.relative_web_path}js/rpc.js'></script>
	</head>
	<body class='tundra'>
		<div class='accessibility'>
			<ul>
				<li><a href='#maincol'>Skip links</a></li>
			</ul>
		</div>
		<div id='content'>
			{include file='page/rpc_header.tpl'}
			{include file='page/rpc_nav.tpl'}
			<div id='maincol'>
				{if $general_error}<div class='errormsg' id='general-error'>{$general_error}</div>{/if}
				{if $general_success}<div class='successmsg' id='general-error'>{$general_success}</div>{/if}
