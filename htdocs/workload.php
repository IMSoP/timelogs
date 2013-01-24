<?php

// Includes
require_once(dirname($_SERVER['DOCUMENT_ROOT']) . '/config/startup.php');

// Should run quickly (except while waiting for Intervals), but has had a tendancy to get into infinite loops - kill it quickly
set_time_limit(1);

$task_statuses = array(
	//'closed' => 63860,
	//'on hold' => 80518,
	'open' => 63859,
	'ready for staging' => 65870,
	//'ready to close' => 78067,
	//'ready to go live' => 65873,
	//'requires client testing' => 65872,
	//'waiting on client' => 65868,
	//'waiting on supplier' => 65869,
	'requires cwt testing' => 65871,
	'requires quote' => 81179,
	'requires spec' => 97659
);

if ($_REQUEST['assignee_id'])
{
	$tasks = CWT_Intervals::get_resource_list('task', array(
		'assigneeid' => intval($_REQUEST['assignee_id']),
		'limit' => 200,
		// everything except closed, i.e. open, cwt/clienttesting, readystaging/close/golive, quote, spec
		'statusid' => implode(',', $task_statuses),
		'sortfield' => 'datedue'
	));
	
	$workload = array();
	$schedule = array();
	$next_available_offset = 0;
	$next_available_day = gmdate('Y-m-d', gmmktime() + ($next_available_offset * 24 * 60 * 60));
	$tpl_tasks = array();
	
	$future_time = get_future_time(intval($_REQUEST['assignee_id']));
	foreach ($future_time as $day)
	{
		foreach ($day as $task)
		{
			$tpl_tasks[$task['task_id']]['title'] = $task['task_name'];
			$tpl_tasks[$task['task_id']]['project'] = $task['project'];
			$tpl_tasks[$task['task_id']]['estimated_time'] = 0;
			$tpl_tasks[$task['task_id']]['due'] = 'N/A';
		}
	}
	
	// Convert the SimpleXML tree to and array that we can delete elements from
	$unscheuled_tasks = (array)$tasks->task;
	$unscheuled_tasks = $unscheuled_tasks['item'];
	
	while (count($unscheuled_tasks))
	{
		foreach ($unscheuled_tasks as $index => $task)
		{
			// Make sure we don't end up in an infinite loop if there's nothing to 
			// work on on a particular day
			$just_skipped = false;
			
			if ((string)$task->dateopen > $next_available_day)
			{
				// We're not ready to start this yet, try the next one
				$just_skipped = true;
				continue;
			}
			
			
			$estimate = (float)$task->estimate;
			if ($estimate == 0)
			{
				// Try having a default estimate
				$estimate = 2;
			}
			$tpl_tasks[(int)$task->localid]['title'] = (string)$task->title;
			$tpl_tasks[(int)$task->localid]['project'] = (string)$task->project;
			$tpl_tasks[(int)$task->localid]['estimated_time'] = (float)$estimate;
			$tpl_tasks[(int)$task->localid]['actual_time'] = (float)$actual;
			$tpl_tasks[(int)$task->localid]['due'] = (string)$task->datedue;
			
			if ($estimate == 0)
			{
				$no_time_allocated_tasks[] = (int)$task->localid;
				$tpl_tasks[(int)$task->localid]['actual_time'] = '?';
				continue;
			}
			
			$outstanding_time = ((float)$estimate - $total_time) / (preg_match_all('/,/', (string)$task->assigneeid, $devnull) + 1);
			
			if ($outstanding_time <= 0)
			{
				$no_time_remaining_tasks[] = (int)$task->localid;
				unset($unscheuled_tasks[$index]);
				break;
			}
			
			while ($outstanding_time > 0)
			{
				if ($schedule[$next_available_day]['hours_allocated'] >= HOURS_PER_DAY)
				{
					$next_available_day = CWT::next_available_day($next_available_offset, $schedule, $future_time);
				}
				$hours_this_day = min($outstanding_time, (HOURS_PER_DAY - $schedule[$next_available_day]['hours_allocated']));
				$outstanding_time = $outstanding_time - $hours_this_day;
				$schedule[$next_available_day]['hours_allocated'] += $hours_this_day;
				$schedule[$next_available_day]['tasks_allocated'][] = (int)$task->localid;
			}
			
			// Take the task out of unscheduled tasks, and have another look at the first task that
			// is still in unscheduled tasks (we may have skipped a task due to start date)
			unset($unscheuled_tasks[$index]);
			break;
		}
		
		// Make sure we don't end up in an infinite loop if there's nothing to 
		// work on on a particular day
		if ($just_skipped == true)
		{
			$next_available_day = CWT::next_available_day($next_available_offset, $schedule, $future_time);
		}
	}
}

echo '
<script type="text/javascript" src="https://www.google.com/jsapi?key='.$GLOBALS['config']['google_api_key'].'"></script>
<script type="text/javascript">
google.load("jquery", "1.6.2");
</script>
';

echo CWT_Smarty_Template::quick_parse('workload', array(
	'people' => $people_tpl,
	'current_person' => intval($_REQUEST['assignee_id']),
	'schedule' => $schedule,
	'no_time_allocated_tasks' => $no_time_allocated_tasks,
	'no_time_remaining_tasks' => $no_time_remaining_tasks,
	'tasks' => $tpl_tasks,
	'intervals_url' => $_SERVER['cwt_intervals_url'],
	'daily_hours' => HOURS_PER_DAY
));
	
//CWT_Intervals::print_timers();

function get_future_time($person_id)
{
	$time_list = CWT_Intervals::get_resource_list('time', array(
		'limit' => 200,
		'personid' => $person_id,
		'datebegin' => gmdate('Y-m-d')
	));
	$time_array = array();
	foreach ($time_list->time->item as $time)
	{
		$time_array[(string)$time->dateiso][] = array(
			'time' => (float)$time->time,
			'task_id' => (int)$time->tasklocalid,
			'task_name' => (string)$time->task,
			'project' => (string)$time->project
		);
	}
	return $time_array;
}

//EOF