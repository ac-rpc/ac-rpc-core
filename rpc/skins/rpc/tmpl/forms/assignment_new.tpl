
	<div id="new-assignment-form-container">
		{include file='util/assignment_new_util.tpl'}
		<form id='frm-new-assign' action='handlers/create.php' method='post'>
			<fieldset id='fieldset-new-assign'>
				<legend> Create an assignment: </legend>
				<p class='instructions'>
					<span class='assignment-label fixed-label'></span>
					<span class='required'>*</span> indicates a required field
				</p>
				<input type='hidden' name='transid' value='{$transid}' />
				<ol class='field-list'>
					<li>
						<label class='assignment-label fixed-label' for='new-assign-title'><span class='required'>*</span> Assignment title:</label>
						<input type='text' class='wide' name='title' id='new-assign-title' title='Enter your assignment&#39;s title (required)' value='' />
					</li>
					<li>
						<label class='assignment-label fixed-label' for='new-assign-startdate'><span class='required'>*</span> Starts:</label> 
						<input type='text' name='start' class='tundra dijitDateTextBox' id='new-assign-startdate' value="{$smarty.now|date_format:'%Y-%m-%d'}" title='Assignment starting date (required)' />
						<input class="cal-button" id="assign-start-calbutton" style='background: url({$application.skin_path}/res/cal.png);' type='button' onclick='dijit.byId("new-assign-startdate").toggleDropDown();' title='Click to select a starting date' alt='Calendar icon' /> 
						<label class='assignment-label' for='new-assign-duedate'><span class='required'>*</span> Due:</label> 
						<input type='text' name='due' class='tundra dijitDateTextBox' id='new-assign-duedate' value="{$smarty.now|date_format:'%Y-%m-%d'}" title='Assignment due date (required)' />
						<input class="cal-button" id="assign-due-calbutton" style='background: url({$application.skin_path}/res/cal.png);' type='button' onclick='dijit.byId("new-assign-duedate").toggleDropDown();' title='Click to select a due date' alt='Calendar icon' /> 
						<noscript><p class='inline instructions'>(YYYY-MM-DD)</p></noscript>
					</li>
					<li>
						<label class='assignment-label fixed-label' for='new-assign-class'>&nbsp;&nbsp;Class:</label> 
						<input type='text' class='normal' name='class' id='new-assign-class' title='Enter the class or course this assignment is for (not required)' value='' />
					</li>
					{* Unauthenticated users get a teacher checkbox *}
					{if !$user.id}
					<li>
						<span class='assignment-label fixed-label'>&nbsp;</span>
						<input type='checkbox' name='isteacher' id='new-assign-isteacher' title='Include extra information for teachers and instructors' />
						<label class='assignment-label' for='new-assign-isteacher' title='Include extra information for teachers and instructors'>Include information for teachers and instructors</label>
					</li>
					{/if}

					{* Administrators/Publishers get additional option to create a new template *}
					{if $user.is_administrator == 1 or $user.is_publisher == 1}
					<li>
						<span class='assignment-label fixed-label'>&nbsp;</span>
						<input type='checkbox' name='astemplate' id='new-assign-astemplate' title='Create a new template' value='' onchange='RPCAssignForm.toggleTemplateFields();' />
						<label class='assignment-label' for='new-assign-astemplate'>Create as a template</label>
					</li>
					{/if}

				</ol>
				{if count($templates) == 0}
				<p class='errormsg'> No assignment templates are available. </p>
				{/if}
				<p class='instructions required center'>* Choose one of our assignment templates!</p>
				<ul id='template-list'>
					{foreach from=$templates item=template}
					<li class='template-choice {if $template.is_published == 0}template-unpublished{/if}' id='template-choice-container_{$template.id}'>
						<span class='template-info {if $template.is_published == 0}template-unpublished{/if}'>
							<input type='radio' name='template' class='template-choice-widget' id='template-choice_{$template.id}' value='{$template.id}' title='Choose this template' /> 
							<label class='template-title' for='template-choice_{$template.id}'>{$template.title|escape}</label>
							{if $user.is_administrator == 1 or $user.is_publisher == 1}
								{if $template.is_published == 0} <span class='info'>(Unpublished template)</span>{/if}
							{/if}
							{if $template.class}
							<span class='template-class'>(<span class='assignment-label'>Class:</span> {$template.class})</span>
							{/if}
						</span>
						<span class='template-actions'>
							<a href='{$template.url}' title='View this template'>View</a>
							{if $template.is_editable == 1}
							 | <a href='{$template.url_edit}' title='Edit this template'>Edit</a>
							 | <a href='{$template.url_delete}' title='Delete this template'>Delete</a>
							{/if}
						</span>
					</li>
					{/foreach}
					{* Authenticated users can create assignments from scratch *}
					{if $user != null}
					<li class='template-choice template-blank' id='template-choice-container_BLANK'>
						<span class='template-info'>
							<input type='radio' class='template-choice-widget' id='template-choice_BLANK' name='template' value='BLANK' title='Start with a blank assignment' /> 
							<label class='template-title' for='template-choice_BLANK'>Start from scratch with a blank assignment</label>
						</span>
					</li>
					{/if}

				</ul>
				<div class='center'> 
					<input type='submit' value='Create Assignment{if $user.is_administrator || $user.is_publisher} or Template{/if}' name='create' class='center' onclick='return RPCAssignForm.validate();' />
				</div>
			</fieldset>
		</form>
	</div>

