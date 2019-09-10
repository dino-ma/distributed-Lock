<?php


namespace DistributedLock\Adapter;


abstract class Adapter
{

    /**
     * @param string $lockName
     * @return bool
     */
    abstract public function getMutexLock(string $lockName) : bool;

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