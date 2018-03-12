$(function(){
    //实例化webwocket对象
    ws=new WebSocket('ws://127.0.0.1:2000');
    //点击发送后获取用户的id
    $('input[name=getUserId]').on('click',function(){
        ws.send('get id');
    });
    //点击后发送指定的消息
    $('input[name=send]').on('click',function(){
      //获取用户的id
        var sendId=$('input[name=userId]').val();
      //获取发送的内容
        var text=$('#chatMessage').val();
      //获取对方用户的id
        var getId=$('input[name=get]').val();
      //组成字符串
        var str=sendId+':'+text+':'+getId;
      ws.send(str);
    });
    //点击后进行全体消息推送
    $('input[name=qunfa]').on('click',function(){
      //进行消息的群发
        var sendId=$('input[name=userId]').val();
      //获取发送的内容
        var text=$('#chatMessage').val();
      //群发的标志
        var getId='allMes';
        var str=sendId+':'+text+':'+getId;
        ws.send(str);
    });

    //接收到的消息进行处理
    ws.onmessage=function(e)
    {
        //将json字符串转换为json对象
        var json_obj=JSON.parse(e.data);
        if(json_obj.type=='id')
        {
           //将用户的id补全
            $('input[name=userId]').val(json_obj.xiaoxi);
        }else if(json_obj.type=='mes')
        {
           //向指定的客户端发送消息
            alert('用户'+json_obj.sendId+":"+json_obj.xiaoxi);
        }else if(json_obj.type=='allMes')
        {
          //群发
            alert('用户'+json_obj.sendId+":"+json_obj.xiaoxi);
        }else if(json_obj.type=='close')
        {
            alert(json_obj.xiaoxi);
        }
    };
})




















