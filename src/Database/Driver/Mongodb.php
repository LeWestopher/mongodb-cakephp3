<?php

namespace Hayko\Mongodb\Database\Driver;

class Mongodb {

	/**
	 * Config
	 * 
	 * @var array
	 * @access private
	 */
		private $_config;

	/**
	 * Are we connected to the DataSource?
	 *
	 * true - yes
	 * false - nope, and we can't connect
	 *
	 * @var boolean
	 * @access public
	 */
		public $connected = false;

	/**
	 * Database Instance
	 *
	 * @var resource
	 * @access protected
	 */
		protected $_db = null;

	/**
	 * Mongo Driver Version
	 *
	 * @var string
	 * @access protected
	 */
		protected $_driverVersion = \Mongo::VERSION;

	/**
	 * Base Config
	 *
	 * set_string_id:
	 *    true: In read() method, convert MongoId object to string and set it to array 'id'.
	 *    false: not convert and set.
	 *
	 * @var array
	 * @access public
	 *
	 */
		protected $_baseConfig = [
			'set_string_id' => true,
			'persistent' => true,
			'host'       => 'localhost',
			'database'   => '',
			'port'       => 27017,
			'login'		=> '',
			'password'	=> '',
			'replicaset'	=> '',
		];

	/**
	 * Direct connection with database
	 *
	 * @var mixed null | Mongo
	 * @access private
	 */
		private $connection = null;

	/**
	 * 
	 */
		public function __construct($config) {
			$this->_config = $config;
		}

	/**
	 * return configuration
	 * 
	 * @return array
	 * @access public
	 */
		public function config() {
			return $this->_config;
		}

	/**
	 * connect to the database
	 * 
	 * @return boolean
	 * @access public
	 */
		public function connect() {
			try {
				
				$host = $this->createConnectionName();
				$class = '\MongoClient';
				if (!class_exists($class)) {
					$class = '\Mongo';
				}

				if (isset($this->_config['replicaset']) && count($this->_config['replicaset']) === 2) {
					$this->connection = new $class($this->_config['replicaset']['host'], $this->_config['replicaset']['options']);
				} else if ($this->_driverVersion >= '1.3.0') {
					$this->connection = new $class($host);
				} else if ($this->_driverVersion >= '1.2.0') {
					$this->connection = new $class($host, array("persist" => $this->_config['persistent']));
				} else {
					$this->connection = new $class($host, true, $this->_config['persistent']);
				}

				if (isset($this->_config['slaveok'])) {
					if (method_exists($this->connection, 'setSlaveOkay')) {
						$this->connection->setSlaveOkay($this->_config['slaveok']);
					} else {
						$this->connection->setReadPreference($this->_config['slaveok']
							? $class::RP_SECONDARY_PREFERRED : $class::RP_PRIMARY);
					}
				}

				if ($this->_db = $this->connection->selectDB($this->_config['database'])) {
					if (!empty($this->_config['login']) && $this->_driverVersion < '1.2.0') {
						$return = $this->_db->authenticate($this->_config['login'], $this->_config['password']);
						if (!$return || !$return['ok']) {
							trigger_error('MongodbSource::__construct ' . $return['errmsg']);
							return false;
						}
					}
					$this->connected = true;
				}

			} catch (MongoException $e) {
				trigger_error($e->getMessage());
			}

			return $this->connected;
		}

	/**
	 * create connection string
	 * 
	 * @access private
	 * @return string
	 */
		private function createConnectionName() {
			$host = '';

			if ($this->_driverVersion >= '1.0.2') {
				$host = 'mongodb://';
			}
			$hostname = $this->_config['host'] . ':' . $this->_config['port'];

			if (!empty($this->_config['login'])) {
				$host .= $this->_config['login'] . ':' . $this->_config['password'] . '@' . $hostname . '/' . $this->_config['database'];
			} else {
				$host .= $hostname;
			}

			return $host;
		}

	/**
	 * return MongoCollection object
	 * 
	 * @param string $collectionName
	 * @return \MongoCollection
	 * @access public
	 */
		public function getCollection($collectionName = '') {
			if (!empty($collectionName)) {
				if (!$this->isConnected()) {
					$this->connect();
				}

				return new \MongoCollection($this->_db, $collectionName);
			}
			return false;
		}

	/**
	 * disconnect from the database
	 * 
	 * @return boolean
	 * @access public
	 */
		public function disconnect() {
			if ($this->connected) {
				$this->connected = !$this->connection->close();
				unset($this->_db, $this->connection);
				return !$this->connected;
			}
			return true;
		}

	/**
	 * database connection status
	 * 
	 * @return booelan
	 * @access public
	 */
		public function isConnected() {
			return $this->connected;
		}

}