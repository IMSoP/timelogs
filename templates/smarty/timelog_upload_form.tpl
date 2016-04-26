<form action="?mode=save" method="post">
<fieldset style="width: 300px; margin: 5px;">
  <label>Date: <input type="text" name="date" value="{$date}" placeholder="yyyy-mm-dd" /></label><br />
  <label>JIRA Username: <input type="text" name="username" /></label><br />
  <label>JIRA Password: <input type="password" name="password" /></label><br />
</fieldset>
	<table style="margin: 5px;">
		<tr>
			<th>Log?</th>
			<th>Task #</th>
			<th>Description</th>
			<th>Recorded Time</th>
			<th>Actual Time</th>
			<th>Task Name</th>
		</tr>
		{foreach from=$timelogs item=timelog key=id}
			<tr>
				<td><input type="checkbox" name="task[{$id}][log]" {if $timelog.task}checked="checked"{/if} /></td>
				<td>#<input type="text" name="task[{$id}][issue_key]" value="{$timelog.task}" size="15" /></td>
				<td><input type="text" name="task[{$id}][description]" value="{$timelog.description}" size="50" /></td>
				<td><input type="text" name="task[{$id}][duration]" value="{$timelog.recorded_duration}" size="10" class="recorded_time" /></td>
				<td><input type="hidden" name="task[{$id}][actual_time]" value="{$timelog.duration}" size="10" class="actual_time" />{$timelog.duration}</td>
				<td><span class="task_name"></span></td>
			</tr>
		{/foreach}
		<tr style="font-weight: bold;">
			<td></td>
			<td></td>
			<td>TOTAL</td>
			<td id="recorded_total"></td>
			<td id="actual_total"></td>
			<td></td>
		</tr>
	</table>
	<input type="submit" value="Post to JIRA" />
</form>

<script type="text/javascript">
function time2mins(time) {
  var split_time = time.split(':');
  return parseInt(split_time[0]) * 60 + parseInt(split_time[1]);
}
function mins2time(time) {
  return zero_pad(Math.floor(time / 60), 1) + ':' + zero_pad(time % 60, 2);
}
function zero_pad(num, places) {
  var zero = places - num.toString().length + 1;
  return Array(+(zero > 0 && zero)).join("0") + num;
}
function update_totals()
{
  var $active_rows = jQuery('tr').has('input[name$="[log]"]:checked');
  var total_recorded_mins = 0;
  var total_actual_mins = 0;
  for (var i = 0; i < $active_rows.length; i++) {
    total_recorded_mins += time2mins($active_rows.find('.recorded_time')[i].value);
  }
  for (var i = 0; i < jQuery('.actual_time').length; i++) {
    total_actual_mins += time2mins(jQuery('.actual_time')[i].value);
  }
  jQuery('#recorded_total').html(mins2time(total_recorded_mins));
  jQuery('#actual_total').html(mins2time(total_actual_mins));
}
jQuery().ready(update_totals());
jQuery('.recorded_time').change(function() {
  if (this.value.indexOf(':') == -1) {
    this.value += ':00';
  }
  update_totals();
});
jQuery('input[type=checkbox]').change(function() {
  update_totals();
});

function get_task_info($table_row, task_key)
{
	jQuery.post(
		'ajax.php',
		{
			'mode': 'get_task_info',
			'key': task_key,
			'username': jQuery('input[name=username]').val(),
			'password': jQuery('input[name=password]').val()
		},
		function task_info_success(task_data) {
			$table_row.find('.task_name').text( task_data.fields.summary );
		}
	);
}
// Ugly: delay so password manager has a chance to work
setTimeout(function(){
	var $non_empty_rows = jQuery('tr').has('input[name$="[issue_key]"]:[value!=""]');
	$non_empty_rows.each(function() {
		var $row = jQuery(this);
		get_task_info($row, $row.find('input[name$="[issue_key]"]').val());
	});

	jQuery('input[name$="[issue_key]"]').bind('change', function() {
		get_task_info(jQuery(this).closest('tr'), jQuery(this).val());
	});
}, 100);
</script>
