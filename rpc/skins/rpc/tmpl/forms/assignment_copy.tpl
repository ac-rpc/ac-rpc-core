
					<form class='inline' id='frm-assignment-duplicate' method='post' action='{$application.relative_web_path}handlers/assignment.php'>
						<input type='hidden' name='transid' id='assign-copy-transid' value='{$transid}' />
						<input type='hidden' name='type' id='assign-duplicate-type_{$assignment.id}' value='{$assignment.type}' />
						<input type='hidden' name='action' id='assign-duplicate-action_{$assignment.id}' value='1' />
						{* id is empty for guest assignments *}
						<input type='hidden' name='id' id='assign-duplicate-id_{$assignment.id}' value='{$assignment.id}' />
						<input type='hidden' name='val' id='assign-duplicate-val_{$assignment.id}' value='' />
						<input type='submit' name='submit' id='assign-duplicate-submit_{$assignment.id}' value="Yes, save my own copy" title="Save my own copy of this assignment." />
					</form>


