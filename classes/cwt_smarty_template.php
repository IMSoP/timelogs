<?php
require_once('Smarty/libs/Smarty.class.php');
//require_once('Smarty/libs/sysplugins/smarty_security.php');
class CWT_Smarty_Template extends Smarty 
{
	function CWT_Smarty_Template()
	{
		global $config;

		// Create a Smarty instance
		$this->__construct();
		
		// Set up config directories
		$this->template_dir = $config['smarty']['template_dir'];
		$this->compile_dir = $config['smarty']['compile_dir'];
		$this->config_dir = $config['smarty']['config_dir'];
		$this->cache_dir = $config['smarty']['cache_dir'];
		$this->force_compile = $config['cache']['disable'];
		

    $this->enableSecurity('Tui_Smarty_Security_Policy');

		// Register custom functions and modifiers
		// The callback can be function_name, array(class_name, method_name) or array(object, method_name)
		//	but NOT a pseudo-function name created by create_function()
		
		$this->registerPlugin('modifier', 'round', 'round');
		$this->registerPlugin('modifier', 'date', array('CWT_Smarty_Template', 'smarty_modifier_date'));
		
		// Allow the site to pass in extra functions / modifiers from config
		if ( is_array( $config['smarty']['extra_functions'] ) )
		{
			foreach ( $config['smarty']['extra_functions'] as $smarty_name => $callback )
			{
				$this->registerPlugin('function', $smarty_name, $callback);
			}
		}
		if ( is_array( $config['smarty']['extra_modifiers'] ) )
		{
			foreach ( $config['smarty']['extra_modifiers'] as $smarty_name => $callback )
			{
				$this->registerPlugin('modifier', $smarty_name, $callback);
			}
		}
	}
	
	/**
	* @param string $template Name of the template, without a file extension
	* @param array $variables
	*/
	function parse($template, $variables = array())
	{
		global $config;
		
		if (is_array($config['smarty']['template_overrides']))
		{
			$template = coalesce(
				$config['smarty']['template_overrides'][$template],
				$template
			);
		}
		
		foreach ($variables as $key => $value)
		{
			$this->assign($key, $value);
		}
		
		return $this->fetch($template.'.tpl');
	}
	
	static function quick_parse($template, $variables=array())
	{
		$smarty = new CWT_Smarty_Template();
		return $smarty->parse($template, $variables);
	}
	
	/**
	 * Allow sites to override the common modifiers using $config
	 * 
	 * @param string Modifier name $smarty_name
	 * @param callback Modifier callback $callback
	 */
	function register_modifier($smarty_name, $callback)
	{
		global $config;
		
		$callback = CWT::coalesce(
			$config['smarty']['extra_modifiers'][$smarty_name],
			$callback
		);
		
		parent::register_modifier($smarty_name, $callback);
	}
	
	/* Custom Functions and Modifiers */
	
	function smarty_modifier_date($timestamp, $format = 'jS M Y')
	{
		return date($format, $timestamp);
	}
}

?>
