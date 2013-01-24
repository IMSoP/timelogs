<?php
class CWT_Exception extends Exception
{
	const EX_UNKNOWN = 0;
	
	private $parent_exception;
	
	/**
	 * NOTE: CWT_Exceptions historically take the parent exception as *first* parameter,
	 * 	but PHP 5.3 has an Exception constructor with it as *third* parameter
	 * This will attempt to work out which you mean:
	 *
	 * @param string|Exception $message or $parent_exception
	 * @param int|string $code if string passed as param 1, $message otherwise
	 * @param Exception|int $parent_exception if string passed as param 1, $code otherwise
	 */
	public function __construct(/* over-loaded args */) 
	{
		$args = func_get_args();
		
		if ( is_string($args[0]) )
		{
			/* PHP 5.3: __construct($message = null, $code = 0, Exception $parent_exception = null) */
			list($message, $code, $parent_exception) = $args;
		}
		else
		{
			/* CWT-style: __construct(Exception $parent_exception = null, $message = null, $code = 0) */
			list($parent_exception, $message, $code) = $args;
		}
			
		if ( $parent_exception instanceof Exception )
		{
			$this->parent_exception = $parent_exception;
		}
		
		parent::__construct($message, $code);
		// PHP 5.3: parent::__construct($message, $code, $parent_exception);
	}
	
	public function get_parent()
	{
		return $this->parent_exception;
	}
	
	public function get_summary()
	{
		return strtoupper(get_class($this))
			. '::' . $this->get_code_constant_name()
			. ' ("' .$this->get_message() . '")';
	}
	
	/**
	 * Use reflection to get the constant name for the numeric code set in this class
	 */
	public function get_code_constant_name()
	{
		if ( ! class_exists('ReflectionClass') )
		{
			return "[UNKNOWN:{$this->getCode()}]";
		}
		
		// Find all constants in this class
		$reflect = new ReflectionClass($this);
		$constants = $reflect->getConstants();
		// Find relevant constant(s)
		$code_lookups = array_keys($constants, $this->getCode());
		
		// Handle ambiguity and other confusion
		if ( count($code_lookups) > 1 )
		{
			return '[AMBIGUOUS:'.implode(' or ', $code_lookups).']';
		}
		elseif ( count($code_lookups) == 1 )
		{
			return $code_lookups[0];
		}
		else
		{
			return "[UNKNOWN:{$this->getCode()}]";
		}
	}
	
	public function dump_backtrace($show = null)
	{
		// Be quiet in live mode
		if ( is_null($show) )
		{
			$show = (
				$GLOBALS['config']['site_mode'] != 'D'
				|| $GLOBALS['config']['site_mode'] != 'S'
			);
		}
		
		$out = '';
		
		// Output basic exception info
		$out .= $this->get_summary() . PHP_EOL;
		
		// Prettify our trace info
		$backtrace = $this->getTrace();
	
		$out .= "Backtrace: \n";
		// PHP's backtraces always seem off-by-one; in this case, the line that threw the exception is not considered part of the trace
		$out .= "\t" . auto_relative_path($this->getFile())
			. " on line {$this->getLine()}"
			. ' (' . $backtrace[0]['class'] . $backtrace[0]['type'] . $backtrace[0]['function'] . ')'
			. ", \n";
		foreach ($backtrace as $trace_no => $trace_entry)
		{
			$out .= "\t" . auto_relative_path($trace_entry['file'])
				. " on line {$trace_entry['line']}"
				. ' (' . $backtrace[$trace_no+1]['class'] . $backtrace[$trace_no+1]['type'] . $backtrace[$trace_no+1]['function'] . ')'
				. ", \n";
				
		}
		$out .= PHP_EOL;
		
		// Be recursive
		$parent = $this->get_parent();
		if($parent instanceof CWT_Exception)
		{
			$out .= '[Parent] ' . $parent->dump_backtrace('RETURN');
		}
		elseif($parent instanceof Exception)
		{
			$out .= '[Parent] ' . (string)$parent;
		}
		
		// Show it in an appropriate form
		if ( $show === 'RETURN' )
		{
			return $out;
		}
		elseif ($show) 
		{
			echo	'<pre>' . 
				htmlspecialchars($out) . 
				"</pre>\n";
		}
		else
		{
			echo
			'<!--' . 
			htmlspecialchars($out) . 
			"-->\n";
		}
	}
	
	public function __call($name, $arguments)
	{
		if ( substr($name, 0, 4) == 'get_' )
		{
			$field = substr($name, 4, strlen($name) - 4);
			
			if (property_exists($this, $field))
			{
				return $this->$field;
			}
			else
			{
				trigger_error('Property "' . $field . '" does not exist on ' . get_class($this));
			}
		}
	}
}

/**
 * Any errors encountered by the Curler class
 */
class Curler_Exception extends CWT_Exception
{
	const EX_CANT_INITIALISE = 6000;
}
