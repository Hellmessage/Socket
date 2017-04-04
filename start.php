<?php
use Workerman\Worker;
require_once __DIR__ . '/Workerman/Autoloader.php';

$global_uid = 0;
// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
function handle_connection($connection){
    global $text_worker, $global_uid;
    // 为这个链接分配一个uid
    $connection->uid = ++$global_uid;
}

// 当客户端发送消息过来时，转发给所有人
function handle_message($connection, $data){
  $Json = json_decode($data);
  $type = $Json->type;
  if($type == "Login"){
    $connection->lid = $Json->lid;
  }
  global $text_worker;
  foreach($text_worker->connections as $conn){
    $List['uid'] = $connection->uid;
    $List['lid'] = $connection->lid;
    $List['data'] = $type;
    $conn->send(json_encode($List));
  }
}

// 当客户端断开时，广播给所有客户端
function handle_close($connection){
    global $text_worker;
    foreach($text_worker->connections as $conn){
        $conn->send("user[{$connection->uid}] logout");
    }
}

// 创建一个文本协议的Worker监听2347接口
$text_worker = new Worker("tcp://0.0.0.0:2347");
// 只启动1个进程，这样方便客户端之间传输数据
$text_worker->count = 1;
$text_worker->onConnect = 'handle_connection';
$text_worker->onMessage = 'handle_message';
$text_worker->onClose = 'handle_close';
Worker::runAll();
?>
