<?php
/**
 * Created by PhpStorm.
 * User: dino.ma
 * Date: 2019/9/10
 * Time: 11:04 AM
 */

namespace DistributedLock\Exceptions;


use Throwable;

class LockException extends DistributeLockException
{

    const CODE = 7071;

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $this->code.$code, $previous);
    }

}