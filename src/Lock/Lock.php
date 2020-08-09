<?php


namespace DistributedLock\Lock;


abstract class Lock
{


    /**
     * 拿到一把互斥锁
     * @param string $lockName
     * @param int $expire   锁持有时长s
     * @return bool
     */
    abstract public function getMutexLock(string $lockName, int $expire) : bool;


    /**
     * 对锁进行续约（daemon or new goroutine or new thread）
     * @param string $lockName
     * @param int $startTime
     * @return bool
     */
    abstract public function renewalLock(string $lockName, int $startTime) : bool;

    /**
     * @param string $lockName
     * @return bool
     */
    abstract public function releaseMutexLock(string $lockName) : bool;

    /**
     * @param string $lockName
     * @return bool
     */
    abstract public function getBlockLock(string $lockName) : bool;

    /**
     * @param string $lockName
     * @return bool
     */
    abstract public function releaseBlockLock(string $lockName) : bool;

}