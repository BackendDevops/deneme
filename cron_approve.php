<?php
require('autoload.php');


while(true){

    sleep(600); // sleep for 600 sec = 10 minute

    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, $settings['BASE_URL']."approve.php"); 
    curl_exec($curl); 
    curl_getinfo($curl,CURLINFO_HTTP_CODE); 
    curl_close($curl);
}