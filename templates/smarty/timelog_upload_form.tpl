<form action="timelog_upload.php?mode=save" method="post">
	API Token: <input type="text" name="api_token" id="api_token" /><br />
	Person: <select name="personid" id="person" disabled></select><br />
	<table class="homemain">
		<tr>
			<th>Log</th>
			<th>Task #</th>
			<th>Description</th>
			<th>Recorded Time Taken</th>
			<th>Actual Time Taken</th>
		</tr>
		{foreach from=$timelogs item=timelog key=id}
			<tr>
				<td><input type="checkbox" name="task[{$id}][log]" checked="checked" /></td>
				<td>#<input type="text" name="task[{$id}][intervals_id]" value="{$timelog.task}" size="6" /></td>
				<td><input type="text" name="task[{$id}][description]" value="{$timelog.description}" size="50" /></td>
				<td><input type="text" name="task[{$id}][duration]" value="{$timelog.recorded_duration}" size="10" /></td>
				<td>{$timelog.duration}</td>
			</tr>
		{/foreach}
	</table>
	<input type="submit" value="Post to Intervals" />
</form>

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
			$("select#person").html(options);
			$("select#person").attr('disabled', false);
		})
	}
	$('input#api_token').val();
}

jQuery('input#api_token').change( function() { populate_assignees(); } );
jQuery('input#api_token').keypress( function() { populate_assignees(); } );
jQuery().ready(populate_assignees());

{/literal}
</script>