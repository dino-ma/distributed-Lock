<?php
namespace DistributedLock\Request;


class PredisRequest
{

    /**
     * @var string
     */
    public $scheme = 'tcp';

    /**
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * @var string
     */
    public $port = '6379';

    /**
     * @var string
     */
    public $auth = '';

    /**
     * @var int
     */
    public $db = 0;
}