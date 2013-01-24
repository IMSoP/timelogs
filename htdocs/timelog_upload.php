<?php

require_once(dirname($_SERVER['DOCUMENT_ROOT']) . '/config/startup.php');


if ($_GET['mode'] == 'save')
{

	foreach ($_POST['task'] as $task)
	{
		if ($task['log'])
		{
			$task_time = explode(':', $task['duration']);
			$task_time_seconds = ($task_time[0] * 3600) + ($task_time[1] * 60);
			
			$timer_params = array(
				'personid' => $_POST['personid'],
				'time' => $task_time_seconds,
				'name' => $task['description']
			);
			if ($task['intervals_id'])
			{
				$sxml_task = CWT_Intervals::get_resource_list('task', array(
					'localid' => $task['intervals_id'])
				);
				$internal_task_id = (int)$sxml_task->task->item->id;
				$timer_params['taskid'] = $internal_task_id;
			}
			// Create the timer (which has to start running)
			$sxml_timer = CWT_Intervals::post_resource('timer', $timer_params);
			
			// Stop the timer
			$sxml_timer = CWT_Intervals::post_resource(
				'timer', 
				array(
					'time' => $task_time_seconds,
					'starttime' => null
				), 
				(int)$sxml_timer->timer->id
			);
		}
	}
	//CWT_Intervals::print_timers();
}
	
if (isset($_FILES['timelogs']))
{
	$file = file($_FILES['timelogs']['tmp_name']);
	$params = array(
		'date' => strtotime(substr($_FILES['timelogs']['name'], 0, 10)),
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
		preg_match('/\#(\d+)/', $line[2], $matches);

		// Round to nearest 5 minutes, with a tendancy to round up
		$duration[1] = (round(($duration[1] + 1) / 5) * 5);

		 // If we've reached an hour, change 60 minutes to one hour
		if ($duration[1] >= 60)
		{
			$duration[0]++;
			$duration[1] -= 60;
		}
		$params['timelogs'][] = array(
			'duration' => $line[1],
			'recorded_duration' => $duration[0].':'.sprintf('%02d', $duration[1]),
			'description' => trim($line[2]),
			'task' => $matches[1]
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
	<form action="timelog_upload.php" enctype="multipart/form-data" method="post">
	R3T3 timelog: <input type="file" name="timelogs" />
	<input type="submit" value="Upload" />
	</form>
<?php
}