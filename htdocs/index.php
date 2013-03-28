<?php

require_once(dirname($_SERVER['DOCUMENT_ROOT']) . '/config/startup.php');


if ($_GET['mode'] == 'save')
{
	foreach ($_POST['task'] as $task)
	{
		if ($task['log'])
		{
			$task_time = explode(':', $task['duration']);
      $success = Tui_Jira::post_worklog($task['issue_key'], $task['description'], $_POST['date'].'T09:00:00.000-0000', "{$task_time[0]}h {$task_time[1]}m");
      if ($success) {
        echo '<a href="https://jira.tuisasweb.com/browse/'.htmlspecialchars(urlencode($task['issue_key'])).'">'.htmlspecialchars($task['issue_key'])."</a> logged successfully.<br>";
      }
      else {
        echo '<strong style="color: red;">Error logging '.htmlspecialchars($task['issue_key']).'.</strong><br>';
      }
		}
	}
	//Tui_Jira::print_timers();
}

if (isset($_FILES['timelogs']))
{
	$file = file($_FILES['timelogs']['tmp_name']);
	$params = array(
		'date' => substr($_FILES['timelogs']['name'], 0, 10),
		'timelogs' => array()
	);
	foreach ($file as $key => $line)
	{
		if (substr($line, 0, 1) == '=')
		{
			// We don't want to log the total
			continue;
		}

		$line = explode("\t", $line);
		if ($line[1] == '0:00:00' && trim($line[2]) == '')
		{
			// Strip out totally blank lines
			continue;
		}


		$duration = explode(':', $line[1]);
		// Round to nearest 5 minutes, with a tendancy to round up
		$duration[1] = (round(($duration[1] + 1) / 5) * 5);

		// If rounding caused us to reach an hour, change 60 minutes to one hour
		if ($duration[1] >= 60)
		{
			$duration[0]++;
			$duration[1] -= 60;
		}

    // Take the task number out of the description
    $description = $line[2];
		preg_match('/^([A-Za-z]+\-\d+)/', $description, $matches);
    if (!empty($matches[1])) {
      $description = trim(str_replace($matches[1].' ', '', $description));
    }
    $task = $matches[1];

		preg_match('/@([\d\.]+)h$/', $description, $matches);
    if ($matches[1]) {
      $duration[0] = intval($matches[1]);
      $duration[1] = ($matches[1]-intval($matches[1]))*60;
      if (!empty($matches[0])) {
        $description = trim(str_replace(' '.$matches[0], '', $description));
      }
    }

		$params['timelogs'][] = array(
			'duration' => $line[1],
			'recorded_duration' => $duration[0].':'.sprintf('%02d', $duration[1]),
			'description' => $description,
			'task' => $task
		);
	}
	echo '
	<script type="text/javascript" src="https://www.google.com/jsapi?key='.$GLOBALS['config']['google_api_key'].'"></script>
	<script type="text/javascript">
	google.load("jquery", "1.6.2");
	</script>
	';
	echo CWT_Smarty_Template::quick_parse('timelog_upload_form', $params);
}
else
{
?>
	<form action="?" enctype="multipart/form-data" method="post">
	R3T3 timelog: <input type="file" name="timelogs" />
	<input type="submit" value="Upload" />
	</form>
<?php
}