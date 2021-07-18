<?php
require_once('autoload.php');
echo '<pre>Starting Process\n</pre>';
try {
    $date = date('Y-m-d H:i:s');
    $lastOrder = $action->getLastOrder();
    if (!$lastOrder) {

        $result = $action->connect('api/orders', ['date' => $date]);
    } else {
        $result = $action->connect('api/orders', ['id' => $lastOrder->orderId]);
    }

    if (is_object($result) && count($result->data) > 0) {
        /* for($page=1;$page<=$result->last_page;$page++){*/
        if ($result->current_page !== 1) {
            $result = $action->connect('api/orders', ['page' => $page]);
        }

        if (count($result->data) > 0) {

            foreach ($result->data as $order) {
                $limitter->listen();
                $orderDetail = $action->connect('api/orders/' . $order->id);
                $action->process($orderDetail);
                flush();
                ob_flush();
            }
        } else {
            exit('No Records,exiting...');
        }
        /*}*/
    } else {
        throw new Error('No data available');
    }
} catch (Error $e) {
    echo $e->getMessage();
}
echo '<pre>Proccessed</pre>';