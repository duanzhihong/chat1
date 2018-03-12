<?php
/**
 * Created by PhpStorm.
 * User: 二狗
 * Date: 2018/3/10
 * Time: 11:16
 */
require_once '/../../vendor/Workerman/Autoloader.php';
use Workerman\worker;
//使用websocket协议
$ws_worker=new worker('websocket://0.0.0.0:2000');
//设置进程
$ws_worker->count=1;
//设置一个属性，用来存放uid存放到connection中
$ws_worker->uidConnection=[];
//设置心跳间隔
define('HEARTBEAT_TIME',120);
/**
 * @param $connection
 * @param $data
 * @return mixed
 * 当客户端发送消息的的时候进行处理
 */
$ws_worker->onMessage=function($connection,$data)
{
    global $ws_worker;
    //如果当前用户没有进行验证
    if(!isset($connection->uid))
    {
       $connection->uid=time();
       //将用户的uid存放到数组中
        $ws_worker->uidConnection[$connection->uid]=$connection;
        $arr=['type'=>'id','xiaoxi'=>$connection->uid];
        return $connection->send(json_encode($arr));
    }
    //假设发送过来的消息格式是uid:message
     //对数据进行处理
    list($sendId,$message,$getId)=explode(':',$data);
    //判断是那种推送
    if($getId=='allMes')
    {
        sendAll($sendId,$message);
    }else
    {
        sendMessageToId($sendId,$message,$getId);
    }
};
/**
 * @param $sendId
 * @param $message
 * 向所有的用户进行推送
 */
function sendAll($sendId,$message)
{
    global $ws_worker;
    $arr=[];
    $arr['type']='allMes';
    $arr['sendId']=$sendId;
    $arr['xiaoxi']=$message;
    $send_connection=$ws_worker->uidConnection[$sendId];
    foreach($ws_worker->uidConnection as $connection)
    {
        if($send_connection!=$connection)
        {
            $connection->send(json_encode($arr));
        }
    }
}

/**
 * @param $sendId
 * @param $message
 * @param $getId
 * 给指定的用户发送消息
 */
function sendMessageToId($sendId,$message,$getId)
{
    global $ws_worker;
    if(isset($ws_worker->uidConnection[$getId]))
    {
        //对指定的用户发送消息
        $connection=$ws_worker->uidConnection[$getId];;
        $arr=['type'=>'mes','xiaoxi'=>$message,'sendId'=>$sendId];
        $connection->send(json_encode($arr));
    }
}

/**
 * @param $connection
 * 当用户断开连接的时候推送广播
 */
$ws_worker->onClose=function ($connection)
{
    //获取这个用户的id
    if(isset($connection->uid)&&!empty($connection->uid))
    {
        $uid=$connection->uid;
        //进行消息的群发
        global $ws_worker;
        $arr=[];
        $arr['type']='close';
        $arr['xiaoxi']=$uid.'下线';
        foreach($ws_worker->uidConnection as $connection)
        {
            $connection->send(json_encode($arr));
        }
    }
};

$ws_worker->onWorkerStart=function($ws_worker)
{
   //设置定时器，检测用户是否在线
    \Workerman\Lib\Timer::add(1,function()use($ws_worker){
        $time_now=time();
        global $ws_worker;
        foreach($ws_worker->uidConnection as $connection)
        {
           //如果用户没有接受过消息
            if(empty($connection->lastMessageTime))
            {
              $connection->lastMessageTime=$time_now;
              continue;
            }
           if($time_now-$connection->lastMessageTime>HEARTBEAT_TIME)
           {
             $connection->close();
           }
        }
    });
};
//运行worker
Worker::runAll();

