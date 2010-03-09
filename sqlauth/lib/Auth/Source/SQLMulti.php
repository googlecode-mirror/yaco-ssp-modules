<?php

/**
 * SQL authentication source.
 *
 * This class is based on www/auth/login.php.
 *
 * This module can be used if your organization has seperate groups with
 * seperate BD or seperate BD domains.
 *
 * When login the user will select a source from a set of them and module
 * will authenticate the user against it. 
 *
 * To use this authentication module,
 * open `config/authsources.php` in a text editor, and add an entry which
 * uses this module:
 *
 * 'example-sqlmulti' => array(
 *		'authsql:SQLMulti',
 *
 *			'username_organization_method' => 'none',
 *			'include_organization_in_username' => FALSE,
 *			'sources' => array (
 *				'employes' => array (
 *					'description' => array(
 *						'en' => 'Employees',
 *						'no' => 'Ansatte',
 *					),
 *					'dsn' => 'pgsql:host=sql.example.org;port=5432;dbname=employes',
 *   					'username' => 'userdb',
 *					'password' => 'secretpassword',
 *					'encrypt' => 'md5',
 *   					'query' => 'SELECT username, name, email FROM users WHERE username = :username AND password = :password',
 *				),
 *				'students' => array (
 *					'description' => 'Students',
 *					'dsn' => 'pgsql:host=sql.example.org;port=5432;dbname=students',
 *                                      'username' => 'userdb',
 *                                      'password' => 'secretpassword',
 *					'encrypt' => 'sha1',
 *                                      'query' => 'SELECT username, name, email FROM users WHERE username = :username AND password = :password',	
 *				),
 *
 *
 * @username_organization_method
 *
 * The way the organization as part of the username should be handled.
 * Three possible values:
 * - 'none':   No handling of the organization. Allows '@' to be part
 *             of the username.
 * - 'allow':  Will allow users to type 'username@organization'.
 * - 'force':  Force users to type 'username@organization'. The dropdown
 *             list will be hidden.
 *
 * The default is 'none'.
 *
 *
 * @include_organization_in_username
 *
 * Whether the organization should be included as part of the username
 * when authenticating. If this is set to TRUE, the username will be on
 * the form <username>@<organization identifier>. If this is FALSE, the
 * username will be used as the user enters it.
 *
 * The default is FALSE.
 *
 * @sources A list of available SQL BD.
 *
 *
 * The index is an identifier for the organization/group. When
 * 'username_organization_method' is set to something other than 'none',
 * the organization-part of the username is matched against the index.
 *
 * The value of each element is an array in the same format as an SQL
 * authentication source except that 'description' is available.
 *
 * The 'description' is a short name/description for this group. 
 * Will be shown in a dropdown list when user logs on. This option can be
 * a string or an array with language => text mappings.
 *
 * All options from the `sqlauth:SQL` configuration can be used in each
 * group, and you should refer to the documentation for that module for
 * more information about available options.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

class sspmod_sqlauth_Auth_Source_SQLMulti extends sspmod_core_Auth_UserPassOrgBase {

	/**
	 * An array with descriptions for organizations.
	 */
	private $orgs;

	/**
	 * An array of organization IDs to SQL configuration objects.
	 */
	private $sqlOrgs;

	/**
	 * Whether we should include the organization as part of the username.
	 */
	private $includeOrgInUsername;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgHelper = SimpleSAML_Configuration::loadFromArray($config,
			'Authentication source ' . var_export($this->authId, TRUE));


                if (array_key_exists('username_organization_method', $config)) {
                        $usernameOrgMethod = $cfgHelper->getValueValidate(
                                        'username_organization_method',
                                        array('none', 'allow', 'force'));
                        $this->setUsernameOrgMethod($usernameOrgMethod);
                }		

		if (array_key_exists('username_organization_in_username', $config)) {
			$this->includeOrgInUsername = $cfgHelper->getBoolean(
                                        'include_organization_in_username', FALSE);
		}

		if(!array_key_exists('sources', $config)) {
			throw new Exception('Missing required attribute \'sources \' for authentication source ' . $this->authId);
		}

		$this->orgs = array();
		$this->sqlOrgs = array();
		foreach ($config['sources'] as $name => $orgCfg) {
			if (is_array($orgCfg)) {
				$orgId = $name;

				if (array_key_exists('description', $orgCfg)) {
					$this->orgs[$orgId] = $orgCfg['description'];
				} else {
					$this->orgs[$orgId] = $orgId;
				}
				$this->sqlOrgs[$orgId] = new sspmod_sqlauth_Auth_Source_SQL($info, $orgCfg);
			}
		}
	}


	/**
	 * Attempt to log in using the given username and password.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @param string $org  The organization the user chose.
	 * @return array  Associative array with the users attributes.
	 */
	protected function login($username, $password, $org) {
		assert('is_string($username)');
		assert('is_string($password)');
		assert('is_string($org)');

		if (!array_key_exists($org, $this->sqlOrgs)) {
			/* The user has selected an organization which doesn't exist anymore. */
			SimpleSAML_Logger::warning('Authentication source ' . var_export($this->authId, TRUE) .
				': Organization seems to have disappeared while the user logged in.' .
				' Organization was ' . var_export($org, TRUE));
			throw new SimpleSAML_Error_Error('WRONGUSERPASS');
		}

		if ($this->includeOrgInUsername) {
			$username = $username . '@' . $org;
		}

		return $this->sqlOrgs[$org]->login($username, $password);
	}


	/**
	 * Retrieve list of organizations.
	 *
	 * @return array  Associative array with the organizations.
	 */
	protected function getOrganizations() {
		return $this->orgs;
	}

}

?>
