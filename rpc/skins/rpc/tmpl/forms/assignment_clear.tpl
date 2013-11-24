

					<form class='inline' id='frm-assignment-clear' method='post' action='{$application.relative_web_path}handlers/assignment.php'>
						<input type='hidden' name='transid' id='assign-clear-transid' value='{$transid}' />
						<input type='hidden' name='type' id='assign-clear-type_{$assignment.id}' value='{$assignment.type}' />
						<input type='hidden' name='action' id='assign-clear-action_{$assignment.id}' value='5' />
						{* id is empty for guest assignments *}
						<input type='hidden' name='id' id='assign-clear-id_{$assignment.id}' value='{$assignment.id}' />
						<input type='hidden' name='val' id='assign-clear-val_{$assignment.id}' value='' />
						<input type='submit' name='submit' id='assign-clear-submit_{$assignment.id}' value="Yes, start over" title="Start over with a new assignment" />
					</form>

