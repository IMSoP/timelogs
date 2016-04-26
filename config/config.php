<?php
//	---- CLIENT-SPECIFIC CONFIGURATION ----
define('CONFIG_INCLUDES_ROOT', dirname(__DIR__));

//// APPLICATION CONFIG ////

// Non-Secure URL
$config['base_url'] = $_SERVER['cwt_base_url'];

// You will need to get a key for your hostname from http://code.google.com/apis/loader/signup.html
$config['google_api_key'] = $_SERVER['cwt_google_api_key'];

define('CONFIG_INCLUDES_CACHE', CONFIG_INCLUDES_ROOT . '/cache');

// Directory for templates for this site - NO trailing slash!
define('CONFIG_INCLUDES_TEMPLATES', CONFIG_INCLUDES_ROOT . '/templates');

// Directory for templates for this site - NO trailing slash!
define('CONFIG_INCLUDES_CONFIG', CONFIG_INCLUDES_ROOT . '/config');

// Directory for HTML includes for this site (can contain PHP code) - NO trailing slash!
define('CONFIG_INCLUDES_HTML', CONFIG_INCLUDES_TEMPLATES . '/php');

define('CONFIG_INCLUDES_SITE', CONFIG_INCLUDES_ROOT . '/classes');

$config['smarty']['template_dir'] = CONFIG_INCLUDES_TEMPLATES . '/smarty/';
$config['smarty']['compile_dir'] = CONFIG_INCLUDES_CACHE . '/smarty_templates/';
$config['smarty']['config_dir'] = CONFIG_INCLUDES_CONFIG . '/smarty/';
$config['smarty']['cache_dir'] = CONFIG_INCLUDES_CACHE . '/smarty_output/';
$config['smarty']['extra_modifiers'] = array (
);

// Automatically multiple time by this amount, e.g. to take account of management hours. 1.2 is a 20% increase.
$config['time_adjustment'] = 1.2;

// Useful to exclude bank holidays etc
$config['non-work_dates'] = array(
	'2011-08-29',
	'2011-12-26',
	'2011-12-27',
	'2012-01-02',
	'2012-04-06',
	'2012-04-09',
	'2012-05-07',
	'2012-06-04',
	'2012-06-05',
	'2012-08-27',
	'2012-12-25',
	'2012-12-26',
);

define('HOURS_PER_DAY', intval($_GET['daily_hours']) ? intval($_GET['daily_hours']) : 6);

// Specify different JIRA instances for each project, keyed by the uppercase project key. Falls back to 'default'
$config['jira_instance']['default'] = 'https://expedu.atlassian.net';
//$config['jira_instance']['BAU'] = 'https://expedu.atlassian.net';
//$config['jira_instance']['DOMINO'] = 'https://expedu.atlassian.net';
//$config['jira_instance']['TOOLKIT'] = 'https://expedu.atlassian.net';
