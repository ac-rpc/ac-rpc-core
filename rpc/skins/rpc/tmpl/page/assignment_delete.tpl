{include file='page/rpc_pagestart.tpl'}
		<div id='assignment'>
			<div class='main-widget'>
				<div id='assignment-header'>
					<h3>Delete {$assignment.type}</h3>
					<p id='assignment-desc' class='assignment-description'>
						{$assignment.description|escape|nl2br}
					</p>
					<div class='question'>
						Are you sure you want to permanently delete the {$assignment.type}: <span class='special'>{$assignment.title|escape}</span>?
						{if $assignment.type == "template"}
						<p class='errormsg'>
							<strong>Warning:</strong> Deleting this template will make it unavailable to all users!<br />
							Once deleted, it cannot be recovered.
						</p>
						{/if}
						{include file='forms/assignment_delete.tpl'}
						<br />
						<em>No thanks,</em> <a href='{$assignment.url}'>return to the {$assignment.type}</a>
						or <a href='{$application.relative_web_path}'>return to the list</a>
					</div>
				</div>
			</div>
		</div>

{include file='page/rpc_pageend.tpl'}
