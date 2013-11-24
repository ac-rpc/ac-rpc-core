
		<div id='edit-utils' style='display: none;'>
			<form id='frm-assign-handler' action='{$application.fixed_web_path}handlers/assignment.php' method='post'>
				<fieldset>
				<input type='hidden' id='assign-handler-transid' name='transid' />
				<input type='hidden' id='assign-handler-id' name='id' />
				<input type='hidden' id='assign-handler-action' name='action' />
				<input type='hidden' id='assign-handler-val' name='val' />
				<input type='hidden' id='assign-handler-type' name='type' />
				</fieldset>
			</form>
			<form id='frm-step-handler' action='{$application.fixed_web_path}handlers/step.php' method='post'>
				<fieldset>
				<input type='hidden' id='step-handler-transid' name='transid' />
				<input type='hidden' id='step-handler-id' name='id' />
				<input type='hidden' id='step-handler-action' name='action' />
				<input type='hidden' id='step-handler-val_1' name='val_1' />
				<input type='hidden' id='step-handler-val_2' name='val_2' />
				</fieldset>
			</form>
			<div id='dialog-content'><div id='dialog-inner'></div><button class='dijitCloseButton' type='button' onclick='dijit.byId("stepChangeDialog").hide();'>OK</button></div>
			<div id='dialog-confirm-content'>
				<div id='dialog-confirm-inner'>Are you sure you want to delete this step?</div>
				<button class='dijitCloseButton' type='button' onclick=''>Yes, delete it</button>
				<button class='dijitCloseButton' type='button' onclick='dijit.byId("delConfirmDialog").hide();'>Cancel</button>
			</div>
			<script type='text/javascript'>
				var rpc_assignParams = {literal}{}{/literal};
				rpc_assignParams.transId = "{$transid}";
				rpc_assignParams.userType = "{$user.type}";
				rpc_assignParams.objectType = "{$assignment.type}";
				rpc_assignParams.editMode = "{$assignment.default_edit_mode}";
				rpc_assignParams.id = {$assignment.id};
				rpc_assignParams.title = '{$assignment.title|escape}';
				rpc_assignParams.className = "{$assignment.class|escape}";

				{if $assignment.type == "assignment" || $assignment.type == "link"}
				rpc_assignParams.startDate = '{$assignment.start_date|date_format:"%Y%m%d"}';
				rpc_assignParams.dueDate = '{$assignment.due_date|date_format:"%Y%m%d"}';
				rpc_assignParams.daysLeft = {$assignment.days_left};
				rpc_assignParams.sendReminders = {if $assignment.send_reminders == 1}true{else}false{/if};
				{/if}

				{if $assignment.type == "assignment"}
				rpc_assignParams.isShared = {if $assignment.is_shared == 1}true{else}false{/if};
				{/if}

				{if $assignment.type == "template"}
				rpc_assignParams.authorName = '{$assignment.author_name}';
				rpc_assignParams.lastEditName = '{$assignment.lastedit_name}';
				rpc_assignParams.published = {$assignment.is_published};
				{/if}

				var rpc_dateShortFmt = "M/d/yyyy";
			</script>
		</div>

