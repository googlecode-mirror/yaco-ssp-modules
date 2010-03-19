<?php

$config = array (
	'auth' => 'saml',
	'required_attrs' => array (
		'givenName', 'cn', 'sn', 'schacSn1', 'schacSn2', 'displayName',
		'irisMailMainAddress', 'eduPersonScopedAffiliation',
		'eduPersonAffiliation', 'eduPersonPrimaryAffiliation',
		'eduPersonPrincipalName',
		'schacPersonalUniqueID', 'irisMailAlternateAddress',
	),
	'recommended_attrs' => array (
		'irisMailAlternateAddress', 'mail', 'schacUserPresenceID',
		'irisClassifCode', 'schacUserStatus',
		'eduPersonEntitlement', 'irisUserEntitlement',
		'schacUserPrivateAttribute', 'schacPersonalUniqueCode',
	),
	'optional_attrs' => array (
		'schacMotherTonge', 'schacGender', 'schacDateOfBirth',
		'schacPlaceOfBirth', 'schacCountryOfCitizenship',
		'jpegPhoto', 'eduPersonNickname', 'schacPersonalTitle',
		'title', 'preferredLanguage', 'schacYearOfBirth',
		'postalAddress', 'homePostalAddress', 'street', 'l',
		'postalCode', 'mobile', 'homePhone', 'telephoneNumber',
		'fax', 'schacCountryOfResidence', 'eduPersonOrgDN',
		'eduPersonAssurance', 'userCertificate', 'userSMIMECertificate',
		'irisPublicKey', 'uid', 'o', 'ou', 'labeledURI',
		'description', 'seeAlso',
	),
	'generated_attrs' => array(
		'eduPersonTargetedID', 'schacHomeOrganization',
		'schacHomeOrganizationType',
	),
    'format_validation_regex' => array(
        'irisMailMainAddress' => '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
        'eduPersonScopedAffiliation' => '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
        'schacPersonalUniqueID' => '/urn:mace:terena.org:schac:personalUniqueID:es:(.+):(.+)/',
        'schacUserStatus' => '/urn:mace:terena.org:schac:userStatus:es:(.+):([0-9]{8}):(.+):(.+):(.+)/',
        'irisMailAlternateAddress' => '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
        'mail' => '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/',
    ),
);

?>
