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

// Fudge some env vars
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

chdir(BASE_PATH);

if(file_exists(BASE_PATH.'/vendor/silverstripe/framework/src/Core/CoreKernel.php')) {
	//SS 4.x
	if (!file_exists($autoloadPath = BASE_PATH. '/vendor/autoload.php')) {
	    exit;
	}

	require_once $autoloadPath;

	if (!file_exists(BASE_PATH . DIRECTORY_SEPARATOR . '.env')) {
	    exit;
	}

        // Build request and detect flush
        $request = SilverStripe\Control\HTTPRequestBuilder::createFromEnvironment();
	$kernel = new SilverStripe\Core\CoreKernel(BASE_PATH);
	$app = new SilverStripe\Control\HTTPApplication($kernel);
	$app->addMiddleware(new SilverStripe\Core\Startup\ErrorControlChainMiddleware($app));
	
	$app->execute($request, function ($request) {
		$env = new SilverStripe\Core\Environment();
		$output = [
			'db_type' => $env::getEnv('SS_DATABASE_CLASS'),
			'db_username' => $env::getEnv('SS_DATABASE_USERNAME'),
			'db_password' => $env::getEnv('SS_DATABASE_PASSWORD'),
			'db_server' => $_SERVER['HTTP_HOST'], // Taken from top of this file
			'db_database' => $env::getEnv('SS_DATABASE_NAME'),
			'assets_path' => ASSETS_PATH
		];

		echo serialize($output);
		echo PHP_EOL;		
	});

	exit;

} else if(file_exists(BASE_PATH.'/sapphire/core/Core.php')) {
	//SS 2.x
	require_once(BASE_PATH . '/sapphire/core/Core.php');
} else if(file_exists(BASE_PATH.'/framework/core/Core.php')) {
	//SS 3.x
	require_once(BASE_PATH. '/framework/core/Core.php');
} else if(file_exists(BASE_PATH.'/framework/src/Core/Core.php')) {
	//SS 4.x
	require_once(BASE_PATH. '/vendor/autoload.php');
	require_once(BASE_PATH. '/framework/src/Core/Core.php');
} else {
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
