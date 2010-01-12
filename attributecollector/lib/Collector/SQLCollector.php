<?php

/**
 * SQL Attributes Collector
 *
 * This class implements a collector that retrieves attributes from a database.
 * It shoud word against both MySQL and PostgreSQL
 *
 * It has the following options:
 * - dsn: The DSN which should be used to connect to the database server. Check the various
 *		  database drivers in http://php.net/manual/en/pdo.drivers.php for a description of
 *		  the various DSN formats.
 * - username: The username which should be used when connecting to the database server.
 * - password: The password which should be used when connecting to the database server.
 * - query: The sql query for retrieve attributes. You can use the special :uidfield string
 *			to refer the value of the field especified as an uidfield in the processor.
 *
 *
 * Example - with PostgreSQL database:
 * <code>
 * 'collector' => array(
 *		 'class' => 'attributecollector:SQLCollector',
 *		 'dsn' => 'pgsql:host=localhost;dbname=simplesaml',
 *		 'username' => 'simplesaml',
 *		 'password' => 'secretpassword',
 *		 'query' => 'select address, phone, country from extraattributes where uid=:uidfield',
 *		 ),
 *	   ),
 *	 ),
 * </code>
 */
class sspmod_attributecollector_Collector_SQLCOllector extends sspmod_attributecollector_SimpleCollector {


	/**
	 * DSN for the database.
	 */
	private $dsn;


	/**
	 * Username for the database.
	 */
	private $username;


	/**
	 * Password for the database;
	 */
	private $password;


	/**
	 * Query for retrieving attributes
	 */
	private $query;


	/**
	 * Database handle.
	 *
	 * This variable can't be serialized.
	 */
	private $db;


    /**
     * Attribute name case.
     *
     * This is optional and by default is "natural"
     */
    private $attrcase;


	/* Initialize this collector.
	 *
	 * @param array $config	 Configuration information about this collector.
	 */
	public function __construct($config) {

		foreach (array('dsn', 'username', 'password', 'query') as $id) {
			if (!array_key_exists($id, $config)) {
				throw new Exception('attributecollector:SQLCollector - Missing required option \'' . $id . '\'.');
			}
			if (!is_string($config[$id])) {
				throw new Exception('attributecollector:SQLCollector - \'' . $id . '\' is supposed to be a string.');
			}
		}

		$this->dsn = $config['dsn'];
		$this->username = $config['username'];
		$this->password = $config['password'];
		$this->query = $config['query'];

        $case_options = array ("lower" => PDO::CASE_LOWER,
                               "natural" => PDO::CASE_NATURAL,
                               "upper" => PDO::CASE_UPPER);
        // Default is 'natural'
        $this->attrcase = $case_options["natural"];
        if (array_key_exists("attrcase", $config)) {
            $attrcase = $config["attrcase"];
            if (in_array($attrcase, array_keys($case_options))) {
                $this->attrcase = $case_options[$attrcase];
            } else {
                throw new Exception("attributecollector:SQLCollector - Wrong case value: '" . $attrcase . "'");
            }
        }
	}


	/* Get collected attributes
	 *
	 * @param array $originalAttributes	 Original attributes existing before this collector has been called
	 * @param string $uidfield	Name of the field used as uid
	 * @return array  Attributes collected
	 */
	public function getAttributes($originalAttributes, $uidfield) {
		assert('array_key_exists($uidfield, $originalAttributes)');
		$db = $this->getDB();
		$st = $db->prepare($this->query);
		$res = $st->execute(array('uidfield' => $originalAttributes[$uidfield][0]));

                $db_res = $st->fetchAll(PDO::FETCH_ASSOC);

                $result = array();
                foreach($db_res as $tuple) {
                    foreach($tuple as $colum => $value) {
                        $result[$colum][] = $value;
                    }
                }
                foreach($result as $colum => $data) {
                    $result[$colum] = array_unique($data);
                }

                return $result;
	}


	/**
	 * Get database handle.
	 *
	 * @return PDO|FALSE  Database handle, or FALSE if we fail to connect.
	 */
	private function getDB() {
		if ($this->db !== NULL) {
			return $this->db;
		}

		$this->db = new PDO($this->dsn, $this->username, $this->password);
        $this->db->setAttribute(PDO::ATTR_CASE, $this->attrcase);
		return $this->db;
	}
}

?>
