<?php
//	---- SITE-WIDE CODE TO LAUNCH AT START OF EACH PAGE EXECUTION ----
//            (please try to keep this code very clean and sensible)

require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/config/config.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/config/exceptions.php';

function tui_autoloader($class_name)
{
	$class_name = strtolower($class_name);
	// Probably unnecessary, but make sure the class name is safe
	if (!preg_match('/^[a-z_0-9]+$/', $class_name))
	{
		die('Invalid class name ('.$class_name.')');
	}
	if (file_exists(CONFIG_INCLUDES_SITE.'/'.$class_name.'.php')) {
    require_once CONFIG_INCLUDES_SITE.'/'.$class_name.'.php';
  }
}
spl_autoload_register('tui_autoloader');