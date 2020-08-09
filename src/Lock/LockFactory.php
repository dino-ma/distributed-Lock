<?php

namespace DistributedLock\Lock;

use DistributedLock\Lock\Driver\PredisLock;
use DistributedLock\Exceptions\LockFactoryException;
use DistributedLock\Request\PredisRequest;
use Predis\Client;

class LockFactory
{

    protected static $maps = [
        'predis' => 'createPredisAdapter'
    ];



    /**
     * @param PredisRequest $predisRequest
     * @return PredisLock
     * @throws LockFactoryException
     */
    public static function createPredisLock(PredisRequest $predisRequest) : PredisLock
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

            return new PredisLock($client);
        } catch (LockFactoryException $exception) {
            throw new LockFactoryException('Failed create PredisLock', 0, $exception);
        }
    }
}
