{include file='page/rpc_pagestart.tpl'}
		<div id='assignment'>
			<div class='main-widget'>
				<div id='assignment-header'>
					<h3>Save a copy of this {$assignment.type}</h3>
					<p id='assignment-desc' class='assignment-description'>
						{$assignment.description|escape|nl2br}
					</p>
					<div class='question'>
						Do you want to save your own copy of the {$assignment.type}: <span class='special'>{$assignment.title|escape}</span>? 
						{include file='forms/assignment_copy.tpl'}
						<br />
						<em>No thanks,</em> <a href='{$assignment.url}'>return to the {$assignment.type}</a>
						or <a href='{$application.relative_web_path}'>return to the list</a>
					</div>
				</div>
			</div>
		</div>

{include file='page/rpc_pageend.tpl'}
