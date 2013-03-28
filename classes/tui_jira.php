<?php

class Tui_Jira
{
	// TODO:
	// Error Handling
	// PHP Doc
	
	private static $api_url = 'https://jira.tuisasweb.com/rest/api/2';
	private static $api_username;
	private static $api_password;
	private static $debug = array('call_timers' => array());

//	public static function get_resource_list($name, $params)
//	{
//		self::$api_token = $_REQUEST['api_token'];
//
//		$start_time = microtime(true);
//
//		$url = self::$api_url.$name.'/';
//		if ($params)
//		{
//			$first_param = true;
//			foreach ($params as $key => $value)
//			{
//				$url .= $first_param ? '?' : '&';
//				$url .= $key . '=' . $value;
//				$first_param = false;
//			}
//		}
//
//		$curler = new CWT_Curler(
//			$url,
//			null,
//			array(
//				'Accept: application/xml',
//				'Content-Type: application/xml',
//				'Authorization: Basic '.base64_encode(self::$api_token.':x')
//			)
//		);
//
//		$result = $curler->send_get();
//
//		self::$debug['call_timers'][$name][] = microtime(true) - $start_time;
//
//		return simplexml_load_string($result['content']);
//	}
//
//	public static function get_resource($name, $id)
//	{
//		self::$api_token = $_REQUEST['api_token'];
//
//		$start_time = microtime(true);
//
//		$curler = new CWT_Curler(
//			self::$api_url.$name.'/'.$id.'/',
//			null,
//			array(
//				'Accept: application/xml',
//				'Content-Type: application/xml',
//				'Authorization: Basic '.base64_encode(self::$api_token.':x')
//			)
//		);
//
//		$result = $curler->send_get();
//
//		self::$debug['call_timers'][$name][] = microtime(true) - $start_time;
//
//		return simplexml_load_string($result['content']);
//	}
//
	public static function post_worklog($issue_key, $comment, $started, $time_spent)
	{
		self::$api_username = $_REQUEST['username'];
		self::$api_password = $_REQUEST['password'];

		$start_time = microtime(true);

		$url = self::$api_url.'/issue/'.$issue_key.'/worklog?adjustEstimate=auto';

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