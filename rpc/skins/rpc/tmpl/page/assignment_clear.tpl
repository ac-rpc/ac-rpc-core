{include file='page/rpc_pagestart.tpl'}
		<div id='assignment'>
			<div class='main-widget'>
				<div id='assignment-header'>
					<h3>Start over</h3>
					<p id='assignment-desc' class='assignment-description'>
						{$assignment.description|escape|nl2br}
					</p>
					<div class='question'>
						Are you sure you want to start over?  This assignment will be lost.
						{include file='forms/assignment_clear.tpl'}
						<br />
						<em>Nevermind,</em> <a href='{$application.relative_web_path}'>return to the {$assignment.type}</a>
					</div>
				</div>
			</div>
		</div>

{include file='page/rpc_pageend.tpl'}
