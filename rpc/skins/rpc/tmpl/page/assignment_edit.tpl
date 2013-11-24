{include file='page/rpc_pagestart.tpl'}

		<script type='text/javascript' src='{$application.dojo_path}dojo/rpc-assignment.js'></script>
		{* Must include assignment_edit_util.tpl, contains hidden forms and other important stuff *}
		{include file='util/assignment_edit_util.tpl'}
		<style type='text/css' src='{$application.dojo_path}dojo/resources/dnd.css' ></style>

		<div id='assignment'>
			<div class='edit-actions'>
				<ul>
					<li><a href='{$assignment.url}'>Done editing</a></li>
				</ul>
			</div>

			<noscript>
				<div class='errormsg'>
					<strong>Error:</strong> Editing assignments requires a JavaScript-enabled web browser!  Most features will be unavailable.
				</div>
			</noscript>
			{* Warning when editing a template *}
			{if $assignment.type == "template" && $assignment.is_published == 1}
			<div class='errormsg'>
				<strong>* Attention:</strong> You are editing published a template.  Any changes made will immediately affect all users! *
			</div>
			{/if}
			{* Warning message when editing an assignment derived from another assignment or template *}
			<div class='question'>
				Any changes you make will be saved immediately.
				{if ($assignment.parent_type == 'assignment' || $assignment.parent_type == 'template') && $assignment.type == "assignment"}
				<br />Attention: You are editing an assignment copied from another assignment or template!
				{/if}
			</div>

			{* Guts of the assignment edit actions... *}
			{include file='forms/assignment_edit.tpl'}

			{* Steps list *}
			<div id='assignment-steps'>
				{* stepListDnd will become a dojo.dnd.Source, and must be present! *}
				<ol id='stepListDnd' class='steps-list'>

				{foreach from=$assignment.steps item=step}
					{include file='page/step_edit.tpl'}
				{/foreach}

				</ol>
				{* Button to add step below last step *}
				<div  id='assignment-appendStep' class='right'>
					<label class='accessibility' for='assign-appendStep'>Add a step to this assignment</label>
					<button id='assign-appendStep' onclick='assign.getLastStep().addStepBelow();'>Add a step</button>
				</div>
			</div>
			<div class='edit-actions'>
				<ul>
					<li><a href='{$assignment.url}'>Done editing</a></li>
				</ul>
			</div>
		</div>
		<script type='text/javascript' src='{$application.relative_web_path}js/assignment.js'></script>

{include file='page/rpc_pageend.tpl'}
