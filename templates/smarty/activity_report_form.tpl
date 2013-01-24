<form action="activity_report.php" method="post">
<input type="hidden" name="mode" value="report" />
<table>
	<tr>
		<th>API Token</th>
		<td><input type="text" name="api_token" id="api_token" /></td>
	</tr>
	<tr>
		<th>Project Manager</th>
		<td><select name="manager_id" id="managers" disabled><option>&laquo; Please Select &laquo;</option>{html_options options=$managers}</select></td>
	</tr>
	<tr>
		<th>Project</th>
		<td><select name="project_id" id="projects" disabled><option>&laquo; Choose Manager First &laquo;</select></td>
	</tr>
	<tr>
		<th>Assignee</th>
		<td><select name="assignee_id" id="assignees" disabled><option>&laquo; Please Select &laquo;</option>{html_options options=$managers}</select></td>
	</tr>
</table>
<input type="checkbox" name="sla_only" /> Limit to tasks covered by SLA<br />
<input type="checkbox" name="actionable_only" disabled="disabled" /> Exclude tasks on hold or with the client or supplier<br />
<input type="submit" value="Report" />
</form>

<script type="text/javascript">
{* Also in workload.tpl - Should have a more generic way of doing this *}
{literal}
var managers_for;

function populate_managers()
{
	// Only fetch the managers if we've got a potentially valid API key and we don't already
	// have the managers for that key
	if (jQuery('input#api_token').val().length == 11 && $('input#api_token').val() != managers_for)
	{
		jQuery.getJSON(
			"ajax.php",
			{resource: 'managers', api_token: jQuery('input#api_token').val()}, 
			function(j){
				var options = '<option value="">&laquo; Please Select &raquo;</option>';
				for (var i = 0; i < j.length; i++) {
					options += '<option value="' + j[i].manager_id + '">' + j[i].name + '</option>';
				}
				jQuery("select#managers").html(options);
				jQuery("select#managers").attr('disabled', false);
			}
		)
		managers_for = $('input#api_token').val();
		
		jQuery.getJSON(
			"ajax.php",
			{resource: 'person', api_token: jQuery('input#api_token').val(), 'api_params[clientid]': -1}, 
			function(j){
				var options = '<option value="">&laquo; Please Select &raquo;</option>';
				for (var i = 0; i < j.length; i++) {
					options += '<option value="' + j[i].person_id + '">' + j[i].name + '</option>';
				}
				jQuery("select#assignees").html(options);
				jQuery("select#assignees").attr('disabled', false);
			}
		)
	}
	jQuery('input#api_token').val();
}

jQuery('input#api_token').change( function() { populate_managers(); } );
jQuery('input#api_token').keypress( function() { populate_managers(); } );
jQuery().ready(populate_managers());

jQuery('select#managers').change(
	function() {
		jQuery.getJSON(
			"ajax.php",
			{resource: 'projects', manager_id: $(this).val(), api_token: jQuery('input#api_token').val()}, 
			function(j){
				var options = '<option value="">&laquo; All &raquo;</option>';
				for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].project_id + '">' + j[i].name + '</option>';
				}
				jQuery("select#projects").html(options);
				jQuery("select#projects").attr('disabled', false);
			}
		)
	}
);
{/literal}
</script>