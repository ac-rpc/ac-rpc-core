
	<div class='main-widget' id='assignment-list-old'>
		<h3>My old assignments</h3>
		{if count($assignments_old) > 0}
		<table class='assignment-list'>
			<col width='30%' />
			<col width='15%' />
			<col width='30%' />
			<col width='5%' />
			<col width='20%' />
			<thead>
				<tr>
					<th>Assignment</th>
					<th>Class</th>
					<th>Due Date</th>
					<th>Shared</th>
					<th class='center'>Actions</th>
				</tr>
			</thead>
			<tbody>
				{* Display the assignments *}
				{foreach from=$assignments_old item=assignment}
				<tr class='assignment-brief-view {cycle values="tr-odd,tr-even"}'>
					<td>{$assignment.title|escape} {if $assignment.linkid !== NULL}<span class='special'>(linked assignment)</span>{/if}</td>
					<td>{$assignment.class|escape}</td>
					<td>{$assignment.due_date|date_format:$application.long_date}</td>
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
			You don&#39;t have any old assignments.
		</p>
		{/if}
	</div>

