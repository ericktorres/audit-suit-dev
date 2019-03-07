<?php

	$url = "https://bluehand.com.mx/backend/api/v1/reports/close-expired-reports";
	$curl = curl_init();
	curl_setopt_array($curl, array(
    	CURLOPT_RETURNTRANSFER => 1,
    	CURLOPT_URL => $url,
    	CURLOPT_USERAGENT => 'Audit Suit'
	));
	$response = curl_exec($curl);

	echo $response . "\r\n";