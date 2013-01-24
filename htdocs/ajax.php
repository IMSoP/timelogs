<?php

// Includes
require_once(dirname($_SERVER['DOCUMENT_ROOT']) . '/config/startup.php');

$return = array();
switch ($_REQUEST['resource'])
{
	case 'projects':
		$project_list = CWT_Intervals::get_resource_list('project', array(
			'managerid' => intval($_REQUEST['manager_id']),
			'limit' => 100,
			'active' => 1
		));
		
		foreach ($project_list->project->item as $project)
		{
			$return[] = array(
				'project_id' => (int)$project->id,
				'name' => (string)$project->name
			);
		}
	break;
		
	case 'managers':
		$managers_sxml = CWT_Intervals::get_resource_list('person', array(
			'active' => 1,
			'excludegroupids' => '4,5', // Restrict to managers
			'limit' => 100 // "All", at least for our current installation
		));
		
		foreach ($managers_sxml->person->item as $manager)
		{
			$return[] = array(
				'manager_id' => (int)$manager->id,
				'name' => (string)$manager->firstname .' '.$manager->lastname
			);
		}
	break;
	
	case 'person':
		$params = array_merge(
			array(
				'active' => 1,
				'limit' => 100 // "All", at least for our current installation
			),
			(array)$_REQUEST['api_params']
		);
		$persons_sxml = CWT_Intervals::get_resource_list('person', $params);
		
		foreach ($persons_sxml->person->item as $person)
		{
			$return[] = array(
				'person_id' => (int)$person->id,
				'name' => (string)$person->firstname .' '.$person->lastname
			);
		}
	break;
}
header('Content-type: application/json');
echo json_encode($return);
// EOF