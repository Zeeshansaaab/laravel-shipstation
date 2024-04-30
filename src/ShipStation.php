<?php
namespace Zeeshan\LaravelShipStation;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class ShipStation extends Client
{
    /**
     * @var string The current endpoint for the API. The default endpoint is /orders/
     */
    public $endpoint = '/orders/';

    /**
     * @var array Our list of valid ShipStation endpoints.
     */
    private $endpoints = [
        '/accounts/',
        '/carriers/',
        '/customers/',
        '/fulfillments/',
        '/orders/',
        '/products/',
        '/shipments/',
        '/stores/',
        '/users/',
        '/warehouses/',
        '/webhooks/'
    ];

    /**
     * @var string Base API URL for ShipStation
     */
    private $base_uri = 'https://ssapi.shipstation.com';

    /**
     * ShipStation constructor.
     *
     * @param  string  $apiKey
     * @param  string  $apiSecret
     * @throws \Exception
     */
    public function __construct()
    {
        $apiKey  = config('shipstation.apiKey');
        $apiSecret = config('shipstation.apiSecret');

        if (!isset($apiKey, $apiSecret)) {
            throw new \Exception('Your API key and/or private key are not set. Did you run artisan vendor:publish?');
        }

        parent::__construct([
            'base_uri' => $this->base_uri,
            'headers'  => [
                'Authorization' => 'Basic ' . base64_encode("{$apiKey}:{$apiSecret}"),
            ]
        ]);
    }

    /**
     * Get a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  array  $options
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function get($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->request('GET', $uri, $options);
        $this->sleepIfRateLimited($response);
        return $response;
    }    

    /**
     * Post to a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  array  $options
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function post($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        //$response = $this->request('POST', $uri, ['form_params' => $options]);
        $contentType = isset($options['json']) ? 'json' : 'form_params';
        $response = $this->request('POST', $uri, [$contentType => $options]);
        $this->sleepIfRateLimited($response);
        return $response;
    }

    /**
     * Delete a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function delete($uri, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->request('DELETE', $uri, $options);
        $this->sleepIfRateLimited($response);
        return $response;
    }


    /**
     * Update a resource using the assigned endpoint ($this->endpoint).
     *
     * @param  array  $options
     * @param  string  $endpoint
     * @return \stdClass
     */
    public function update($endpoint, array $options = []): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->request('PUT', $endpoint, ['json' => $options]);
        $this->sleepIfRateLimited($response);
        // return json_decode($response->getBody()->getContents());
        return $response;
    }

    /**
     * Check to see if we are about to rate limit and pause if necessary.
     *
     * @param Response $response
     */
    public function sleepIfRateLimited(Response $response)
    {
        $rateLimit = $response->getHeader('X-Rate-Limit-Remaining')[0];
        $rateLimitWait = $response->getHeader('X-Rate-Limit-Reset')[0];

        if (($rateLimitWait / ($rateLimit ?: 1)) > 1.5) {
            sleep(1.5);
        }
    }

    /**
     * Set our endpoint by accessing it via a property.
     *
     * @param  string $property
     * @return $this
     */
    public function __get($property)
    {
        if (in_array('/' . $property . '/', $this->endpoints)) {
            $this->endpoint = '/' . $property . '/';
        }

        $className = "Zeeshan\\LaravelShipStation\\Helpers\\" . ucfirst($property);

        if (class_exists($className)) {
            return new $className($this);
        }

        return $this;
    }

    public function orders(): ShipStationOrders
    {
        return new ShipStationOrders();
    }
}
