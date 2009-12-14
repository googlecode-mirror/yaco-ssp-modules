<?php

$config = array(

	/*
	 * This is a authentication source which handles admin authentication.
	 */
	'admin' => array(
		/*
		 * The default is to use core:AdminPassword, but it can be replaced with
		 * any authentication source.
		 */
		'core:AdminPassword',
	),

	'userpass' => array(
		'exampleauth:UserPass',
		'user1:user1' => array(
            'eduPersonPrincipalName' => 'user1',
            'eduPersonEntitlement' => 'test',
            'cn' => 'User 1 TestUser',
            'uid' => 'user1',
            'gn' => 'User 1',
            'sn' => 'TestUser',
            'mail' => 'user1@example.org',
            'schacUserStatus' => array(
                'urn:mace:terena.org:schac:userStatus:es:campusandaluzvirtual.es:TCS1:2009:student:active',
            ),
		),
		'user2:user2' => array(
            'eduPersonPrincipalName' => 'user2@example.org',
            'eduPersonEntitlement' => 'testing',
            'mail' => 'user2@example.org',
            'cn' => 'User 2 TestUser',
            'uid' => 'user2',
            'gn' => 'User 2',
            'sn' => 'TestUser',
            'schacUserStatus' => array(
                'urn:mace:terena.org:schac:userStatus:es:campusandaluzvirtual.es:TCS1:2009:student:active',
            ),
		),
	),
);

?>
