
				{* Save a link to another user's assignment *}
				{if !empty($assignment.id)}
					<form class='inline' id='frm-assignment-link' method='post' action='{$application.relative_web_path}handlers/assignment.php'>
						<input type='hidden' name='transid' id='assign-link-transid' value='{$transid}' />
						<input type='hidden' name='type' id='assign-link-type_{$assignment.id}' value='{$assignment.type}' />
						<input type='hidden' name='action' id='assign-link-action_{$assignment.id}' value='6' />
						{* id is empty for guest assignments *}
						<input type='hidden' name='id' id='assign-link-id_{$assignment.id}' value='{$assignment.id}' />
						<input type='hidden' name='val' id='assign-link-val_{$assignment.id}' value='' />
						<input type='submit' name='submit' id='assign-link-submit_{$assignment.id}' value="Yes, add it to my assignments list" title="Add this assignment to my list" />
					</form>
				{/if}
