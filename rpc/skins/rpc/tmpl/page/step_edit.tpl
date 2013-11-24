					{* DO NOT change the id of these <li> nodes! They must remain as rpcstepid_<stepid> *}
					<li class='step-container {if $assignment.type != "link"}{/if} {if $assignment.default_edit_mode == "ADVANCED"}mode-advanced{/if}' id='rpcstepid_{$step.id}'>
						<div class='step-head'>
							<div class='step-actions-container'>
								<span class='step-head-content-edit dojoDndHandle' title='Drag and drop steps to rearrange (advanced editing only)'>
									Step <span class='step-num'>{counter}</span>: 
									<span class='instructions ctl-advanced'>(drag and drop to rearrange)</span>
								</span>
								{* Step actions not available for linked assignments *}
								{if $assignment.type !== "link"}
								<span class='step-actions'>
									<label class='accessibility' for='step-moveup_{$step.id}'>Move step up</label>
									<button class='mini-button ctl-advanced' id='step-moveup_{$step.id}' title='Move this step up' onclick='assign.getStep({$step.id}).moveUp();'><img src='{$application.skin_path}res/up.png' alt='Up icon'/></button> 
									<label class='accessibility' for='step-movedown_{$step.id}'>Move step down</label>
									<button class='mini-button ctl-advanced' id='step-movedown_{$step.id}' title='Move this step down' onclick='assign.getStep({$step.id}).moveDown();'><img src='{$application.skin_path}res/down.png' alt='Down icon'/></button>
									<label class='accessibility' for='step-remove_{$step.id}'>Remove this step</label>
									<button class='mini-button ctl-advanced' id='step-remove_{$step.id}' title='Remove this step' onclick='assign.getStep({$step.id}).remove();'><img src='{$application.skin_path}res/del.png' alt='Delete icon'/></button> 
									<label class='accessibility' for='step-addbelow_{$step.id}'>Insert step below</label>
									<button class='mini-button' id='step-addbelow_{$step.id}' title='Insert a new step after this one' onclick='assign.getStep({$step.id}).addStepBelow();'><img src='{$application.skin_path}res/add.png' alt='Insert step icon'/></button> 
								</span>
								{/if}
							</div>
							<div class='step-title-container'><span class='step-title' id='step-title_{$step.id}' title='Click to rename step (advanced editing only)'>{$step.title|escape}</span> <span class='instructions ctl-advanced'>(click title to edit)</span></div>
						</div>
						<div class='step-details'>
							{if $assignment.type == "assignment" || $assignment.type == "link"}
							{* Widgets for regular assignments *}
							<label class='assignment-label'>Complete this step by:</label> 
							{* Editable assignments get date widgets, linked assignments just get a text date *}
							{if $assignment.type == "assignment"}
							<input class="rpc-DateTextBox" type="text" name="{$step.id}_due" id="due_{$step.id}" value="{$step.due_date|date_format:'%Y%m%d'}" title="Due date for this step (MM/DD/YYYY)" />
							<input class="cal-button" type='button' style='background: url({$application.skin_path}/res/cal.png);' onclick='dijit.byId("due_{$step.id}").toggleDropDown();' title='Click to select a date' alt='Calendar icon' /> 
							{/if}
							{if $assignment.type == "link"}
							<span id="due_{$step.id}">{$step.due_date|date_format:$application.long_date}</span> 
							{/if}

							(<span class='assignment-label'>Days left to complete this step:</span> <span id="daysleft_{$step.id}" class="days-left">{if $step.days_left >= 1}{$step.days_left}{else}None{/if}</span> &hellip; 
							<span class='assignment-label'>Time you should spend on this step:</span> <span id="percent_{$step.percent}" class="percent">{if $step.percent}{$step.percent}%{else}Not specified{/if}</span>)
							{/if}

							{if $assignment.type == "template"}
							{* Widgets for assignment tempaltes *}
							<label class='assignment-label' for='percent_{$step.id}'>Percent of time spent on this step:</label> 
							<input type='text' class='short step-percent' name='{$step.id}_percent' id='percent_{$step.id}' value='{$step.percent}' />
							<button type='button' class='step-savepercent mini-button closelink' id='{$step.id}-step-confirmpercent' title='Click to set new percent' onclick='assign.getStep({$step.id}).updatePercent();'>Set</button>
							{/if}
						</div>
						<div class='step-contents-container'>
							<div class='step-contents-container-student {if $assignment.type == "template" && $user.type != "TEACHER"}step-wide{/if}'>{* Templates always get wide view (no annotations) *}
								{if $user.type == "TEACHER"}
								<h4 class='stepdesc-head student'>For students:</h4>
								{else}
								<h4 class='stepdesc-head student'>Instructions:</h4>
								{/if}
								<div class='step-contents student' id='stepdesc-student_{$step.id}'>
									{*
									 * Step annotation, description and teacher_description (HTML contents) are NOT escaped! The application should have already
									 * stripped unwanted or dangerous tags.
									 *}
									{$step.description}
								</div>
								<div class='editor-controls ctl-advanced'>
									<button id='s-open_{$step.id}' class='openlink' onclick='assign.getStep({$step.id}).launchEditor("s");' title='Edit this step&#39;s description'>Edit</button>
									<button id='s-close_{$step.id}' class='closelink' onclick='assign.getStep({$step.id}).closeEditor("s", true);' title='Save changes and close editor'>Save Changes</button>
									<button id='s-cancel_{$step.id}' class='closelink' onclick='assign.getStep({$step.id}).closeEditor("s", false);' title='Cancel editing and discard changes'>Cancel</button>
								</div>
							</div>
							{* Students see the annotations window on assignments only *}
							{if $user.type == "STUDENT" && ($assignment.type == "assignment" || $assignment.type == "link")}
							<div class='step-contents-container-annotation'>
								<h4 class='stepdesc-head student'>My notes:</h4>
								<div class='step-contents annotation' id='stepdesc-annotation_{$step.id}'>
									{if empty($step.annotation)}
									Type your notes and track your progress here&hellip; 
									{else}
									{$step.annotation}
									{/if}
								</div>
								<div class='editor-controls'>
									<button id='a-open_{$step.id}' class='openlink' onclick='assign.getStep({$step.id}).launchEditor("a");' title='Edit this step&#39;s notes'>Edit</button>
									<button id='a-close_{$step.id}' class='closelink' onclick='assign.getStep({$step.id}).closeEditor("a", true);' title='Save changes and close editor'>Save Changes</button>
									<button id='a-cancel_{$step.id}' class='closelink' onclick='assign.getStep({$step.id}).closeEditor("a", false);' title='Cancel editing and discard changes'>Cancel</button>
								</div>
							</div>
							{/if}
							{if $user.type == "TEACHER"}
							<div class='step-contents-container-teacher'>
								<h4 class='stepdesc-head teacher'>For teachers:</h4>
								<div class='step-contents teacher' id='stepdesc-teacher_{$step.id}'>
									{$step.teacher_description}
								</div>
								<div class='editor-controls'>
									<button id='t-open_{$step.id}' class='openlink' onclick='assign.getStep({$step.id}).launchEditor("t");' title='Edit this step&#39;s description for teachers'>Edit</button>
									<button id='t-close_{$step.id}' class='closelink' onclick='assign.getStep({$step.id}).closeEditor("t", true);' title='Save changes and close editor'>Save Changes</button>
									<button id='t-cancel_{$step.id}' class='closelink' onclick='assign.getStep({$step.id}).closeEditor("t", false);' title='Cancel editing and discard changes'>Cancel</button>
								</div>
							</div>
							{/if}
							{* This is a hack for IE6 overflow when showing floated teacher contents *}
							{* Could also show some kind of footer information... *}
							<div class='step-contents-footer'></div>
						</div>
					</li>
