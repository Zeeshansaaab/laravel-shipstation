<?php

namespace Zeeshan\LaravelShipStation;

use Zeeshan\LaravelShipStation\ShipStation;

class ShipStationOrders
{
    private $uri = '/orders';
    private array $params = [];
    public function __construct()
    {
        $this->params = [
            'storeId' => config('shipstation.ebayStoreId'),
            'pageSize' => config('shipstation.page_size'),
            'page' => 1,
            'orderStatus' => null,
        ];
    }

    public function get(array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $shipStation = new ShipStation();
        $response =  $shipStation->get($this->uri, ['query' => array_merge($this->params, $options)]);
        return json_decode($response->getBody()->getContents());
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
}
