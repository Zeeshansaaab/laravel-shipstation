<?php

namespace Zeeshan\LaravelShipStation;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Zeeshan\LaravelShipStation\ShipStation;

class ShipStationOrders
{
    const ENDPOINT = '/orders';
    const UPDATE = self::ENDPOINT . '/createorder';
    private $uri = '/orders';
    private array $params = [];
    private $order = null;

    public function __construct()
    {
        $this->params = [
            'storeId' => config('shipstation.ebayStoreId'),
            'pageSize' => config('shipstation.pageSize'),
            'page' => 1,
            'orderStatus' => null,
        ];
    }

    public function get(array $options = [])
    {
        try{
            $shipStation = new ShipStation();
            $response = $shipStation->get($this->uri, ['query' => array_merge($this->params, $options)]);
            return $this->toJson($response);
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
        $this->uri .= "/$orderId";

        return $this->get();
    }

    public function update(int $orderId, array $options = [])
    {
        $order = $this->find($orderId);

        if(!(isset($order->orderKey) && $order->orderKey)){
            throw new NotFoundResourceException('Order does not have an orderKey which is require to update the order.');
        }

        $shipStation = new ShipStation();

        if($this->params['storeId']){
            $options['storeId'] = $this->params['storeId'];
        }

        $order = json_decode(json_encode((object) $order), true);

        $options = array_merge($order, $options);
        $options['json'] = true;
        return $this->toJson($shipStation->post(self::UPDATE, $options));
    }

    public function toJson($response)
    {
        return json_decode($response->getBody()->getContents());
    }
}
