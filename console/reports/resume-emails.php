<?php
	// Sending emails for managers
	$manager_emails = "https://bluehand.com.mx/backend/api/v1/reports/get-emails/managers";
	$str_emails = file_get_contents($manager_emails);
	$arr_emails = json_decode($str_emails);
	$log = "";

	for($i=0; $i<count($arr_emails); $i++){
		$manager = $arr_emails[$i];

		$url = "https://bluehand.com.mx/backend/api/v1/reports/email-for-managers/".$manager->{'manager_id'}."/".$manager->{'email'};
		$curl = curl_init();
		curl_setopt_array($curl, array(
    		CURLOPT_RETURNTRANSFER => 1,
    		CURLOPT_URL => $url,
    		CURLOPT_USERAGENT => 'Audit Suit'
		));
		$response = curl_exec($curl);

		echo $log .= $manager->{'email'} . ": " . $response . ".\r\n";
		
		curl_close($curl);
	}