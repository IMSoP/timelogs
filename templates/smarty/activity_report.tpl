{foreach from=$report item=tasks key=project_name}
	<h2>{$project_name}</h2>
	{ assign var="num_tasks" value=$tasks|@count }
	{if $num_tasks gt 0}
		<p>
		{$num_tasks} tasks, 
		Average time since last update: {$total_last_updated.$project_name/$num_tasks|round} days, 
		(total {$total_last_updated.$project_name|round} days)
		</p>
	{/if}
	
	{foreach from=$tasks item=task}
		<table style="width: 100%">
			<tr>
				<td style="width: 50%; vertical-align: top;">
					<strong>
						<a href="https://cwtdigital.intervalsonline.com/tasks/view/{$task.task_id}/" target="_blank">
							#{$task.task_id} {$task.title}
						</a>
					</strong><br />
					{$task.assignees}, {$task.status}<br />
					{$task.sla}<br />
					<span class="{$task.last_updated.class}">Last updated {$task.last_updated.days_ago} days ago by {$task.last_updated.by}</span>
				</td>
				<td style="vertical-align: top;">
					<div style="border: 1px solid lightgrey; margin: 0; height: 80px; overflow: auto;">
					{$task.last_updated.note_title}{if $task.last_updated.note}: {$task.last_updated.note}{/if}
					</div>
				</td>
			</tr>
		</table>
	{foreachelse}
		<p><strong>No Open Tasks</strong></p>
	{/foreach}
{/foreach}