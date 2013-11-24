{include file='page/rpc_pagestart.tpl'}
		<div id='assignment'>

			{include file='page/assignment_actions.tpl'}

			<div id='assignment-header'>
				<h3 id='assign-title'>{$assignment.title|escape}</h3>
				<div id='assignment-details'>
					<h4>Details</h4>
					<p id='assign-description' class='assign-description'>
						{$assignment.description|escape|nl2br}
					</p>
					<ol class='field-list'>
						<li>
							<span class='assignment-label'>Class:</span> {if $assignment.class}{$assignment.class|escape}{else}Unspecified{/if}
						</li>
						{if $assignment.type == "assignment" || $assignment.type == "link"}
						<li>
							<span class='assignment-label'>Starts:</span> {$assignment.start_date|date_format:$application.long_date}&nbsp;&nbsp;&nbsp;
							<span class='assignment-label'>Due:</span> <span id="assign-{$assignment.id}_due">{$assignment.due_date|date_format:$application.long_date}&nbsp;&nbsp;&nbsp;</span>
						</li>
						<li>
							{if $assignment.days_left >= 1}You have <span id="assign-{$assignment.id}_daysleft" class='special'>{$assignment.days_left}</span>
								{if $assignment.days_left == 1}day
								{else}days
								{/if}
								 left to complete this assignment!
							{else}
							This assignment is past due.
							{/if}
						</li>
						{/if}

						{* Display only if the owning user is viewing *}
						{if ($user && $user.id == $assignment.author && $assignment.type == "assignment") || $assignment.type == "link"}
						<li>
							<em>{if $assignment.send_reminders == true}
							You are receiving email reminders for this assignment&#39;s milestones.
							{else}
							You are not receiving email reminders for this assignment&#39;s milestones.
							{/if}</em>
						</li>
						{if $assignment.type == "assignment"}
						<li>
							<em>{if $assignment.is_shared == true}
							You have allowed others to view this assignment (except for my notes)
							(Link: <a href="{$assignment.url}">{$assignment.url}</a>)
							{else}
							You have not allowed others to view this assignment.
							{/if}</em>
						</li>
						{/if}
						{/if}

						{if $assignment.type == "template"}
						<li>
							<em>This template {if $assignment.is_published}is{else}has not been{/if} published for use.</em>
						</li>
						{/if}
				</div>
			</div>
			<div id='assignment-steps'>
				<ol id='stepListDnd' class='steps-list'>
				{foreach from=$assignment.steps item=step}
					{include file='page/step_view.tpl'}
				{/foreach}
				</ol>
			</div>

			{include file='page/assignment_actions.tpl'}

		</div>

{include file='page/rpc_pageend.tpl'}
