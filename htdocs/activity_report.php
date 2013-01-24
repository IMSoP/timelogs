<?php

// Includes
require_once(dirname($_SERVER['DOCUMENT_ROOT']) . '/config/startup.php');

process_page();

// Display main and disconnect from DB
display_main();


//// OUTPUT FUNCTIONS ////

function display_main()
{
	echo '
	<script type="text/javascript" src="https://www.google.com/jsapi?key='.$GLOBALS['config']['google_api_key'].'"></script>
	<script type="text/javascript">
	google.load("jquery", "1.6.2");
	</script>
	<style tyle="text/css">
	.updated_recently { color: #16AD2D; }
	.not_updated_recently { color: #BB0000; font-weight: bold; }
	</style>
	';
	
	switch ($_REQUEST['mode'])
	{
		case 'report':
			echo CWT_Smarty_Template::quick_parse('activity_report', array(
				'report' => $GLOBALS['api_data']['report'],
				'total_last_updated' => $GLOBALS['api_data']['total_last_updated']
			));
		break;
			
		default:
			echo CWT_Smarty_Template::quick_parse('activity_report_form', array(
				'managers' => $GLOBALS['api_data']['managers']
			));
	}
}

//// PROCESSING FUNCTIONS ////

function process_page()
{	
	global $config;
	
	switch ($_REQUEST['mode'])
	{
		case 'report';
			// Get all projects owned by support (id 68075)
			
			$manager_id = intval($_REQUEST['manager_id']);
			$project_id = intval($_REQUEST['project_id']);
			$assignee_id = intval($_REQUEST['assignee_id']);
			
			if ($project_id)
			{
				$project_list = CWT_Intervals::get_resource('project', $project_id);
			}
			else
			{
				$project_list = CWT_Intervals::get_resource_list('project', array(
					'managerid' => $manager_id,
					'limit' => 100 // Avoid pagination (mostly)
				));
			}
			
			$report = array();
			
			// We get one less level back if we only ask for a single project - make them consistent
			$projects = $project_id ? $project_list->project : $project_list->project->item;
			foreach ($projects as $project)
			{
				$task_params = array(
					'excludeclosed' => 1,
					'limit' => 100, //avoid pagination (mostly)
					'projectid' => (int)$project->id
				);
				if ($_REQUEST['sla_only'])
				{
					$task_params['priorityid'] = implode(',', array_keys($config['sla_task_priorities']));
				}
				if ($assignee_id)
				{
					$task_params['assigneeid'] = $assignee_id;
				}
				
				// Get all tasks for these projects
				$task_list = CWT_Intervals::get_resource_list('task', $task_params);
				
				if (!count($task_list->task->item))
				{
					continue;
				}
				
				$project_note = CWT_Intervals::get_resource_list('projectnote', array(
					'projectid' => (int)$project->id,
					'title' => 'SLA'
				));
				$project_sla = (string)$project_note->projectnote->item->note;
				$project_sla = json_decode(html_entity_decode($project_sla));
				
				foreach ($task_list->task->item as $task)
				{
					$tasknotes = CWT_Intervals::get_resource_list('tasknote', array(
						'taskid' => (int)$task->id,
						'limit' => 100 // Avoid pagination (mostly)
					));
					
					$num_tasknotes = count($tasknotes->tasknote->item);
					
					$oldest_tasknote = $tasknotes->tasknote->item[$num_tasknotes-1];
					
					$task_open_date = strtotime((string)$oldest_tasknote->date);
					
					// Non-SLA tasks are lower priority but should still be resolved within 90 days
					$sla_due = $task_open_date + (90 * 24 * 60 * 60);
					$sla = 'Error: Failed to calculate';
					if ($project_sla)
					{
						$task_priority_name = $config['sla_task_priorities'][(int)$task->priorityid];
												
						if (isset($project_sla->$task_priority_name))
						{
							$task_sla = $project_sla->{$task_priority_name};
							$sla = 'Target fix time: '.$task_sla[2].' working hours';
							
							$sla_due = calculate_due_date($task_open_date, $task_sla[2] * 60);
							
							if ($sla_due < time())
							{
								$sla .= ', <strong style="color: #BB0000;">Already exceeded</strong>';
							}
							elseif ($sla_due < time() + (3 * 24 * 60 * 60)) // Due within next 3 days
							{
								$days_remaining = ($sla_due - time()) / (24 * 60 * 60);
								$sla .= ', <strong style="color: #FF9900;">Due '.date('H:i jS M', $sla_due).' (in '.sprintf('%.1f', $days_remaining).' days)</strong>';
							}
							else
							{
								$sla .= ', <strong style="color: #16AD2D;">Due '.date('H:i jS M', $sla_due).'</strong>';
							}
						}
						else
						{
							$sla = 'Task outside SLA';
						}
					}
					else
					{
						if (in_array((int)$task->statusid, array_keys($config['sla_task_priorities'])))
						{
							$sla = 'Invalid: Client does not have an SLA';
						}
						else
						{
							$sla = 'No SLA agreed';
						}
					}
					
					// Get the latest note for each task
					$last_updated_diff = (gmmktime() - strtotime($tasknotes->tasknote->item[0]->date)) / (24 * 60 * 60);
					
					$report[(string)$project->name][] = array(
						'task_id' => (int)$task->localid,
						'title' => (string)$task->title,
						'assignees' => cwt::coalesce_empty((string)$task->assignees, '<i>Unassigned</i>'),
						'status' => (string)$task->status,
						'last_updated' => array(
							'timestamp' => strtotime($tasknotes->tasknote->item[0]->date),
							'days_ago' => round($last_updated_diff),
							'by' => (string)$tasknotes->tasknote->item[0]->author,
							'note_title' => (string)$tasknotes->tasknote->item[0]->title,
							'note' => (string)$tasknotes->tasknote->item[0]->note,
							'class' => ($last_updated_diff > 3 ? 'not_updated_recently' : 'updated_recently')
						),
						'sla_due' => $sla_due,
						'sla' => $sla
					);
					$total_last_updated[(string)$project->name] += $last_updated_diff;
				}
				usort($report[(string)$project->name], 'sort_by_sla_due');
		
			}
			
			$GLOBALS['api_data']['report'] = $report;
			$GLOBALS['api_data']['total_last_updated'] = $total_last_updated;
		break;
		
		default:
	}
	
	// CWT_Intervals::print_timers();
	return;

}

function calculate_due_date($start_date, $mins_available)
{
	// Do single-day maths in minutes since midnight, to make arithmatic easier.
	
	$office_opens_mins = 60 * 9; // Office opens at 09:00hrs
	$office_closes_mins = 60 * 17.5; // Office closes at 17:30hrs
	$mins_per_day = $office_closes_mins - $office_opens_mins;
	
	$start_date_mins = (date('H', $start_date) * 60) + date('i', $start_date);
	if ($start_date_mins < $office_closes_mins)
	{
		// Either finish the task today, or keep working on it until close of play
		$mins_worked_first_day = min($mins_available, $office_closes_mins - $start_date_mins);
		// Task was raised during office hours
		$schedule[date('Y-m-d', $start_date)] = $mins_worked_first_day;
		$mins_available -= $mins_worked_first_day;
	}
	
	$next_available_offset = 0;
	while($mins_available > 0)
	{
		$next_available_day = CWT::next_available_day($next_available_offset, $schedule, $mins_available);
		$mins_worked_this_day = min($mins_available, $mins_per_day);
		$schedule[$next_available_day] = $mins_worked_this_day;
		$mins_available -= $mins_worked_this_day;
	}
	
	end($schedule); // We're about to use current();
	if (count($schedule) == 1)
	{
		// Work needs to be completed same day, will start timer when task was raised
		$due_date = $start_date + (60 * current($schedule));
	}
	else
	{
		// Work spans multiple days, final day will start when office opens
		$due_date = strtotime(key($schedule)) + (60 * ($office_opens_mins + current($schedule)));
	}
	
	return $due_date;
}

function sort_by_sla_due($a, $b)
{
	return $a['sla_due'] > $b['sla_due'];
}