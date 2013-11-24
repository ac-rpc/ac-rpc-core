
					<form class='inline' id='frm-assignment-delete' method='post' action='{$application.relative_web_path}handlers/assignment.php'>
						<input type='hidden' name='transid' id='assign-delete-transid' value='{$transid}' />
						<input type='hidden' name='type' id='assign-delete-type' value='{$assignment.type}' />
						<input type='hidden' name='id' id='assign-delete-id_{$assignment.id}' value='{$assignment.id}' />
						<input type='hidden' name='action' id='assign-delete-action_{$assignment.id}' value='2' />
						<input type='hidden' name='val' id='assign-delete-val_{$assignment.id}'value='' />
						<input type='submit' name='submit' id='assign-delete-submit_{$assignment.id}' value='Yes, delete it!' title='Permanently delete this {$assignment.type}' />
					</form>

