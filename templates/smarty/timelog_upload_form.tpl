<form action="?mode=save" method="post">
  Date: <input type="text" name="date" value="{$date}" placeholder="yyyy-mm-dd" /><br />
  JIRA Username: <input type="text" name="username" /><br />
  JIRA Password: <input type="password" name="password" /><br />
	<table class="homemain">
		<tr>
			<th>Log</th>
			<th>Task #</th>
			<th>Description</th>
			<th>Recorded Time Taken</th>
			<th>Actual Time Taken</th>
			<th>Harvest description</th>
		</tr>
		{foreach from=$timelogs item=timelog key=id}
			<tr>
				<td><input type="checkbox" name="task[{$id}][log]" {if $timelog.task}checked="checked"{/if} /></td>
				<td>#<input type="text" name="task[{$id}][issue_key]" value="{$timelog.task}" size="15" /></td>
				<td><input type="text" name="task[{$id}][description]" value="{$timelog.description}" size="50" /></td>
				<td><input type="text" name="task[{$id}][duration]" value="{$timelog.recorded_duration}" size="10" class="recorded_time" /></td>
				<td><input type="hidden" name="task[{$id}][actual_time]" value="{$timelog.duration}" size="10" class="actual_time" />{$timelog.duration}</td>
				<td><input type="text" onclick="this.select()" value="{$timelog.task} {$timelog.description}" size="60" /></td>
			</tr>
		{/foreach}
		<tr>
			<td></td>
			<td></td>
			<td></td>
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
  var total_recorded_mins = 0;
  var total_actual_mins = 0;
  for (var i = 0; i < jQuery('.recorded_time').length; i++) {
    total_recorded_mins += time2mins(jQuery('.recorded_time')[i].value);
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
</script>
