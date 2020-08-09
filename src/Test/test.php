<?php

require '../../vendor/autoload.php';


$predisRequest = new \DistributedLock\Request\PredisRequest();
$predisRequest->scheme = 'tcp';
$predisRequest->host = '127.0.0.1';
$predisRequest->port = '6379';
$predisRequest->auth = '';
$predisRequest->db = '0';


$t1 = microtime(true);
// ... 执行代码 ...

$lock = \DistributedLock\Lock\LockFactory::createPredisLock($predisRequest);
$lock->timeOut = 5;
$i = 0;

var_dump($lock->getMutexLock('msj-test-1', 1000), $i);
var_dump($lock->renewalLock('msj-test-1', 900));//TTL剩余900秒时进行续约



//var_dump($lock->releaseMutexLock('msj-test-1'));
//
//
//$lock->retry = 2;
//var_dump($lock->getBlockLock('msj_block_name'));
//var_dump($lock->releaseBlockLock('msj_block_name'));
//sleep(2);

//while (true){
//    var_dump($lock->getMutexLock('msj-test-1', 5), $i);
//    var_dump('lock:'.$lock->getMutexLock('msj-test-1', 5));
//    var_dump('unlock:'.$lock->releaseMutexLock('msj-test-1'));

//    $isBlock = $lock->getBlockLock('msj_block_name');
//    var_dump('is_lock:'.$isBlock);
//    var_dump('is_unlock:'.$lock->releaseBlockLock('msj_block_name'));
//    sleep(1);
    $i++;
//}
//var_dump($lock->releaseMutexLock('msj-test-2'));

$t2 = microtime(true);
echo '耗时'.round($t2-$t1,3).'秒<br>';
echo 'Now memory_get_usage: ' . memory_get_usage()/(1024*1024) . '<br />';

//基于PHP层面添加哨兵 不太合理，本身PHP是多进程语言，在这个方面go更适合，可以在获取到一把锁之后，开启一个延迟执行（锁持有时间的后三分之一开始）的Goroutine进行守护，只要主Goroutine没有执行完成，则他的哨兵则会持续将锁进行续约，如果发生服务器断电、OOM等突然场景，则当前应用均宕机，通过REDIS/其他存储介质均可正常释放锁。