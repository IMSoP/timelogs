<?php
//	---- SITE-WIDE CODE TO LAUNCH AT START OF EACH PAGE EXECUTION ----
//            (please try to keep this code very clean and sensible)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/exceptions.php';

if (!isset($config['time_adjustment'])) {
	$config['time_adjustment'] = 1;
}

require __DIR__ . '/../vendor/autoload.php';