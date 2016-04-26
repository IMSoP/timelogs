<?php

class Upload_Tempo
{
	// TODO:
	// Error Handling
	// PHP Doc

	private static $api_username;
	private static $api_password;
	private static $debug = array('call_timers' => array());

	public function post_worklog($issue_key, $comment, $started, $time_spent_h, $time_spent_m, $worktype)
	{
		self::$api_username = $_REQUEST['username'];
		self::$api_password = $_REQUEST['password'];

		$start_time = microtime(true);

		$api_url = self::get_instance_url($issue_key);

		$info_url = $api_url . '/rest/api/2/issue/' . $issue_key;

		$curler = new TUI_Curler(
			$info_url,
			null,
			array(
				'Accept: application/json',
				'Content-Type: application/json',
				//'Authorization: Basic '.base64_encode(self::$api_token.':x')
			),
			self::$api_username.':'.self::$api_password
		);

		$task_info = $curler->send_get();
		$task_info = json_decode($task_info['content'], true);
		$old_time_remaining = $task_info['timetracking']['remainingEstimateSeconds'];
		
		$time_spent_seconds = $time_spent_h * 3600 + $time_spent_m * 60;

		$new_time_remaining = max(0, $old_time_remaining - $time_spent_seconds);

		$upload_url = $api_url . '/rest/tempo-timesheets/3/worklogs/';

		$request = [
			"worklogAttributes" =>
			[
				[
					"key"   => "_Worktype_",
					"value" => $worktype
				]
			],
			"issue"            => [
				"key"                      => $issue_key,
				"remainingEstimateSeconds" => $new_time_remaining
			],
			"author"           =>
				[
					"name" => $_POST['username']
				],
			"comment"          => $comment,
			"dateStarted"      => $started,
			"timeSpentSeconds" => $time_spent_seconds
		];

		$curler = new TUI_Curler(
			$upload_url,
			null,
			array(
				'Accept: application/json',
				'Content-Type: application/json',
				//'Authorization: Basic '.base64_encode(self::$api_token.':x')
			),
			self::$api_username.':'.self::$api_password
		);

		$result = $curler->send_post(json_encode($request));

		self::$debug['call_timers'][$issue_key][] = microtime(true) - $start_time;

		return $result['headers']['HTTP-Status'] == 200;
	}

	public function get_instance_url($issue_key)
	{
		list($project_key, $issue_id) = explode('-', $issue_key);

		if (isset($GLOBALS['config']['jira_instance'][strtoupper($project_key)])) {
			return $GLOBALS['config']['jira_instance'][strtoupper($project_key)];
		}
		else {
			return $GLOBALS['config']['jira_instance']['default'];
		}
	}

	/**
	 * Show how long each call took, grouped by resource
	 * Useful for identifing if you're doing lots of unnecessary calls
	 */
	public function print_timers()
	{
		echo '<pre>Timers: ';
		print_r(self::$debug['call_timers']);
		echo '</pre>';
	}
}

//EOF
