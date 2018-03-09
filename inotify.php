<?php

$server = new swoole_http_server('0.0.0.0', 18001);

date_default_timezone_set('Asia/Shanghai');

$server->set(array(
    'task_worker_num' => 1,
    'worker_num' => 1,
    'daemonize' => false,   //online open it
));

$server->on('Task', function ($serv, $task_id, $from_id, $task) {

});

$server->on('Finish', function ($serv, $task_id, $data) use ($server) {

});

$server->on('Request', function ($req, $resp) use ($server) {
    $method = $req->server;

});

$server->on('Start',function($serv){
    file_put_contents("/tmp/server.pid",$serv->master_pid);
    file_put_contents("/tmp/server.pid", ',' . $serv->manager_pid,FILE_APPEND);

    //set the master process name or set the pid
    cli_set_process_title("php http master");
});

$server->on('ManagerStart',function($serv){
    //set the manger process name or set the pid
    cli_set_process_title("php http manger");
});

$server->on('WorkerStart',function($serv){
    echo file_get_contents("test/test.php").PHP_EOL;
    //set the worker process name or set the pid
    cli_set_process_title("php http worker");
});

// 添加重新加载进程
$process = new \swoole_process(function (\swoole_process $process) {
    require __DIR__.'/src/Inotify.php';
    $reload=new Inotify(__DIR__."/test");
    $reload->run();
}, false);

$server->addProcess($process);

$server->start();