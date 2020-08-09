<?php
/**
 * Created by PhpStorm.
 * User: dino.ma
 * Date: 2019/9/10
 * Time: 11:03 AM
 */

namespace DistributedLock\Exceptions;

use Throwable;

class DistributeLockException extends \Exception
{

    const CODE = 7070;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {

        parent::__construct($message, $this->code.$code, $previous);
    }

}