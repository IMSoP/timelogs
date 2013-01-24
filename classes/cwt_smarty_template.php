<?php
require_once('Smarty/libs/Smarty.class.php');
class CWT_Smarty_Template extends Smarty 
{
	function CWT_Smarty_Template()
	{
		global $config;
		
		// Create a Smarty instance
		$this->Smarty();
		
		// Set up config directories
		$this->template_dir = $config['smarty']['template_dir'];
		$this->compile_dir = $config['smarty']['compile_dir'];
		$this->config_dir = $config['smarty']['config_dir'];
		$this->cache_dir = $config['smarty']['cache_dir'];
		$this->force_compile = $config['cache']['disable'];
		
		$this->security = true;
		
		// Throw in some additional PHP functions that are used by existing
		// templates - these will hopefully be replaced by registered
		// modifiers fairly soon (or a config var, etc), and this ugliness
		// can be removed
		$this->security_settings['MODIFIER_FUNCS'] = array_merge(
			$this->security_settings['MODIFIER_FUNCS'],
			array(
				'urlencode',        // -> use built-in {$foo|escape:url}
				'htmlspecialchars', // -> use built-in {$foo|escape:html}
				'strtolower',       // -> use built-in {$foo|lower}
				
				'array_slice',      // Useful, but along with the array-related custom
				                    // modifiers below, should possibly be replaced with 
				                    // something more task-specific
				
				'http_build_query'  // Can maybe be replaced with simpler params
				                    // pairs nicely with 'generate_hidden_form_elements' below
			)
		);
		
		// Register custom functions and modifiers
		// The callback can be function_name, array(class_name, method_name) or array(object, method_name)
		//	but NOT a pseudo-function name created by create_function()
		
		$this->register_modifier('round', 'round');
		$this->register_modifier('date', array('CWT_Smarty_Template', 'smarty_modifier_date'));
		
		// Allow the site to pass in extra functions / modifiers from config
		if ( is_array( $config['smarty']['extra_functions'] ) )
		{
			foreach ( $config['smarty']['extra_functions'] as $smarty_name => $callback )
			{
				$this->register_function($smarty_name, $callback);
			}
		}
		if ( is_array( $config['smarty']['extra_modifiers'] ) )
		{
			foreach ( $config['smarty']['extra_modifiers'] as $smarty_name => $callback )
			{
				$this->register_modifier($smarty_name, $callback);
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
