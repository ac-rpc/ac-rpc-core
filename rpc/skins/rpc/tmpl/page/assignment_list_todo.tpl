
	<div class='main-widget' id='to-do-list'>
		<h3>My assignment to-do list (past 7 days through next 7 days)</h3>
		{if count($todos) > 0}
		<table class='assignment-list'>
			<thead>
				<tr>
					<th>Step</th>
					<th>Complete step by</th>
					<th>Due</th>
					<th class='center'>Actions</th>
				</tr>
			</thead>
			<tbody>
				{* Display the assignments *}
				{foreach from=$todos item=todo}
				<tr class='assignment-brief-view {cycle values="tr-odd,tr-even"}'>
					<td><strong>Step {$todo.position}:</strong> {$todo.step|escape} (In assignment: <em>{$todo.assignment|escape} {if $todo.linkid !== NULL}<span class='special'>(linked assignment)</span>{/if}</em>)</td>
					<td>{$todo.due_date|date_format:$application.long_date}</td>
					<td class='left'>
					{if $todo.days_left == 0}
						Due Today!
					{elseif $todo.days_left == 1}
						Due Tomorrow!
					{elseif $todo.days_left == -1}
						Due yesterday
					{elseif $todo.days_left < -1}
						Due {math equation="abs(d)" d=$todo.days_left} days ago
					{else}
						Due in {$todo.days_left} days
					{/if}
					</td>
					<td class='center'><a href='{$todo.url}'>View</a></td>
				</tr>
				{/foreach}
			</tbody>
		</table>
		{else}
		<p class='info'>
			You don&#39;t have any assignment milestones due in the next 7 days.
		</p>
		{/if}
	</div>

