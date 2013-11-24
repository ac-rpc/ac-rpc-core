
			<div id='assignment-header'>
				<h3 id='assign-title' title="Click title to rename">{$assignment.title|escape}</h3>
				<p class='instructions'>(Click title to rename)</p>
				<div id='assignment-details'>
					<h4>Details</h4>
					<p id='assign-description' class='assign-description'>
						{$assignment.description|escape|nl2br}
					</p>
					<ol class='field-list'>
						<li>
							<span class='assignment-label'>Class:</span> <span id='assign-classname'>{if $assignment.class}{$assignment.class|escape}{else}Unspecified{/if}</span>
						</li>
						{if $assignment.type == "assignment"}
						{* Regular assignment UI components *}
						<li>
							<label class='assignment-label required' for="assign-start">Starts:</label>
							<input class="rpc-DateTextBox" type="text" name="start" id="assign-start" value="{$assignment.start_date|date_format:$application.short_date}" title="Date this assignment is due (MM/DD/YYYY)" />
							<input class="cal-button" id="assign-start-calbutton" style='background: url({$application.skin_path}/res/cal.png);' type='button' onclick='dijit.byId("assign-start").toggleDropDown();' title='Click to select a starting date' alt='Calendar icon' /> 
							<label class='assignment-label required' for="assign-due">Due:</label>
							<input class="rpc-DateTextBox" type="text" name="due" id="assign-due" value="{$assignment.due_date|date_format:$application.short_date}" title="Date this assignment is due (MM/DD/YYYY)" />
							<input class="cal-button" id="assign-due-calbutton" style='background: url({$application.skin_path}/res/cal.png);' type='button' onclick='dijit.byId("assign-due").toggleDropDown();' title='Click to select a due date' alt='Calendar icon' /> 
							<span class='assignment-label'>Days Remaining:</span> <span id="assign-daysleft" class='special'>{if $assignment.days_left >= 1}{$assignment.days_left}{else}0{/if}</span>
						</li>
						<li>
							<input type='checkbox' id="assign-advanced" {if $assignment.default_edit_mode == "ADVANCED"}checked="checked"{/if} title='Check this box to enable advanced editing options' />
							<label class='assignment-label' for='assign-advanced' title='Check this box to enable advanced editing options'>Enable advanced editing options</label>
						</li>
						<li>
							<input type='checkbox' id="assign-public" name="public" {if $assignment.is_shared == true}checked="checked"{/if} title='Check this box to permit others to view this assignment with the supplied URL' />
							<label class='assignment-label' for="assign-public" title='Check this box to permit others to view this assignment with the supplied URL'>Others may view this assignment (except for my notes)</label> 
							<span id='assignment-sharelink' class='instructions{if $assignment.is_shared != true} invisible{/if}'>Link: <a href="{$assignment.url}">{$assignment.url}</a></span>
						</li>
						{/if}

						{if $assignment.type == "assignment" || $assignment.type == "link"}
						<li>
							<input type='checkbox' id="assign-remind" name="remind" {if $assignment.send_reminders == true}checked="checked"{/if} title='Check this box to receive email reminders of each step milestone' />
							<label class='assignment-label' for="assign-remind" title='Check this box to receive email reminders of each step milestone'>Send me email reminders for each step milestone</label>
						</li>
						{/if}

						{* Template UI components *}
						{if $assignment.type == "template"}
						<li>
							<span class='assignment-label'>Original author:</span> <span id='template-author-name'>{$assignment.author_name|escape}</span>
						</li>
						<li>
							<span class='assignment-label'>Last edited by:</span> <span id='template-lastedit-name'>{$assignment.lastedit_name|escape}</span>
						</li>
						<li>
							<input type='checkbox' id="template-published" name="published" {if $assignment.is_published == true}checked="checked"{/if} title='Publish template, making it available to all users' /> 
							<label class='assignment-label' for="template-published">Publish this template</label>
						</li>
						{/if}
					</ol>
				</div>
			</div>
