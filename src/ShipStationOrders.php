<?php

namespace Zeeshan\LaravelShipStation;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Zeeshan\LaravelShipStation\Helpers\Orders;
use Zeeshan\LaravelShipStation\Models\Order;
use Zeeshan\LaravelShipStation\ShipStation;

class ShipStationOrders extends Orders
{
    const ENDPOINT = '/orders';
    const UPDATE = self::ENDPOINT . '/createorder';
    private $api;
    private array $params = [];
    private $order = null;

    public function __construct()
    {
        $this->api = new ShipStation();
        $this->params = [
            'storeId' => config('shipstation.ebayStoreId'),
            'pageSize' => config('shipstation.pageSize'),
            'page' => 1,
            'orderStatus' => null,
        ];
    }

    public function all(array $options = [], $endpoint = self::ENDPOINT)
    {
        try{
            $response = $this->api->get(self::ENDPOINT, ['query' => array_merge($this->params, $options)]);
            return $this->toCollection($response);
        } catch (ClientException $errorResponse){
            if($errorResponse->getCode() == 400 || $errorResponse->getCode() == 404){
                throw new NotFoundResourceException('Order not found');
            }

            if($errorResponse->getCode() == 401){
                throw new UnauthorizedException('Unauthorized.');
            }

            return $errorResponse;
        }
    }

    public function status($status)
    {
        $this->params['orderStatus'] = $status;
        return $this;
    }

    public function storeId($storeId)
    {
        $this->params['storeId'] = $storeId;
        return $this;
    }

    public function page(int $page)
    {
        $this->params['page'] = $page;
        return $this;
    }

    public function pageSize(int $pageSize)
    {
        $this->params['pageSize'] = $pageSize;
        return $this;
    }

    public function limit(int $limit)
    {
        $this->params['pageSize'] = $limit;
        return $this;
    }

    public function sortBy($column, $direction = 'asc')
    {
        $this->params['sortBy'] = $column;
        $this->params['sortDir'] = $direction;

        return $this;
    }

    public function find(int $orderId)
    {
        $endpoint = self::ENDPOINT . "/$orderId";
        $response = $this->api->get($endpoint);
        $response = $this->toJson($response);
        $order = $this->toObj($response);

        $storeId = $this->params['storeId'];
        $orderStoreId = $order->advancedOptions['storeId'] ?? null;

        if($storeId != $orderStoreId){
            throw new NotFoundResourceException(sprintf('Order not found in this store.'));
        }

        return $order;
    }

    public function where(string $column, string $value)
    {
        $this->params[$column] = $value;
        return $this;
    }

    public function modify(int $orderId, array $options = [])
    {
        $order = $this->find($orderId);
        if(!(isset($order->orderKey) && $order->orderKey)){
            throw new NotFoundResourceException('Order does not have an orderKey which is require to update the order.');
        }

        $shipStation = new ShipStation();

        $order = json_decode(json_encode((object) $order), true);
        $options = array_merge($order, $options);
        $options['json'] = true;
        $response = $this->toJson($shipStation->post(self::UPDATE, $options));
        return $this->toObj($response);
    }

    public function toCollection($response)
    {
        $response = $this->toJson($response);
        $collection =  collect($response->orders)->map(function ($order) {
            return $this->toObj($order);
        });
        $collection->total = $response->total;
        $collection->page = $response->page;
        $collection->pages = $response->pages;
        return $collection;
    }

    private function toObj($orderResponse)
    {
        $arr = json_decode(json_encode($orderResponse), true);
        $order = new Order();
        foreach ($arr as $key => $val){
            $order->{$key} = $val;
        }

        return $order;
    }

    private function toJson($response)
    {
        return json_decode($response->getBody()->getContents());
    }
}
