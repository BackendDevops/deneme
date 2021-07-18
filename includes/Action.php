<?php

namespace Kvnc;


use DateTime;
use DateTimeZone;
use Error;
use Exception;
use Throwable;
use RuntimeException;


class Action
{

    /**
     * db pdo instance
     *
     * @var mixed
     */
    protected $db;

    /**
     * apiUrl
     *
     * @var mixed
     */
    protected $apiUrl;

    /**
     * apiKey
     *
     * @var mixed
     */
    protected $apiKey;

    public function __construct($db)
    {
        $this->db = $db;
        $this->apiUrl = getenv('API_URL') ?? "https://sample-market.despatchcloud.uk/";
        $this->apiKey = getenv('API_KEY') ?? "3s7SzawE4S75Fv20M4eFGH8OmTzt9Km1Wgcuuh3eBzBNeOqv8kN0WOFzPS1RcPF256HNxtBaSned5Nh4QABcjM4MxHK2oUCOTnHfcs1qn3fsUlToypnLCrXbQmLTnih";
    }

    /**
     * getLastOrder
     *
     * @return mixed
     */
    public function getLastOrder()
    {
        return $this->db->table('orders')->orderBy('id', 'desc')->limit(1)->get();
    }

    /**
     * getFailedOrders
     *
     * @return mixed
     */
    public function getFailedOrders()
    {
        return $this->db->table('failed_orders')->where('status', 0)->orderBy('id', 'desc')->limit(50)->getAll();
    }

    /**
     * removeFromFailedOrders
     *
     * @param  int $orderId
     * @return void
     */
    public function removeFromFailedOrders($orderId)
    {
        $this->db->table('failed_orders')->where('order_id', $orderId)->update(['status' => 1]);
    }


    /**
     * dd
     *
     * @param  mixed $data
     * @return void
     */
    public function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        exit();
    }
    public function failed($params)
    {
    }

    public function log($endpoint, $params, $method, $response)
    {
        $date = date('Y-m-d H:i:s');
        $this->db->table('logs')->insert(
            [
                'endpoint' => $endpoint,
                'payload'  => json_encode($params),
                'method'   => $method,
                'response' => $response,
                'created_at' => $date,
            ]

        );
    }

    /**
     * connect
     *
     * @param  mixed $endpoint
     * @param  mixed $data
     * @param  mixed $params
     * @param  mixed $method
     * @return mixed
     */
    public function connect(string $endpoint, array $params = [], array $data = [], string $method = "GET")
    {

        try {
            $params = array('api_key' => $this->apiKey);

            $params['api_key'] = $this->apiKey;
            $fullUrl = $this->apiUrl . $endpoint . "?" . http_build_query($params);
            echo '<pre>' . $fullUrl . '</pre>';
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json'
                ),
            ));

            $response = curl_exec($curl);
           
            if (!curl_errno($curl)) {
                switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                    case 200:  # OK
                        $result = json_decode($response);
                        $this->log($endpoint, $params, $method, json_encode($result));
                        return $result;
                        break;
                    default:
                        echo 'Error HTTP_CODE: ', $http_code, "\n";
                        $info = curl_getinfo($curl);
                        $this->log($endpoint, $params, $method, json_encode($info));
                        if (array_key_exists('id', $params)) {
                            $this->db->table('failed_orders')->insert(
                                [
                                    'order_id' => $params['id'],
                                    'status' => 0,
                                    'created_at' => date('Y-m-d H:i:s')
                                ]
                            );
                        }
                        return false;
                }
            }

            curl_close($curl);
        } catch (Error $e) {
            $this->log($endpoint, $params, $method, json_encode($e->getMessage()));
            if (array_key_exists('id', $params)) {
                $this->db->table('failed_orders')->insert(
                    [
                        'order_id' => $params['id'],
                        'status' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                );
            }
            return false;
        }
    }

    /**
     * createOrder
     *
     * @param  mixed $data
     * @return mixed
     */
    public function createOrder($data)
    {
        $order = $this->isOrderExists($data->orderId);
        if (!$order) {
            $orderData = array(
                'orderId'               => $data->orderId,
                'payment_method'        => $data->payment_method,
                'shipping_method'       => $data->shipping_method,
                'customer_id'           => $data->customer_id,
                'company_id'            => $data->company_id,
                'type'                  => $data->type,
                'billing_address_id'    => $data->billing_address_id,
                'shipping_address_id'   => $data->shipping_address_id,
                'total'                 => $data->total,
                'raw_data'              => json_encode($data),
                'created_at'            => $this->convertDate($data->created_at),
                'updated_at'            => $this->convertDate($data->updated_at)
            );
            $record = $this->db->table('orders')->insert($orderData);
            if ($record > 0) {
                return $record;
            }
            return false;
        }
        return $order->id;
    }

    /**
     * createCustomer
     *
     * @param  mixed $data
     * @return mixed
     */
    public function createCustomer(object $data)
    {
        $customer = $this->isCustomerExists($data->id);
        if (!$customer) {
            $customerData = array(
                'api_id' => $data->id,
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'created_at' => $this->convertDate($data->created_at),
                'updated_at' => $this->convertDate($data->updated_at)
            );
            $record = $this->db->table('customers')->insert($customerData);
            if ($record > 0) {
                return $record;
            }
            return false;
        }
        return $customer->id;
    }
    /**
     * createProduct
     *
     * @param  mixed $data
     * @return mixed
     */
    public function createProduct($data)
    {
        $product = $this->isProductExists($data->id);
        if (!$product) {
            $productData = array(
                'api_id' => $data->id,
                'title' => $data->title,
                'description' => $data->description,
                'image' => $data->image,
                'sku'   => $data->sku,
                'created_at' => $this->convertDate($data->created_at),
                'updated_at' => $this->convertDate($data->updated_at)
            );
            $record = $this->db->table('products')->insert($productData);
            if ($record > 0) {
                return $record;
            }
            return false;
        }
        return $product->id;
    }
    /**
     * createBillingAddress
     *
     * @param  mixed $data
     * @return mixed
     */
    public function createBillingAddress($data)
    {
        $billingAddress = $this->isBillingAddressExists($data->id);
        if (!$billingAddress) {
            $billingAddressData = array(
                'api_id' => $data->id,
                'name' => $data->name,
                'phone' => $data->phone,
                'line1' => $data->line_1,
                'line2'   => $data->line_2,
                'city'    => $data->city,
                'country' => $data->country,
                'state'  => $data->state,
                "postcode" => $data->postcode,
                'created_at' => $this->convertDate($data->created_at),
                'updated_at' => $this->convertDate($data->updated_at)
            );
            $record = $this->db->table('billing_addresses')->insert($billingAddressData);
            if ($record > 0) {
                return $record;
            }
            return false;
        }
        return $billingAddress->id;
    }

    /**
     * createShippingAddress
     *
     * @param  mixed $data
     * @return mixed
     */
    public function createShippingAddress($data)
    {
        $shippingAddress = $this->isShippingAddressExists($data->id);
        if (!$shippingAddress) {
            $shippingAddressData = array(
                'api_id' => $data->id,
                'name' => $data->name,
                'phone' => $data->phone,
                'line_1' => $data->line_1,
                'line_2'   => $data->line_2,
                'city'    => $data->city,
                'country' => $data->country,
                'state'  => $data->state,
                "post_code" => $data->postcode,
                'created_at' => $this->convertDate($data->created_at),
                'updated_at' => $this->convertDate($data->updated_at)
            );
            $record = $this->db->table('shipping_addresses')->insert($shippingAddressData);
            if ($record > 0) {
                return $record;
            }
            return false;
        }
        return $shippingAddress->id;
    }
    public function createOrderItem($data)
    {
        $orderItem = $this->isOrderItemExists($data->api_id);
        if (!$orderItem) {
            $orderItemData = array(
                'api_id' => $data->api_id,
                'order_id' => $data->order_id,
                'product_id' => $data->product_id,
                'quantity'   => $data->quantity,
                'sub_total'  => $data->subtotal,
                'item_raw_data' => $data->item_raw_data,
                'created_at' => $this->convertDate($data->created_at),
                'updated_at' => $this->convertDate($data->updated_at)
            );
            $record = $this->db->table('order_items')->insert($orderItemData);
            if ($record > 0) {
                return $record;
            }
            return false;
        }
        return $orderItem->id;
    }
    /**
     * isCustomerExists
     *
     * @param  mixed $cid
     * @return mixed|boolean
     */
    public function isCustomerExists($cid)
    {
        $record = $this->db->table('customers')->where('api_id', $cid)->get();
        if ($record) {
            return $record;
        }
        return false;
    }

    /**
     * isBillingAddressExists
     *
     * @param  mixed $bid
     * @return mixed|boolean
     */
    public function isBillingAddressExists($bid)
    {
        $record = $this->db->table('billing_addresses')->where('api_id', $bid)->get();
        if ($record) {
            return $record;
        }
        return false;
    }
    /**
     * isShippingAddressExists
     *
     * @param  mixed $sid
     * @return mixed|boolean
     */
    public function isShippingAddressExists($sid)
    {
        $record = $this->db->table('shipping_addresses')->where('api_id', $sid)->get();
        if ($record) {
            return $record;
        }
        return false;
    }
    /**
     * isProductExists
     *
     * @param  mixed $pid
     * @return mixed|boolean
     */
    public function isProductExists($pid)
    {
        $record = $this->db->table('products')->where('api_id', $pid)->get();
        if ($record) {
            return $record;
        }
        return false;
    }
    /**
     * isOrderItemExists
     *
     * @param  mixed $oid
     * @return mixed
     */
    public function isOrderItemExists($oid)
    {
        $record = $this->db->table('order_items')->where('api_id', $oid)->get();
        if ($record) {
            return $record;
        }
        return false;
    }
    public function isOrderExists($oid)
    {
        $record = $this->db->table('orders')->where('orderId', $oid)->get();
        if ($record) {
            return $record;
        }
        return false;
    }
    public function getUnApprovedOrders()
    {
        return $this->db->table('orders')->where('type', 'pending')->orderBy('id', 'desc')->limit(20)->getAll();
    }
    public function approve($orderId)
    {
        try {
            $params = array('api_key' => $this->apiKey);

            $fullUrl = $this->apiUrl . "api/orders/"  . $orderId . "?" . http_build_query($params);
            echo '<pre>' . $fullUrl . '</pre>';
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POST => true,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => http_build_query(array('type' => 'approved')),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json'
                ),
            ));

            $response = curl_exec($curl);
            
            if (!curl_errno($curl)) {
                switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                    case 200:  # OK
                        $result = json_decode($response);
                        if ($result->type == "approved") {
                            $this->db->table('orders')->where('orderId', $orderId)->update(['type' => 'approved']);
                            $this->log("api/orders" . DIRECTORY_SEPARATOR . $orderId, ['type' => 'approved'], 'POST', json_encode($result));
                        }
                        break;
                    default:
                        echo 'Error HTTP_CODE: ', $http_code, "\n";
                        $info = curl_getinfo($curl);
                        $this->log("api/orders" . DIRECTORY_SEPARATOR . $orderId, ['type' => 'approved'], 'POST', json_encode($info));
                }
            }

            curl_close($curl);
        } catch (Error $e) {
            $this->log("api/orders" . DIRECTORY_SEPARATOR . $orderId, ['type' => 'approved'], 'POST', json_encode($e->getMessage()));
        }
    }
    public function convertDate($date)
    {
        return (new DateTime($date, new DateTimeZone(getenv('TIMEZONE'))))->format('Y-m-d H:i:s');
    }

    /**
     * process
     *
     * @param  mixed $orderData
     * @return boolean
     */
    public function process($orderData)
    {
        if(is_object($orderData)){
        if (!$this->isOrderExists($orderData->id)) {
            $customer = $this->createCustomer($orderData->customer);
            if ($customer) {
                $billingAdress = $this->createBillingAddress($orderData->billing_address);
                if ($billingAdress) {
                    $shippingAddress = $this->createShippingAddress($orderData->shipping_address);
                    if ($shippingAddress) {

                        $orderRecord = $this->createOrder(
                            (object) [
                                'orderId' => $orderData->id,
                                'payment_method' => $orderData->payment_method,
                                'shipping_method' => $orderData->shipping_method,
                                'customer_id'     => $customer,
                                'company_id'      => $orderData->company_id,
                                'type'            => $orderData->type,
                                'billing_address_id' => $billingAdress,
                                'shipping_address_id' => $shippingAddress,
                                'total'              => $orderData->total,
                                'raw_data'           => json_encode($orderData),
                                'created_at'  =>       $orderData->created_at,
                                'updated_at' => $orderData->updated_at,
                            ]
                        );
                        foreach ($orderData->order_items as $orderItem) {
                            $product = $this->createProduct($orderItem->product);

                            $orderItemRecord = $this->createOrderItem(
                                (object)  [
                                    'api_id' => $orderItem->id,
                                    'order_id' => $orderRecord,
                                    'product_id' => $product,
                                    'quantity'   => $orderItem->quantity,
                                    'subtotal'  => $orderItem->subtotal,
                                    'item_raw_data' => json_encode($orderItem),
                                    'created_at'   => $orderItem->created_at,
                                    'updated_at'   => $orderItem->updated_at,

                                ]

                            );
                        }
                        $this->approve($orderData->id);
                        return true;
                    }
                }
            }
        } else {
            echo 'Order is already recorded';
            return false;
        }
    }else{
        return false;
    }
    }
    
}
