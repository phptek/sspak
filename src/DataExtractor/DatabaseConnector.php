<?php

namespace SilverStripe\SsPak\DataExtractor;

use DB;

/**
 * Connects to the SilverStripe Database object of a given SilverStripe project,
 * in order to bulk save/load data
 */
class DatabaseConnector
{

	private $basePath;
	private $isConnected = false;

	public function __construct($basePath) {
		$this->basePath = $basePath;
	}

	public function connect() {
		if ($this->isConnected) {
			return;
		}

		$this->isConnected = true;

		// Necessary for SilverStripe's _ss_environment.php loader to work
		$_SERVER['SCRIPT_FILENAME'] = $this->basePath . '/dummy.php';

		global $databaseConfig;

		// require composers autoloader
		if (file_exists($this->basePath . '/vendor/autoload.php')) {
			require_once $this->basePath . '/vendor/autoload.php';
		}

		// v4
		if (file_exists($this->basePath . '/vendor/silverstripe/framework/src/Core/CoreKernel.php')) {
			echo "Recognised a v4 system..." . PHP_EOL;
	
			use SilverStripe\Control\HTTPApplication;
			use SilverStripe\Control\HTTPRequest;
			use SilverStripe\Core\CoreKernel;

			if (!file_exists($autoloadPath = BASE_PATH. '/vendor/autoload.php')) {
	    			exit;
			}

			require_once $autoloadPath;

			// Mock request
			$request = new HTTPRequest('GET', '/');
			$request->setSession(new Session([])));
			$kernel = new CoreKernel(BASE_PATH);
			$app = new HTTPApplication($kernel);
	
			$app->execute($request, function (HTTPRequest $request) {
				global $databaseConfig;
				$output = [];

				foreach($databaseConfig as $k => $v) {
		    			$output['db_' . $k] = $v;
				}

				$output['assets_path'] = ASSETS_PATH;

				echo serialize($output);
				echo PHP_EOL;		
			});
		}
		// v3
		elseif (file_exists($this->basePath . '/framework/core/Core.php')) {
			echo "Recognised a v3 system..." . PHP_EOL;
			require_once($this->basePath . '/framework/core/Core.php');
		// v2
		} elseif (file_exists($this->basePath . '/sapphire/core/Core.php')) {
			echo "Recognised a v2 system..." . PHP_EOL;
			require_once($this->basePath . '/sapphire/core/Core.php');
		} else {
			echo "No system recognised..." . PHP_EOL;
			throw new \LogicException("No Core file included in project. Perhaps $this->basePath is not a SilverStripe project?");
		}

		// Connect to database
		require_once('model/DB.php');

		if ($databaseConfig) {
			DB::connect($databaseConfig);
		} else {
			throw new \LogicException("No \$databaseConfig found");
		}
	}

	public function getDatabase() {
		$this->connect();

		if(method_exists('DB', 'get_conn')) {
			return DB::get_conn();
		} else {
			return DB::getConn();
		}
	}

	/**
	 * Get a list of tables from the database
	 */
	public function getTables() {
		$this->connect();

		if(method_exists('DB', 'table_list')) {
			return DB::table_list();
		} else {
			return DB::tableList();
		}
	}

	/**
	 * Get a list of tables from the database
	 */
	public function getFieldsForTable($tableName) {
		$this->connect();

		if(method_exists('DB', 'field_list')) {
			return DB::field_list($tableName);
		} else {
			return DB::fieldList($tableName);
		}
	}

	/**
	 * Save the named table to the given table write
	 */
	public function saveTable($tableName, TableWriter $writer) {
		$query = $this->getDatabase()->query("SELECT * FROM \"$tableName\"");

		foreach ($query as $record) {
			$writer->writeRecord($record);
		}

		$writer->finish();
	}

	/**
	 * Save the named table to the given table write
	 */
	public function loadTable($tableName, TableReader $reader) {
		$this->getDatabase()->clearTable($tableName);

		$fields = $this->getFieldsForTable($tableName);

		foreach($reader as $record) {
			foreach ($record as $k => $v) {
				if (!isset($fields[$k])) {
					unset($record[$k]);
				}
			}
			// TODO: Batch records
			$manipulation = [
				$tableName => [
					'command' => 'insert',
					'fields' => $record,
				],
			];
			DB::manipulate($manipulation);
		}
	}
}
