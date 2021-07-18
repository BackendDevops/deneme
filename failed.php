<?php 

require_once('autoload.php');
echo '<pre>Starting Process\n</pre>';
$orders = $action->getFailedOrders();

if(count($orders)>0){
    foreach($orders as $order){
        $limitter->listen();
        try{
            $orderDetail = $action->connect('api/orders/' . $order->order_id);
            $status = $action->process($orderDetail);
            if($status){
                $action->removeFromFailedOrders($orderDetail->id)
            }
            flush();
            ob_flush();

        }catch(Error $e){

        }
    }
    
}else{
    exit('No Record to reprocess');
}
echo '<pre>Proccessed</pre>';