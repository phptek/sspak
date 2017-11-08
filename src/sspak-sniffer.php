#!/usr/bin/env php
<?php

/**
 * SSPak Sniffer
 * Extract database and assets information from a SilverStripe site.
 */

// Argument parsing
if(empty($_SERVER['argv'][1])) {
	echo "Usage: {$_SERVER['argv'][0]} (site-docroot)\n";
	exit(1);
}

$basePath = $_SERVER['argv'][1];
if($basePath[0] != '/') $basePath = getcwd() . '/' . $basePath;

// SilverStripe bootstrap
define('BASE_PATH', $basePath);
if (!defined('BASE_URL')) {
	define('BASE_URL', '/');
}
$_SERVER['HTTP_HOST'] = 'localhost';
chdir(BASE_PATH);

if(file_exists(BASE_PATH.'/sapphire/core/Core.php')) {
	echo "Recognised a v2 system..." . PHP_EOL;
	//SS 2.x
	require_once(BASE_PATH . '/sapphire/core/Core.php');
} else if(file_exists(BASE_PATH.'/framework/core/Core.php')) {
	echo "Recognised a v3 system..." . PHP_EOL;
	//SS 3.x
	require_once(BASE_PATH. '/framework/core/Core.php');
} else if(file_exists(BASE_PATH.'/vendor/silverstripe/framework/src/Core/CoreKernel.php')) {
	echo "Recognised a v4 system..." . PHP_EOL;
	//SS 4.x
	require_once(BASE_PATH. '/vendor/autoload.php');
	require_once(BASE_PATH. '/vendor/silverstripe/framework/src/Core/CoreKernel.php');
} else {
	echo "Unrecognised system..." . PHP_EOL;
	echo "Couldn't locate framework's Core.php. Perhaps " . BASE_PATH . " is not a SilverStripe project?\n";
	exit(2);
}

$output = array();
foreach($databaseConfig as $k => $v) {
	$output['db_' . $k] = $v;
}
$output['assets_path'] = ASSETS_PATH;

echo serialize($output);
echo "\n";
