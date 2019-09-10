<?php

namespace DistributedLock\Adapter;

use DistributedLock\Adapter\Driver\PredisAdapter;
use DistributedLock\Exceptions\AdapterFactoryException;
use DistributedLock\Request\PredisRequest;
use Predis\Client;

class AdapterFactory
{

    protected static $maps = [
        'predis' => 'createPredisAdapter'
    ];


    /**
     * 创建一个Predis对象
     *
     * @param PredisRequest $predisRequest
     * @return PredisAdapter
     * @throws AdapterFactoryException
     */
    public static function createPredisAdapter(PredisRequest $predisRequest) : PredisAdapter
    {
        try {
            $client = new Client(
                [
                'scheme' => $predisRequest->scheme,
                'host' => $predisRequest->host,
                'port' => $predisRequest->port,
                ]
            );
            if (!empty($predisRequest->auth)) {
                $client->auth($predisRequest->auth);
            }
            $client->select($predisRequest->db);

            return new PredisAdapter($client);
        } catch (AdapterFactoryException $exception) {
            throw new AdapterFactoryException('Failed create PredisAdapter', 0, $exception);
        }
    }
}
