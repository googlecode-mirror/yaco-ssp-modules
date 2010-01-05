<?php

/**
 * LDAP Attributes collector
 *
 * This class implements a collector that retrieves attributes from a LDAP
 * server.
 *
 * It has the following options:
 * - host: LDAP server host
 * - port: LDAP server port
 * - binddn: The username which should be used when connecting to the LDAP
 *           server.
 * - password: The password which should be used when connecting to the LDAP
 *             server.
 * - basedn:   DN to start the LDAP search
 * - attrlist: An associative array of [LDAP attr1 => atr1, LDAP attr2 => atr2]
 * - searchfilter: filter used to search the directory. You can use the special
 * :uidfield string to refer the value of the field specified as an uidfield in
 * the processor
 *
 * Example configuration:
 *
 * <code>
 * 'collector' => array(
 *		 'class' => 'attributecollector:LDAPCollector',
 *       'host' => 'myldap.srv',
 *		 'port' => 389,
 *		 'binddn' => 'cn=myuser',
 *		 'password' => 'yaco',
 *		 'basedn' => 'dc=my,dc=org',
 *		 'searchfilter' => 'uid=:uidfield',
 *		 'attrlist' => array(
 *			 // LDAP attr => real attr
 *			 'objectClass' => 'myClasses',
 *           ),
 *       ),
 * </code>
 */
class sspmod_attributecollector_Collector_LDAPCollector extends sspmod_attributecollector_SimpleCollector {


	/**
	 * Host and port to connect to
	 */
	private $host;
	private $port;

	/**
	 * Ldap Protocol
	 */
    private $protocol;

	/**
	 * Bind DN and password
	 */
	private $binddn;
	private $password;

	/**
	 * Base DN to search LDAP
	 */
	private $basedn;


	/**
	 * Attribute list to retrieve. Syntax: LDAPattr1 => Realattr1
	 */
	private $attrlist;

	/**
	 * Search filter
	 */
	private $searchfilter;

	/**
	 * LDAP handler
	 */
	private $ds;


	/* Initialize this collector.
	 *
	 * @param array $config	 Configuration information about this collector.
	 */
	public function __construct($config) {

		foreach (array('host', 'port', 'binddn', 'password', 'basedn', 'attrlist', 'searchfilter') as $id) {
			if (!array_key_exists($id, $config)) {
				throw new Exception('attributecollector:LDAPCollector - Missing required option \'' . $id . '\'.');
			}
			if ($id != 'port' && $id != 'attrlist' && !is_string($config[$id])) {
				throw new Exception('attributecollector:LDAPCollector - \'' . $id . '\' is supposed to be a string.');
			}
			if ($id == 'attrlist' && !is_array($config[$id])) {
				throw new Exception('attributecollector:LDAPCollector - \'' . $id . '\' is supposed to be an associative array.');
			}
		}

        if(!array_key_exists('protocol', $config)) {
            $this->protocol = 3;
        }
        else {
            $this->protocol = (integer)$config['protocol'];
        }

		$this->host = $config['host'];
		$this->port = $config['port'];
		$this->binddn = $config['binddn'];
		$this->password = $config['password'];
		$this->basedn = $config['basedn'];
		$this->attrlist = $config['attrlist'];
		$this->searchfilter = $config['searchfilter'];
	}


	/* Get collected attributes
	 *
	 * @param array $originalAttributes	 Original attributes existing before this collector has been called
	 * @param string $uidfield	Name of the field used as uid
	 * @return array  Attributes collected
	 */
	public function getAttributes($originalAttributes, $uidfield) {
		assert('array_key_exists($uidfield, $originalAttributes)');

		// Bind to LDAP
		$this->bindLdap();

		$retattr = array();

		$fetch = array_keys($this->attrlist);
		$userid = $originalAttributes[$uidfield][0];

		// Prepare filter
		$filter = preg_replace('/:uidfield/', $userid, 
				$this->searchfilter);

		$res = @ldap_search($this->ds, $this->basedn, $filter, $fetch);

		if ($res === FALSE) {
			// Problem with LDAP search
			throw new Exception('attributecollector:SQLCollector - LDAP Error when trying to fetch user attributes');
		}


		$info = @ldap_get_entries($this->ds, $res);
		if ($info['count'] > 0) {
			// Assign values
			foreach ($this->attrlist as $ldapa => $reala) {
				$ldapa_lc = strtolower($ldapa);
				if (isset($info[0][$ldapa_lc]) &&
						$info[0][$ldapa_lc]['count'] > 0) {
					unset ($info[0][$ldapa_lc]['count']);
					$retattr[$reala] = $info[0][$ldapa_lc];
				}
			}
		}

		return $retattr;

	}

	/**
	 * Connects and binds to the configured LDAP server. Stores LDAP
	 * handler in $this->ds
	 */
	private function bindLdap() {
		// Bind to LDAP
		$ds = ldap_connect($this->host, $this->port);
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $this->protocol);
		if (is_null($ds)) {
			throw new Exception('attributecollector:SQLCollector - Cannot connect to LDAP');
		}

		if (ldap_bind($ds, $this->binddn,
					$this->password) !== TRUE) {
            throw new Exception('attributecollector:SQLCollector - Cannot bind to LDAP');
		}

		$this->ds = $ds;
	}

}

?>
