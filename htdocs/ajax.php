<?php

require __DIR__ . '/../config/startup.php';

switch ( $_POST['mode'] )
{
	case 'get_task_info':
		$url = $config['jira_instance']['default'] . '/rest/api/2/issue/' . trim($_POST['key']);
		$curler = new TUI_Curler(
			$url,
			null,
			array(
				'Accept: application/json',
				'Content-Type: application/json'
			),
			$_POST['username'].':'.$_POST['password']
		);
		header('Content-Type: application/json');
		$response = $curler->send_get();
		echo $response['content'];
	break;
	default:
		header('HTTP/1.1 400 Huh?');
}