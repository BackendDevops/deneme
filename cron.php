<?php

require('autoload.php');


while(true){

    sleep(60); // sleep for 60 sec = 1 minute

    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL, $settings['BASE_URL']."fetch.php"); 
    curl_exec($curl); 
    curl_getinfo($curl,CURLINFO_HTTP_CODE); 
    curl_close($curl);
}