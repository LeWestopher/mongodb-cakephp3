<?php

namespace Hayko\Mongodb\Database\Schema;

use Hayko\Mongodb\Database\Driver\Mongodb;
use Cake\Database\Schema\Table;

class MongoSchema {

	/**
	 * Database Connection
	 *
	 * @var resource
	 * @access protected
	 */
		protected $_connection = null;

	/**
	 * Constructor
	 * 
	 * @param ConnectionInterface $conn
	 * @return void
	 * @access public
	 */
		public function __construct(Mongodb $conn) {
			$this->_connection = $conn;
		}

	/**
	 * Describe
	 *
	 * @access public
	 */
		public function describe($name, array $options = []) {
			$config = $this->_connection->config();
			if (strpos($name, '.')) {
				list($config['schema'], $name) = explode('.', $name);
			}

			$table = new Table(['table' => $name]);

			if(empty($table->primaryKey())) {
				$table->addColumn('_id', ['type' => 'string', 'default' => new \MongoId(), 'null' => false]);
				$table->addConstraint('_id', ['type' => 'primary', 'columns' => ['_id']]);
			}

			return $table;
		}

}