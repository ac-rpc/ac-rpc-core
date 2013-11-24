
			<div class='edit-actions'>
				<ul>
					{if !($user && $user.id)}<li><span class='instructions'>Login to save your own copy of this assignment</span></li>{/if}
					{if $assignment.is_temporary}<li><a href='{$application.relative_web_path}?action=clear'>Start over</a></li>{/if}
					{if $assignment.valid_actions}
						{foreach from=$assignment.valid_actions item=action}
						{if $action == 'parent'}<li><a href='{$assignment.parent_url}' target="_blank" title="View original (opens in a new window)">View original {$assignment.parent_type}</a></li>{/if}
						{* if $action == ''}<li><a href='{$assignment.url}'>View {$assignment.type}</a></li>{/if *}
						{* Most actions not relevant if user isn't logged in *}
						{if $user && $user.id}
							{if $action == 'edit'}<li><a href='{$assignment.url_edit}'>Edit {$assignment.type}</a></li>{/if}
							{if $action == 'delete'}<li><a href='{$assignment.url_delete}'>Delete {$assignment.type}</a></li>{/if}
							{if $action == 'copy'}<li><a href='{$assignment.url_copy}'>Save my own copy of this {$assignment.type}</a></li>{/if}
							{if $action == 'link'}<li><a href='{$assignment.url_link}'>Save a link to this {$assignment.type}</a></li>{/if}
						{/if}
					{/foreach}
					{/if}
					<li><a href='{$application.relative_web_path}'>Home</a></li>
				</ul>
			</div>

