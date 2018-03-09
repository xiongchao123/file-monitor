<?php


//需要柔和重启的进程ID
$pid=8088;

require __DIR__.'/src/Boom/Process/AutoReload.php';
$reload = new \Boom\Process\AutoReload($pid);
$reload->watch(__DIR__.'/test');
//设置文件后缀
$reload->addFileType('.php');
$reload->run();

