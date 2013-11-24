			<div id='admin-utils' style='display: none;'>
				<form id='frm-admin-handler' action='{$application.fixed_web_path}/handlers/admin.php' method='post'>
					<fieldset>
						<input type='hidden' id='admin-handler-transid' name='transid' value='{$transid}' />
						<input type='hidden' id='admin-handler-id' name='id' value='' />
						<input type='hidden' id='admin-handler-action' name='action' value='' />
						<input type='hidden' id='admin-handler-perm' name='perm' value='' />
					</fieldset>
				</form>
			</div>
