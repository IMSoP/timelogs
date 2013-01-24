<?php

class CWT_Intervals
{
	// TODO:
	// Error Handling
	// PHP Doc
	
	private static $api_url = 'https://api.myintervals.com/';
	private static $api_token;
	private static $debug = array('call_timers' => array());
	
	public static function get_resource_list($name, $params)
	{
		self::$api_token = $_REQUEST['api_token'];
		
		$start_time = microtime(true);
		
		$url = self::$api_url.$name.'/';
		if ($params)
		{
			$first_param = true;
			foreach ($params as $key => $value)
			{
				$url .= $first_param ? '?' : '&';
				$url .= $key . '=' . $value;
				$first_param = false;
			}
		}
		
		$curler = new CWT_Curler(
			$url,
			null,
			array(
				'Accept: application/xml',
				'Content-Type: application/xml',
				'Authorization: Basic '.base64_encode(self::$api_token.':x')
			)
		);
		
		$result = $curler->send_get();
		
		self::$debug['call_timers'][$name][] = microtime(true) - $start_time;
		
		return simplexml_load_string($result['content']);
	}
	
	public static function get_resource($name, $id)
	{
		self::$api_token = $_REQUEST['api_token'];
		
		$start_time = microtime(true);
		
		$curler = new CWT_Curler(
			self::$api_url.$name.'/'.$id.'/',
			null,
			array(
				'Accept: application/xml',
				'Content-Type: application/xml',
				'Authorization: Basic '.base64_encode(self::$api_token.':x')
			)
		);
		
		$result = $curler->send_get();
		
		self::$debug['call_timers'][$name][] = microtime(true) - $start_time;
		
		return simplexml_load_string($result['content']);
	}
	
	public static function post_resource($name, $params, $id = null)
	{
		self::$api_token = $_REQUEST['api_token'];
		
		$start_time = microtime(true);
		
		$url = self::$api_url.$name.'/';
		if ($id)
		{
			$url .= $id.'/';
		}
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<'.$name.'>';
		foreach ($params as $key => $value)
		{
			$xml .= '<'.$key.'>'.$value.'</'.$key.'>';
		}
		$xml .= '</'.$name.'>';
		
		$curler = new CWT_Curler(
			$url,
			null,
			array(
				'Accept: application/xml',
				'Content-Type: application/xml',
				'Authorization: Basic '.base64_encode(self::$api_token.':x')
			)
		);
		
		$result = $curler->send_post($xml);
		
		self::$debug['call_timers'][$name][] = microtime(true) - $start_time;
		
		return simplexml_load_string($result['content']);
	}
	
	/**
	 * Show how long each call took, grouped by resource
	 * Useful for identifing if you're doing lots of unnecessary calls
	 */
	public static function print_timers()
	{
		echo '<pre>';
		print_r(self::$debug['call_timers']);
		echo '</pre>';
	}
}

//EOF