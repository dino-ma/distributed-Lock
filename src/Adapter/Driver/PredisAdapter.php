<?php
/**
 * Created by PhpStorm.
 * User: dino.ma
 * Date: 2019/9/10
 * Time: 2:46 PM
 */

namespace DistributedLock\Adapter\Driver;

use DistributedLock\Adapter\Adapter;
use DistributedLock\Exceptions\LockException;
use Predis\Client;

class PredisAdapter extends Adapter
{

    const MUTEX_LOCK_PRE = 'metux_lock:';

    const BLOCK_LOCK_PRE = 'block_lock:';

    public $timeOut = 500000;//微妙 0.5秒

    public $retry = 50;

    private $redis;

    private $pid;



    public function __construct(Client $redis)
    {
        $this->redis = $redis;
        $this->pid = getmypid();
    }

    /**
     * @param  string $lockName
     * @return array
     */
    private function getLockInfo(string $lockName) : array
    {
        return [
            'key' => $lockName,
            'val' =>
                [
                    'pid' => $this->pid,
                    'time' => self::getCurrentTime() + $this->timeOut,
                ]
        ];
    }

    /**
     * 拿到一把互斥锁
     *
     * @param  string $lockName
     * @return bool
     * @throws LockException
     */
    public function getMutexLock(string $lockName): bool
    {
        try {
            $isLock = 0;
            $lockInfo = $this->getLockInfo($lockName);
            $redisLock = $this->redis->get(self::MUTEX_LOCK_PRE.$lockInfo['key']);
            if (!empty($redisLock)) {
                $oldLockInfo = self::arrayValDecode($redisLock);

                if ($oldLockInfo['time'] < ($lockInfo['val']['time'] - $this->timeOut) || $oldLockInfo['pid'] == $lockInfo['val']['pid']) {
                    $isLock = $this->redis->setex(
                        self::MUTEX_LOCK_PRE.$lockInfo['key'],
                        $this->timeOut,
                        self::arrayValEncode($lockInfo['val'])
                    );
                }
            } else {
                $isLock = $this->redis->setnx(self::MUTEX_LOCK_PRE.$lockInfo['key'], self::arrayValEncode($lockInfo['val']));
            }

            if ($isLock > 0) {
                return true;
            }

            return false;
        } catch (LockException $exception) {
            throw new LockException('Failed lock to {$lockName}', 0, $exception);
        }
    }


    /**
     * 释放一把互斥锁
     *
     * @param  string $lockName
     * @return bool
     * @throws LockException
     */
    public function releaseMutexLock(string $lockName): bool
    {
        try {
            $lockInfo = self::getLockInfo($lockName);
            $redisInfo = $this->redis->get(self::MUTEX_LOCK_PRE.$lockName);
            $isLock = 0;
            if (!empty($redisInfo)) {
                $redisInfo = self::arrayValDecode($redisInfo);
                if ($redisInfo['pid'] == $lockInfo['val']['pid'] || $lockInfo['val']['time'] > $redisInfo['time']) {
                    $isLock = $this->redis->setex(self::MUTEX_LOCK_PRE.$lockInfo['key'], $this->timeOut, self::arrayValEncode($lockInfo['val']));
                }
            } else {
                $isLock = $this->redis->setnx(self::MUTEX_LOCK_PRE.$lockInfo['key'], self::arrayValEncode($lockInfo['val']));
            }

            if ($isLock > 0) {
                return true;
            }

            return false;
        } catch (LockException $exception) {
            throw new LockException('Failed lock to {$lockName}', 0, $exception);
        }
    }


    /**
     * 拿到一把阻塞锁
     *
     * @param string $lockName
     * @return bool
     * @throws LockException
     */
    public function getBlockLock(string $lockName): bool
    {
        try {
            $ms = 5;
            while ($this->retry--) {
                if ($this->getMutexLock(self::BLOCK_LOCK_PRE.$lockName)) {
                    return true;
                } else {
                    usleep(10000 * $ms);//毫秒
                }
            }

            return false;
        } catch (LockException $exception) {
            throw  new LockException('Failed lock to {$lockName}', 0, $exception);
        }
    }

    /**
     * 释放阻塞锁
     *
     * @param string $lockName
     * @return bool
     * @throws LockException
     */
    public function releaseBlockLock(string $lockName): bool
    {
        try {
            return $this->releaseMutexLock(self::BLOCK_LOCK_PRE.$lockName);
        } catch (LockException $exception) {
            throw  new LockException('Failed lock to {$lockName}', 0, $exception);
        }
    }

    /**
     * @param  array $array
     * @return string
     */
    private static function arrayValEncode(array $array) : string
    {
        return json_encode($array);
    }

    /**
     * @param  string $string
     * @return array
     */
    private static function arrayValDecode(string $string) : array
    {
        return json_decode($string, true);
    }

    /**
     * @return int
     */
    private static function getCurrentTime() : int
    {
        return microtime(true) * 1000;
    }
}
