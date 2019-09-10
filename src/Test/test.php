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



$lock = \DistributedLock\Adapter\AdapterFactory::createPredisAdapter($predisRequest);
$lock->timeOut = 100000;
$i = 0;
//var_dump($lock->releaseMutexLock('msj-test-1'));


//$lock->retry = 2;
//var_dump($lock->getBlockLock('msj_block_name'));
//var_dump($lock->releaseBlockLock('msj_block_name'));
//sleep(2);

while (true){

//    var_dump($lock->getMutexLock('msj-test-1'), $i);
//    var_dump('lock:'.$lock->getMutexLock('msj-test-1'));
//    var_dump('unlock:'.$lock->releaseMutexLock('msj-test-1'));

    var_dump('lock:'.$lock->getBlockLock('msj_block_name'));
    var_dump('unlock:'.$lock->releaseBlockLock('msj_block_name'));
    sleep(1);
    $i++;


}
//var_dump($lock->releaseMutexLock('msj-test-2'));

$t2 = microtime(true);
echo '耗时'.round($t2-$t1,3).'秒<br>';
echo 'Now memory_get_usage: ' . memory_get_usage()/(1024*1024) . '<br />';