
					{* DO NOT change the id of these <li> nodes! They must remain as rpcstepid_<stepid> *}
					<li class='step-container' id='rpcstepid_{$step.id}'>
						<div class='step-head'>
							<div class='step-title-container'>
								<span class='step-head-content-view'>
									Step <span class='step-num'>{$step.position}</span>: 
								</span>
								<span class='step-title' id='step-title_{$step.id}'>{$step.title|escape}</span> 
							</div>
						</div>
						<div class='step-details'>
							{if $assignment.type == "assignment" || $assignment.type == "link"}
							<span class='assignment-label'>Complete this step by:</span> <span id="due_{$step.id}">{$step.due_date|date_format:$application.long_date}</span> 
							(<span class='assignment-label'>Days left to complete this step:</span> <span id="daysleft_{$step.id}" class="days-left">{if $step.days_left >= 1}{$step.days_left}{else}None{/if}</span> &hellip;
							<span class='assignment-label'>Time you should spend on this step:</span> <span id="percent_{$step.percent}" class="percent">{if $step.percent}{$step.percent}%{else}Not specified{/if}</span>)
							{/if}

							{if $assignment.type == "template"}
							<span class='assignment-label'>Percent time spent on this step:</span> {if $step.percent > 0}{$step.percent}%{else}unspecified{/if}
							{/if}
						</div>
						<div class='step-contents-container'>
							<div class='step-contents-container-student {if ($user == NULL && ($guest_usertype == "STUDENT" || $guest_usertype == NULL)) || ($user.type == "STUDENT" && empty($step.annotation))} step-wide{/if}'>
								<h4 class='stepdesc-head student'>{if $user.type == "TEACHER" || $guest_usertype == "TEACHER"}For students:{else}Instructions:{/if}</h4>
								<div class='step-contents student' id='stepdesc-student_{$step.id}'>
									{*
										Step annotation, description and teacher_description (HTML contents) are NOT escaped! The application should have already
										stripped unwanted or dangerous tags.
									 *}
									{$step.description}
								</div>
							</div>
							{* Students see the annotations window *}
							{if ($user.type == "STUDENT"  || $guest_usertype == "STUDENT") && !empty($step.annotation)}
							<div class='step-contents-container-annotation'>
								<h4 class='stepdesc-head annotation'>My notes:</h4>
								<div class='step-contents annotation' id='stepdesc-annotation_{$step.id}'>
									{$step.annotation}
								</div>
							</div>
							{/if}
							{if $user.type == "TEACHER" || $guest_usertype == "TEACHER"}
							<div class='step-contents-container-teacher'>
								<h4 class='stepdesc-head teacher'>For teachers:</h4>
								<div class='step-contents teacher' id='stepdesc-teacher_{$step.id}'>
									{$step.teacher_description}
								</div>
							</div>
							{/if}
							{* This is a hack for IE6 overflow when showing floated teacher contents *}
							{* Could also show some kind of footer information... *}
							<div class='step-contents-footer'></div>
						</div>
					</li>
