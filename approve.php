<?php

require 'autoload.php';

$unApprovedOrders = $action->getUnApprovedOrders();

if(count($unApprovedOrders)>0){
    foreach($unApprovedOrders as $item){
        echo $item->orderId."<br>";
        $action->approve($item->orderId);
    }

}else{
    exit('No more records');
}