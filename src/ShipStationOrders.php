<?php

namespace Zeeshan\LaravelShipStation;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Zeeshan\LaravelShipStation\ShipStation;

class ShipStationOrders
{
    const ENDPOINT = '/orders';
    private $uri = '/orders';
    private array $params = [];
    private $order = null;

    public function __construct()
    {
        $this->params = [
            'storeId' => config('shipstation.ebayStoreId'),
            'pageSize' => config('shipstation.page_size'),
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
            if($errorResponse->getCode() == 400){
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

    public function find(int $orderId)
    {
        $this->uri .= "/$orderId";

        return $this->get();
    }

    public function update(int $orderId, array $options = [])
    {
        $order = $this->find($orderId);

        if(!(isset($order->orderKey) && $order->orderKey)){
            throw new NotFoundResourceException('Order not found');
        }

        $shipStation = new ShipStation();

        $options = array_merge(['storeId' => $this->params['storeId'], 'orderKey' => $order->orderKey], $options);

        $this->toJson($shipStation->post($this->uri, ['query' => $options]));

    }

    public function toJson($response)
    {
        return json_decode($response->getBody()->getContents());
    }
}
