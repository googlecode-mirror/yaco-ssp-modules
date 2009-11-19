<?php

$lang = array(
	'user_CV_header' => array (
		'en' => 'Input a certificate to validate',
		'es' => 'Introduzca un certificado para validar',
	),

	'user_CV_submit' => array (
		'en' => 'Validate certificate',
		'es' => 'Validar certificado',
	),

	'user_CV_text' => array (
		'en' => 'Below you can validate a certificate. Paste a PEM (Base64 encoded DER certificate) certificate in the following text area.',
		'es' => 'A continuación puede validar un certificado. Por favor, copie en el cuadro de texto un certificado PEM (certificado DER codificado en Base64).',
	),

	'error_header' => array (
		'en' => 'Your certificate could not be validate',
		'es' => 'Su certificado no se ha podido validar',
	),

	'unable_to_load' => array (
		'en' => 'Your certificate could not be loaded. Incorrect certificate format.',
		'es' => 'Su certificado no se ha podido cargar. Formato del certificado incorrecto.',
	),

	'error' => array (
		'en' => 'Error.',
		'es' => 'Error.',
	),

	'error_found_2' => array (
		'en' => 'Unable to get issuer certificate. Issuer certificate of an untrusted certificate cannot be found.',
		'es' => 'No se puede obtener el certificado del emisor. El certificado del emisor de un certificado no confiable no se puede encontrar.',
	),

	'error_found_3' => array (
		'en' => 'Unable to get certificate CRL.',
		'es' => 'No se pudo obtener el certificado CRL.',
	),

	'error_found_4' => array (
		'en' => 'Unable to decrypt certificate`s signature.',
		'es' => 'No se pudo descifrar la firma del certificado.',
	),

	'error_found_5' => array (
		'en' => 'Unable to decrypt CRL`s signature.',
		'es' => 'No se pudo descifrar la firma del CRL.',
	),

	'error_found_6' => array (
		'en' => 'Unable to decode issuer public key. Certificate SubjectPublicKeyInfo could not be read.',
		'es' => 'No se pudo descifrar pública del emisor. no se pudo leerse el SubjectPublicKeyInfo del certificado.',
	),

	'error_found_7' => array (
		'en' => 'The signature of the certificate is invalid.',
		'es' => 'La firma del certificado no es válida.',
	),

	'error_found_8' => array (
		'en' => 'CRL signature failure.',
		'es' => 'La firma del CRL no es válida.',
	),

	'error_found_9' => array (
		'en' => 'The certificate is not yet valid, The notBefore date is after the current time.',
		'es' => 'El certificado no es válido aún, La fecha NotBefore es posterior a la hora actual.',
	),

	'error_found_10' => array (
		'en' => 'Your certificate has expired.',
		'es' => 'Su certificado ha expirado.',
	),

	'error_found_11' => array (
		'en' => 'CRL is not yet valid.',
		'es' => 'El CRL no es válido aún.',
	),

	'error_found_12' => array (
		'en' => 'CRL has expired.',
		'es' => 'El CRL ha expirado.',
	),

	'error_found_13' => array (
		'en' => 'The certificate notBefore field contains an invalid time.',
		'es' => 'El campo NotBefore del certificado contiene una hora no válida.',
	),

	'error_found_14' => array (
		'en' => 'The certificate notAfter field contains an invalid time.',
		'es' => 'El campo NotAfter del certificado contiene una hora no válida.',
	),

	'error_found_15' => array (
		'en' => 'The CRL lastUpdate field contains an invalid time.',
		'es' => 'El campo lastUpdate del CRL contiene una hora no válida.',
	),

	'error_found_16' => array (
		'en' => 'The CRL nextUpdate field contains an invalid time.',
		'es' => 'El campo nextUpdate del CRL contiene una hora no válida.',
	),

	'error_found_17' => array (
		'en' => 'Out of memory. An error occurred trying to allocate memory',
		'es' => 'Agotada la memoria. Error al intentar asignar memoria.',
	),

	'error_found_18' => array (
		'en' => 'Self signed certificate. Unable to get certificate CRL.',
		'es' => 'Certificado autofirmado. No se pudo obtener el certificado CRL.',
	),

	'error_found_19' => array (
		'en' => 'The certificate chain could be built up using the untrusted certificates but the root could not be found locally.',
		'es' => 'La cadena de certificados podría ser construida mediante certificados no confiables pero la raíz no se puede encontrar a nivel local.',
	),

	'error_found_20' => array (
		'en' => 'The issuer certificate of a locally looked up certificate could not be found. This normally means the list of trusted certificates is not complete.',
		'es' => 'No se pudo obtener el certificado del emisor local. Esto normalmente significa que la lista de certificados de confianza no es completa.',
	),

	'error_found_21' => array (
		'en' => 'No signatures could be verified because the chain contains only one certificate and it is not self signed. .',
		'es' => 'Las firmas no se pudieron verificar porque la cadena de certificados contiene sólo un certificado y no es auto firmado.',
	),

	'error_found_22' => array (
		'en' => 'Certificate chain too long.',
		'es' => 'La cadena de certificados es demasiado larga.',
	),

	'error_found_23' => array (
		'en' => 'Your certificate has been revoked.',
		'es' => 'Su certificado ha sido revocado.',
	),

	'error_found_24' => array (
		'en' => 'Invalid CA certificate.',
		'es' => 'Certificado CA inválido.',
	),

	'error_found_25' => array (
		'en' => 'The basicConstraints pathlength parameter has been exceeded.',
		'es' => 'Se excedió la longitud de la cadena del parámetro basicConstraints.',
	),

	'error_found_26' => array (
		'en' => 'The supplied certificate cannot be used for the specified purpose.',
		'es' => 'El certificado entregado no puede ser utilizado para los fines especificados.',
	),

	'error_found_27' => array (
		'en' => 'The root CA is not marked as trusted for the specified purpose.',
		'es' => 'la raíz del CA no está marcado como de confianza para los fines especificados.',
	),

	'error_found_28' => array (
		'en' => 'The root CA is marked to reject the specified purpose.',
		'es' => 'la raíz del CA se marcó como rechazada para el propósito especificado.',
	),

	'error_found_29' => array (
		'en' => 'the current candidate issuer certificate was rejected because its subject name did not match the issuer name of the current certificate.',
		'es' => 'El actual certificado del emisor candidato fue rechazado porque el nombre de su asunto  no coincidía con el nombre del emisor del certificado actual.',
	),

	'error_found_30' => array (
		'en' => 'Authority and subject key identifier mismatch.',
		'es' => 'La autoridad y el asunto del identificador de la clave no coinciden.',
	),

	'error_found_31' => array (
		'en' => 'Authority and issuer serial number mismatch.',
		'es' => 'La autoridad y el número de serie del emisor no coinciden.',
	),

	'error_found_32' => array (
		'en' => 'KeyUsage extension does not permit certificate signing.',
		'es' => 'La extensión KeyUsage no permite la firma de certificados.',
	),

	'error_found_50' => array (
		'en' => 'Application verification failure.',
		'es' => 'Error de verificación de la aplicación.',
	),

	'success_header' => array (
		'en' => 'Your certificate has been successfuly validated',
		'es' => 'Su certificado ha sido validado con exito',
	),

	'cert_validation_success' => array (
		'en' => 'Your certificate has been successfuly validated',
		'es' => 'Su certificado ha sido validado con exito',
	),

	'cert_not_found' => array(
		'da' => 'Certifikat ikke findes i virksomheden metadata',
		'en' => 'Certificate not found in entity metadata',
		'es' => 'No se encontro certificado en los metadatos de la entidad',
	),

);


?>
<?php

$lang = array(
	'user_CV_header' => array (
		'en' => 'Input a certificate to validate',
		'es' => 'Introduzca un certificado para validar',
	),

	'user_CV_submit' => array (
		'en' => 'Validate certificate',
		'es' => 'Validar certificado',
	),

	'user_CV_text' => array (
		'en' => 'Below you can validate a certificate. Paste a PEM (Base64 encoded DER certificate) certificate in the following text area.',
		'es' => 'A continuación puede validar un certificado. Por favor, copie en el cuadro de texto un certificado PEM (certificado DER codificado en Base64).',
	),

	'error_header' => array (
		'en' => 'Your certificate could not be validate',
		'es' => 'Su certificado no se ha podido validar',
	),

	'unable_to_load' => array (
		'en' => 'Your certificate could not be loaded. Incorrect certificate format.',
		'es' => 'Su certificado no se ha podido cargar. Formato del certificado incorrecto.',
	),

	'error' => array (
		'en' => 'Error.',
		'es' => 'Error.',
	),

	'error_found_2' => array (
		'en' => 'Unable to get issuer certificate. Issuer certificate of an untrusted certificate cannot be found.',
		'es' => 'No se puede obtener el certificado del emisor. El certificado del emisor de un certificado no confiable no se puede encontrar.',
	),

	'error_found_3' => array (
		'en' => 'Unable to get certificate CRL.',
		'es' => 'No se pudo obtener el certificado CRL.',
	),

	'error_found_4' => array (
		'en' => 'Unable to decrypt certificate`s signature.',
		'es' => 'No se pudo descifrar la firma del certificado.',
	),

	'error_found_5' => array (
		'en' => 'Unable to decrypt CRL`s signature.',
		'es' => 'No se pudo descifrar la firma del CRL.',
	),

	'error_found_6' => array (
		'en' => 'Unable to decode issuer public key. Certificate SubjectPublicKeyInfo could not be read.',
		'es' => 'No se pudo descifrar pública del emisor. no se pudo leerse el SubjectPublicKeyInfo del certificado.',
	),

	'error_found_7' => array (
		'en' => 'The signature of the certificate is invalid.',
		'es' => 'La firma del certificado no es válida.',
	),

	'error_found_8' => array (
		'en' => 'CRL signature failure.',
		'es' => 'La firma del CRL no es válida.',
	),

	'error_found_9' => array (
		'en' => 'The certificate is not yet valid, The notBefore date is after the current time.',
		'es' => 'El certificado no es válido aún, La fecha NotBefore es posterior a la hora actual.',
	),

	'error_found_10' => array (
		'en' => 'Your certificate has expired.',
		'es' => 'Su certificado ha expirado.',
	),

	'error_found_11' => array (
		'en' => 'CRL is not yet valid.',
		'es' => 'El CRL no es válido aún.',
	),

	'error_found_12' => array (
		'en' => 'CRL has expired.',
		'es' => 'El CRL ha expirado.',
	),

	'error_found_13' => array (
		'en' => 'The certificate notBefore field contains an invalid time.',
		'es' => 'El campo NotBefore del certificado contiene una hora no válida.',
	),

	'error_found_14' => array (
		'en' => 'The certificate notAfter field contains an invalid time.',
		'es' => 'El campo NotAfter del certificado contiene una hora no válida.',
	),

	'error_found_15' => array (
		'en' => 'The CRL lastUpdate field contains an invalid time.',
		'es' => 'El campo lastUpdate del CRL contiene una hora no válida.',
	),

	'error_found_16' => array (
		'en' => 'The CRL nextUpdate field contains an invalid time.',
		'es' => 'El campo nextUpdate del CRL contiene una hora no válida.',
	),

	'error_found_17' => array (
		'en' => 'Out of memory. An error occurred trying to allocate memory',
		'es' => 'Agotada la memoria. Error al intentar asignar memoria.',
	),

	'error_found_18' => array (
		'en' => 'Self signed certificate. Unable to get certificate CRL.',
		'es' => 'Certificado autofirmado. No se pudo obtener el certificado CRL.',
	),

	'error_found_19' => array (
		'en' => 'The certificate chain could be built up using the untrusted certificates but the root could not be found locally.',
		'es' => 'La cadena de certificados podría ser construida mediante certificados no confiables pero la raíz no se puede encontrar a nivel local.',
	),

	'error_found_20' => array (
		'en' => 'The issuer certificate of a locally looked up certificate could not be found. This normally means the list of trusted certificates is not complete.',
		'es' => 'No se pudo obtener el certificado del emisor local. Esto normalmente significa que la lista de certificados de confianza no es completa.',
	),

	'error_found_21' => array (
		'en' => 'No signatures could be verified because the chain contains only one certificate and it is not self signed. .',
		'es' => 'Las firmas no se pudieron verificar porque la cadena de certificados contiene sólo un certificado y no es auto firmado.',
	),

	'error_found_22' => array (
		'en' => 'Certificate chain too long.',
		'es' => 'La cadena de certificados es demasiado larga.',
	),

	'error_found_23' => array (
		'en' => 'Your certificate has been revoked.',
		'es' => 'Su certificado ha sido revocado.',
	),

	'error_found_24' => array (
		'en' => 'Invalid CA certificate.',
		'es' => 'Certificado CA inválido.',
	),

	'error_found_25' => array (
		'en' => 'The basicConstraints pathlength parameter has been exceeded.',
		'es' => 'Se excedió la longitud de la cadena del parámetro basicConstraints.',
	),

	'error_found_26' => array (
		'en' => 'The supplied certificate cannot be used for the specified purpose.',
		'es' => 'El certificado entregado no puede ser utilizado para los fines especificados.',
	),

	'error_found_27' => array (
		'en' => 'The root CA is not marked as trusted for the specified purpose.',
		'es' => 'la raíz del CA no está marcado como de confianza para los fines especificados.',
	),

	'error_found_28' => array (
		'en' => 'The root CA is marked to reject the specified purpose.',
		'es' => 'la raíz del CA se marcó como rechazada para el propósito especificado.',
	),

	'error_found_29' => array (
		'en' => 'the current candidate issuer certificate was rejected because its subject name did not match the issuer name of the current certificate.',
		'es' => 'El actual certificado del emisor candidato fue rechazado porque el nombre de su asunto  no coincidía con el nombre del emisor del certificado actual.',
	),

	'error_found_30' => array (
		'en' => 'Authority and subject key identifier mismatch.',
		'es' => 'La autoridad y el asunto del identificador de la clave no coinciden.',
	),

	'error_found_31' => array (
		'en' => 'Authority and issuer serial number mismatch.',
		'es' => 'La autoridad y el número de serie del emisor no coinciden.',
	),

	'error_found_32' => array (
		'en' => 'KeyUsage extension does not permit certificate signing.',
		'es' => 'La extensión KeyUsage no permite la firma de certificados.',
	),

	'error_found_50' => array (
		'en' => 'Application verification failure.',
		'es' => 'Error de verificación de la aplicación.',
	),

	'success_header' => array (
		'en' => 'Your certificate has been successfuly validated',
		'es' => 'Su certificado ha sido validado con exito',
	),

	'cert_validation_success' => array (
		'en' => 'Your certificate has been successfuly validated',
		'es' => 'Su certificado ha sido validado con exito',
	),

	'cert_not_found' => array(
		'da' => 'Certifikat ikke findes i virksomheden metadata',
		'en' => 'Certificate not found in entity metadata',
		'es' => 'No se encontro certificado en los metadatos de la entidad',
	),

);


?>
