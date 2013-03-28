<?php
/**
 * OO wrapper for our existing Curl calls. Also supports maintaining cookies
 * across multiple requests.
 */
class TUI_Curler
{
	private $url;
	
	private $curl;
	private $captured_headers;
	private $cookie_jar;
	
	/**
	 * @param string|null $url Pre-set URL (otherwise you MUST call set_url())
	 * @param int|null $timeout Overall timeout; see set_timeout()
	 * @param array|null $headers Additional HTTP headers; see set_headers()
   * @param string|null $basic_auth Basic authentication credentials; Format as username:password
	 *
	 * @throws CWT_Curler_Exception::EX_CANT_INITIALISE
	 */
	public function __construct($url=null, $timeout=null, $headers=null, $basic_auth=null)
	{
		global $config;
		
		$this->curl = curl_init();
		$this->reset_captured_headers();
		
		if(!$this->curl)
		{
			throw new CWT_Curler_Exception(null, 'Could not initialise Curl', CWT_Curler_Exception::EX_CANT_INITIALISE);
		}
		
    // Using older version of SSL because of incompatibility between OpenSSH server/client when using TLS
    curl_setopt($this->curl, CURLOPT_SSLVERSION, 3);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_USERAGENT, 
			cwt::coalesce($config['curler']['user_agent'], 'CWT Curler Library')
		);
    if ($basic_auth) {
      curl_setopt($this->curl, CURLOPT_USERPWD, $basic_auth);
    }
		
		$this->set_timeout($timeout, null);
		
		if (!is_null($url))
		{
			$this->set_url($url);
		}
		if ( $config['curler']['cookie_directory'] )
		{
			$this->set_cookie_directory($config['curler']['cookie_directory']);
		}
		if ( is_array($headers) )
		{
			$this->set_headers($headers);
		}
		
		curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, 'curl_header_handler'));
	}
	
	/**
	 * Trivial static version of constructor, to allow quick chained calls
	 *	e.g. $response = Tui_Curler::create($url)->send_get();
	 * @throws CWT_Curler_Exception::EX_CANT_INITIALISE
	 */
	public static function create($url=null, $timeout=null, $headers=null)
	{
		return new self($url, $timeout, $headers);
	}
	
	public function __destruct()
	{
		// Release the CURL resource
		curl_close($this->curl);
		
		// Don't leave cookie crumbs all over our nice clean servers
		if ( isset($this->cookie_jar) && file_exists($this->cookie_jar) )
		{
			unlink($this->cookie_jar);
		}
	}
	
	public function set_url($url)
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		
		return $this;
	}
	
	/**
	 * Set additional HTTP request headers
	 * Note that calling this multiple times will discard previous values, not append to them
	 *
	 * @param array $in_headers Either:
	 *	- a list of prepared header strings; e.g. array('Content-Type: application/xml')
	 *	- OR an associative array of header=>value pairs;  e.g. array('Content-Type' => 'application/xml')
	 */
	public function set_headers(Array $in_headers)
	{
		$prepared_headers = array();
		
		// Allow passing of an associative array of headers,
		//	because that's what I expected to work in the first place
		foreach ( $in_headers as $key => $value )
		{
			if ( is_int($key) )
			{
				$prepared_headers[] = $value;
			}
			else
			{
				$prepared_headers[] = "$key: $value";
			}
		}
		
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $prepared_headers);
		
		return $this;
	}
	
	/**
	 * @param int $overall_timeout The maximum number of seconds to allow cURL functions to execute.
	 * @param int|null $connect_timeout The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
	 *	If $connect_timeout is NULL (and not set in config), $overall_timeout will be used for both settings
	 */
	public function set_timeout($overall_timeout, $connect_timeout = null)
	{
		global $config;
		
		curl_setopt($this->curl, CURLOPT_TIMEOUT,        
			cwt::coalesce($overall_timeout, $config['curler']['default_timeout'])
		);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT,
			cwt::coalesce(
				$connect_timeout, $config['curler']['default_connect_timeout'],
				$overall_timeout, $config['curler']['default_timeout']
			)
		);
		
		return $this;
	}
	
	public function set_cookie_directory($cookie_directory)
	{
		$this->cookie_jar = tempnam($cookie_directory, 'cookie_');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR,      $this->cookie_jar);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE,     $this->cookie_jar);
		
		return $this;
	}
	
	/**
	 * Enable use of an HTTP proxy
	 *
	 * @param string $proxy_address Complete address of proxy; can include port, username and password
	 *	e.g. 'user:pass@proxy.example.com:8080'
	 * @return Tui_Curler Chainable
	 */
	public function set_proxy_server($proxy_address)
	{
		curl_setopt($this->curl, CURLOPT_PROXY, $proxy_address);
		
		return $this;
	}
	
	/**
	 * @param string|array $post Post data - either urlencoded string, or associative array 
	 * 	(see {@link http://uk.php.net/manual/en/function.curl-setopt.php curl_setopt manual page})
	 * @return array(content, headers, error, file_size, response_time)
	 */
	public function send_post($post)
	{
		$return = array();
		
		curl_setopt($this->curl, CURLOPT_POST,       1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
		
		$start = microtime(true);
		
		$this->reset_captured_headers();
		
		$return['content']       = curl_exec ($this->curl);
		$return['headers']       = $this->captured_headers;
		$return['error']         = curl_error($this->curl);
		$return['file_size']     = strlen($return['content']);
		$return['response_time'] = round(microtime(true) - $start, 3);
		
		return $return;
	}
	
	public function send_put($put_string)
	{
		$return = array();
		
		$put_file = tmpfile();
		fwrite($put_file, $put_string);
		fseek($put_file, 0); 
		
		curl_setopt($this->curl, CURLOPT_PUT,  true);
		curl_setopt($this->curl, CURLOPT_INFILE, $put_file);
		curl_setopt($this->curl, CURLOPT_INFILESIZE, strlen($put_string)); 
		
		$start = microtime(true);
		
		$this->reset_captured_headers();
		
		$return['content']       = curl_exec ($this->curl);
		$return['headers']       = $this->captured_headers;
		$return['error']         = curl_error($this->curl);
		$return['file_size']     = strlen($return['content']);
		$return['response_time'] = round(microtime(true) - $start, 3);
		
		fclose($put_file);
		
		return $return;
	}
	
	/**
	 * @return array(content, headers, error, file_size, response_time)
	 */
	public function send_get()
	{
		$return = array();
		
		// Although GET is the default, the handle may have been used for a POST/etc request
		curl_setopt($this->curl, CURLOPT_HTTPGET,       1);
		
		$this->reset_captured_headers();
		
		$start = microtime(true);
		
		$return['content']       = curl_exec ($this->curl);
		$return['headers']       = $this->captured_headers;
		$return['error']         = curl_error($this->curl);
		$return['file_size']     = strlen($return['content']);
		$return['response_time'] = round(microtime(true) - $start, 3);
		
		return $return;
	}
	
	private function reset_captured_headers()
	{
		$this->captured_headers = array();
	}
	
	/**
	 * This all came from the existing curl_ methods in WFE. It looks like it's
	 * just for cleaning up HTTP headers for easier associative-array access.
	 * 
	 * @param $ch
	 * @param $header
	 * @return unknown_type
	 */
	private function curl_header_handler($ch, $header)
	{
		$matches = array();
		
		if ( preg_match('/^([^:]+)\s*:\s*([^\x0D\x0A]*)\x0D?\x0A?$/', $header, $matches) )
		{
			$this->captured_headers[$matches[1]][] = $matches[2];
		}
		
		elseif (preg_match('/^HTTP\/1\.\d (\d{3})([^\x0D\x0A]*)\x0D?\x0A?$/i', $header, $matches))
		{
			$this->captured_headers['HTTP-Status'] = $matches[1];
		}
		
		return strlen($header);
	}
}
