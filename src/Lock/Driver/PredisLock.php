<?php
/**
 * Created by PhpStorm.
 * User: dino.ma
 * Date: 2019/9/10
 * Time: 2:46 PM
 */

namespace DistributedLock\Lock\Driver;

use DistributedLock\Lock\Lock;
use DistributedLock\Exceptions\LockException;
use Predis\Client;

class PredisLock extends Lock
{

    const MUTEX_LOCK_PRE = 'metux_lock:';

    public $timeOut = 0.5;//妙 0.5秒

    public $retry = 5;//重试次数

    private $redis;

    private $pid;



    public function __construct(Client $redis)
    {
        $this->redis = $redis;
        $this->pid = getmypid();//当前进程ID
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
                    'time' =>  time() + $this->timeOut,//锁到期时间
                    'start_time'    => self::getCurrentTime(),//锁开始时间
                    'expire_time'   =>  $this->timeOut,//锁MAX持有时间
                ]
        ];
    }

    /**
     * 拿到一把互斥锁
     * @param string $lockName
     * @param int $expire   妙
     * @return bool
     * @throws LockException
     */
    public function getMutexLock(string $lockName, int $expire = 0): bool
    {
        try {
            if ($expire > 0){
                $this->timeOut = $expire;
            }
            $isLock = 0;
            $lockInfo = $this->getLockInfo($lockName);
            $redisLock = $this->redis->get(self::MUTEX_LOCK_PRE.$lockInfo['key']);

            if (!empty($redisLock)) {
                //当前锁存在，需要续时
                $oldLockInfo = self::arrayValDecode($redisLock);
                if ($oldLockInfo['pid'] == $lockInfo['val']['pid']) {
                    $isLock = $this->redis->setex(
                        self::MUTEX_LOCK_PRE.$lockInfo['key'],
                        $this->timeOut,
                        self::arrayValEncode($lockInfo['val'])
                    );
                }
            } else {
                //当前锁不存在需要需要设置锁
                $isLock = $this->redis->setex(self::MUTEX_LOCK_PRE.$lockInfo['key'],  $this->timeOut, self::arrayValEncode($lockInfo['val']) );
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
     * @param string $lockName  锁名称
     * @param int $startTime    续约启动秒数
     * @param int $incrTime     每次续约增加时间
     * @return bool
     * @throws LockException
     */
    public function renewalLock(string $lockName, int $startTime = 0, int $increaseTime = 1): bool
    {
        try {
            //如果是同一个进程 譬如JAVA GO 等可以拿到进程/线程ID 去进行比较必须是"我进行续约"，而PHP的话只能暂时忽略这个步骤
            $lockInfo = $this->getLockInfo($lockName);
            $key = self::MUTEX_LOCK_PRE.$lockInfo['key'];
            $redisLock = $this->redis->get($key);
            if (!empty($redisLock)) {
                $redisLock = self::arrayValDecode((string)$redisLock);

                if ($startTime == 0) {
                    $startTime = intval($redisLock['expire_time'] / 3)*2;
                }

                $ttl = $this->redis->ttl($key);
                if ($ttl <= $startTime ) {
                    if ($this->redis->exists($key)) {
                        $this->redis->expire($key, ($ttl + $increaseTime));
                        sleep($increaseTime);
                    }
                    //此处还可以进行优化
                }

                return true;
            }

            return false;
        } catch (LockException $exception) {
            throw new LockException('Failed renew to {$lockName}', 0, $exception);
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
            $isDel = 0;
            if (!empty($redisInfo)) {
                $redisInfo = self::arrayValDecode($redisInfo);
                if ($redisInfo['pid'] == $lockInfo['val']['pid']) {
                    $isDel = $this->redis->del(self::MUTEX_LOCK_PRE.$lockInfo['key']);
                }
            }

            if ($isDel > 0) {
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
            while ($this->retry-- && $this->retry > 0) {
                if ($this->getMutexLock($lockName)) {
                    return true;
                }
                usleep(100000 * $ms);//微妙
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
            return $this->releaseMutexLock($lockName);
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
