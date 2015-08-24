<?php

class Tui_Jira
{
	// TODO:
	// Error Handling
	// PHP Doc

	private static $api_username;
	private static $api_password;
	private static $debug = array('call_timers' => array());

	public static function post_worklog($issue_key, $comment, $started, $time_spent)
	{
		self::$api_username = $_REQUEST['username'];
		self::$api_password = $_REQUEST['password'];

		$start_time = microtime(true);

		$api_url = self::get_instance_url($issue_key);
		$url = $api_url.'/rest/api/2/issue/'.$issue_key.'/worklog?adjustEstimate=auto';

		$request = array(
		  "comment" => $comment,
		  "started" => $started,
		  "timeSpent" => $time_spent,
		);

		$curler = new Tui_Curler(
			$url,
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

		return $result['headers']['HTTP-Status'] == 201;
	}

	public static function get_instance_url($issue_key)
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
	public static function print_timers()
	{
		echo '<pre>Timers: ';
		print_r(self::$debug['call_timers']);
		echo '</pre>';
	}
}

//EOF
