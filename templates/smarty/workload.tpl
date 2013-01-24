<form action="workload.php" method="post"><!-- post to avoid api tokens in URLs -->

API Token: <input type="text" name="api_token" id="api_token" /><br />
Person: <select name="assignee_id" id="resources" disabled>
<option value="">&laquo; Please Select &raquo;</option>
{html_options options=$people selected=$current_person}
</select><br />
Hours per day: <input type="text" name="daily_hours" value="{$daily_hours}" />
<input type="submit" value="Run Report" />
</form>


{if $current_person}
	<table border="1">
		<tr>
			<th>Date</th>
			<th>Est</th>
			<th>Act</th>
			<th>Due</th>
			<th>Task</th>
			<th>Project</th>
		</tr>
	
	{foreach from=$no_time_allocated_tasks item=task_id}
		<tr>
			<td>No Time Allocated</td>
			<td>{$tasks.$task_id.estimated_time|round:"2"}</td>
			<td>{$tasks.$task_id.actual_time|round:"2"}</td>
			<td {if $smarty.now|date_format:"%Y-%m-%d" > $tasks.$task_id.due}style="color: red;"{/if}>{$tasks.$task_id.due}</td>
			<td><a href="{$intervals_url}tasks/view/{$task_id}/" target="_blank">#{$task_id} {$tasks.$task_id.title}</a></td>
			<td>{$tasks.$task_id.project}</td>
		</tr>
	{/foreach}
	
	{foreach from=$no_time_remaining_tasks item=task_id}
		<tr>
			<td>No Time Remaining</td>
			<td>{$tasks.$task_id.estimated_time|round:"2"}</td>
			<td>{$tasks.$task_id.actual_time|round:"2"}</td>
			<td {if $smarty.now|date_format:"%Y-%m-%d" > $tasks.$task_id.due}style="color: red;"{/if}>{$tasks.$task_id.due}</td>
			<td><a href="{$intervals_url}tasks/view/{$task_id}/" target="_blank">#{$task_id} {$tasks.$task_id.title}</a></td>
			<td>{$tasks.$task_id.project}</td>
		</tr>
	{/foreach}
	
	{foreach from=$schedule item=day key=date}
		{foreach from=$day.tasks_allocated item=task_id name=dated_row}
		<tr>
			{if $smarty.foreach.dated_row.first}
				<td rowspan="{$day.tasks_allocated|@count}">{$date}</td>
			{/if}
			<td>{$tasks.$task_id.estimated_time|round:"2"}</td>
			<td>{$tasks.$task_id.actual_time|round:"2"}</td>
			<td {if $date > $tasks.$task_id.due}style="color: red;"{/if}>{$tasks.$task_id.due}</td>
			<td><a href="{$intervals_url}tasks/view/{$task_id}/" target="_blank">#{$task_id} {$tasks.$task_id.title}</a></td>
			<td>{$tasks.$task_id.project}</td>
		</tr>
		{/foreach}
	{/foreach}
	</table>
{/if}

<p>This report is a best guess for actual delivery dates, however it does make several assumptions:</p>
<ul>
	<li>All time is logged upto the end of yesterday (and no time is logged for today/future)</li>
	<li>Tasks will be worked on in order of due date, unless they are before their start date</li>
	<li>Tasks without a time estimate will take 2 hours</li>
	<li>No new tasks will be raised</li>
	<li>Time estimates are accurate</li>
	<li>The remaining time will be evenly divided between all employees</li>
	<li>All tasks are ready to start and there are no external dependencies</li>
	<li>The employee will work mon-fri, regardless of sickness, holiday or bank holidays, unless 
	those days are loaded into $config['non-work_dates'] or already have future time logged against them</li>
	<li>Tasks will be completed in order of ascending due date</li>
</ul>

<script type="text/javascript">
{* Similar to code in activity_report_form.tpl - Should have a more generic way of doing this *}
{literal}
var assignees_for;

function populate_assignees()
{
	// Only fetch the managers if we've got a potentially valid API key and we don't already
	// have the managers for that key
	if (jQuery('input#api_token').val().length == 11 && $('input#api_token').val() != assignees_for)
	{
		assignees_for = $('input#api_token').val();
		$.getJSON(
			"ajax.php",{resource: 'person', api_token: $('input#api_token').val(), 'api_params[clientid]': -1}, 
			function(j){
				var options = '<option value="">&laquo; Please Select &raquo;</option>';
				for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].person_id + '">' + j[i].name + '</option>';
			}
			$("select#resources").html(options);
			$("select#resources").attr('disabled', false);
		})
	}
	$('input#api_token').val();
}

jQuery('input#api_token').change( function() { populate_assignees(); } );
jQuery('input#api_token').keypress( function() { populate_assignees(); } );
jQuery().ready(populate_assignees());

{/literal}
</script>