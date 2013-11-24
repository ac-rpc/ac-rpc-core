
	<div class='main-widget' id='assignment-list-pending'>
		<h3>My active assignments</h3>
		{if count($assignments_pending) > 0}
		<table class='assignment-list'>
			<col width='30%' />
			<col width='15%' />
			<col width='15%' />
			<col width='15%' />
			<col width='5%' />
			<col width='20%' />
			<thead>
				<tr>
					<th>Assignment</th>
					<th>Class</th>
					<th>Due Date</th>
					<th>Due</th>
					<th>Shared</th>
					<th class='center'>Actions</th>
				</tr>
			</thead>
			<tbody>
				{* Display the assignments *}
				{foreach from=$assignments_pending item=assignment}
				<tr class='assignment-brief-view {cycle values="tr-odd,tr-even"} {if $assignment.linkid !== NULL}linked{/if}'>
					<td>{$assignment.title|escape} {if $assignment.linkid !== NULL}<span class='special'>(linked assignment)</span>{/if}</td>
					<td>{$assignment.class|escape}</td>
					<td>{$assignment.due_date|date_format:$application.long_date}</td>
					<td class='left'>
					{if $assignment.days_left == 0}
						Due Today!
					{elseif $assignment.days_left == 1}
						Due Tomorrow!
					{elseif $assignment.days_left == -1}
						Due yesterday
					{elseif $assignment.days_left < 0}
						Due {math equation="abs(d)" d=$assignment.days_left} days ago
					{else}
						Due in {$assignment.days_left} days
					{/if}
					</td>
					<td>{if $assignment.is_shared == 1}Yes{else}No{/if}</td>
					<td class='center'>
						<a href='{$assignment.url}'>View</a> | 
						<a href='{$assignment.url_edit}'>Edit</a> | 
						<a href='{$assignment.url_delete}'>{if $assignment.linkid}Remove Link{else}Delete{/if}</a>
					</td>
				</tr>
				{/foreach}
			</tbody>
		</table>
		{else}
		<p class='info'>
			You don&#39;t have any saved assignments.
		</p>
		{/if}
	</div>

