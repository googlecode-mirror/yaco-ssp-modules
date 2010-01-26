<?php

$config = array (
   	'auth' => 'saml',
	'required_attrs' => array (
		'givenName', 'cn', 'sn', 'schacSn1', 'schacSn2', 'displayName',
		'irisMailMainAddress', 'eduPersonScopedAffiliation',
		'eduPersonAffiliation', 'eduPersonPrimaryAffiliation',
		'eduPersonPrincipalName', 'eduPersonOrgDN',
		'schacPersonalUniqueID',
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
		'fax', 'schacCountryOfResidence', 'eduPersonAssurance',
		'userCertificate', 'userSMIMECertificate',
		'irisPublicKey', 'uid', 'o', 'ou', 'labeledURI',
		'description', 'seeAlso',
	),
);

?>
