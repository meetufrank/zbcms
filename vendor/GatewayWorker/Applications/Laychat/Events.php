<?php
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Db;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $data)
    {


        $message = json_decode($data, true);   //接受客户端数据
        $message_type = $message['type'];  //数据类型

        switch ($message_type) {

            case 'init':
                $message = json_decode($data, true);

                $cids = $message['data']['cid'];
                $userid = $message['data']['userid'];
                $_SESSION["cid"]= $cids;
                $_SESSION["userid"]= $userid;

                if(!empty($userid)){
                    //查询条件为  id== userid  的时候的一条数据
                    $db1 = Db::instance('db1');  //数据库链接
                    $user_info = $db1->select()->from('clt_whitelist_user_list')->where("id = $userid")->query();

                    //判断 上个客户端id是否为空    client_id

                    if(isset($user_info[0])){
                        $send_id=$user_info[0]['client_id'];
                        if(!empty($send_id)&&$send_id!=$client_id){

                            $message = [
                                'type' => 'logout',
                                'data' => [
                                    'username' => 'xsada',
                                    'send_id' => $send_id
                                ]
                            ];
                            //若不为空，则对查询出来的这个客户端id推送logout类型的消息
                            Gateway::sendToClient($send_id, json_encode($message));
                        }

                        $db1->query("update `clt_whitelist_user_list` set `client_id` = '".$client_id."' where `id` = $userid");
                    }

                }


                // 绑定到直播间id
                Gateway::joinGroup($client_id, $cids);


                return;
            case 'roofplacement':  //置顶消息

                $cid = $message['cid'];
                $nid = $message['nid'];

                //新增置顶消息
                $db1 = Db::instance('db1');  //数据库链接
                $notice_info_where = "id = ".$nid;
                $notice_info = $db1->select()->from('clt_notice')->where($notice_info_where)->query();
                $nctext = $notice_info[0]['ctext']; //置顶消息内容
                $nid = $notice_info[0]['id']; //置顶消息id
                $ndate = $notice_info[0]['ndate']; //置顶消息发送时间

                $notice = $db1->select()->from('clt_notice')->where("cid = $cid")->orderByDESC(array('id'))->query();

                //消息
               $message = [
                    'type' => 'roofplacement',
                    'data' => [
                        'username' => '管理员',
                        'avatar' => '',
                        'id' => $nid,   //置顶消息id
                        'type' =>'1',
                        'content' => $notice,  //置顶消息内容
                        'ndate' => $ndate,  //置顶消息时间
                        'timestamp' => time() * 1000,
                    ]
                ];




                return Gateway::sendToGroup($cid, json_encode($message), $client_id);

            case 'chatsend':  //客户端消息


                $nid = $message['nid'];  //聊天数据主键id
                $cid = $message['cid'];  //频道id

                //新增置顶消息
                $db1 = Db::instance('db1');  //数据库链接
                //保存聊天信息
                $db1 = Db::instance('db1');  //数据库链接
                $chatnum = $db1->query("SELECT count(*) as chatnum FROM `clt_chat` WHERE cid = $cid");
                $chatnums = $chatnum[0]['chatnum'];



                $chat_list = $db1->select()->from('clt_chat')->where("id = $nid")->query();   //查询出聊天数据
                $content = $chat_list[0]['content'];      //聊天内容
                $username = $chat_list[0]['username'];    //聊天用户姓名
                $avatar = $chat_list[0]['userimgurl'];    //用户头像路径
                $ctype = $chat_list[0]['ctype'];          //聊天类型
                $chattime = $chat_list[0]['chattime'];    //聊天时间
                $to_examine = $chat_list[0]['to_examine'];    //是否审核聊天数据   1为需要审核

                //消息
                $message = [
                    'message_type' => 'chatsend',
                    'data' => [
                        'username' => $username,
                        'avatar' => $avatar,
                        'id' => $nid,   //置顶消息id
                        'ctype' => $ctype,
                        'chatnum' => $chatnums,  //聊天总数
                        'content' => $content,  //置顶消息内容
                        'chattime' => $chattime,  //置顶消息时间
                        'to_examine' => $to_examine  //是否审核聊天数据
                    ]
                ];



                return Gateway::sendToGroup($cid, json_encode($message), $client_id);


            case 'prohibitions':  //禁言

                $cid = $message['cid'];
                $chatid = $message['chatid'];  //谁发的id


                $db1 = Db::instance('db1');  //数据库链接
                $chat_list = $db1->select()->from('clt_chat')->where("id = $chatid")->query();   //查询出聊天数据

                $userid = $chat_list[0]['userid'];

                //消息
                $message = [
                    'type' => 'prohibitions',
                    'data' => [
                        'userid' => $userid,
                    ]
                ];

                return Gateway::sendToGroup($cid, json_encode($message), $client_id);


            case 'liftaban':  //取消禁言

                $cid = $message['cid'];
                $chatid = $message['chatid'];  //谁发的id


                $db1 = Db::instance('db1');  //数据库链接
                $chat_list = $db1->select()->from('clt_chat')->where("id = $chatid")->query();   //查询出聊天数据

                $userid = $chat_list[0]['userid'];

                //消息
                $message = [
                    'type' => 'liftaban',
                    'data' => [
                        'userid' => $userid,
                    ]
                ];


                return Gateway::sendToGroup($cid, json_encode($message), $client_id);


            case 'adoptchat':  //审核通过互动聊天

                $cid = $message['cid'];
                $chatid = $message['chatid'];  //谁发的id

                //更改聊天数据审核状态
                $db1 = Db::instance('db1');  //数据库链接
                $update_where = "id = $chatid";
                $db1->query("update `clt_chat` set `to_examine` = '' where $update_where");
                $chat_list = $db1->select()->from('clt_chat')->where("id = $chatid")->query();   //查询出聊天数据
                $content = $chat_list[0]['content'];      //聊天内容
                $username = $chat_list[0]['username'];    //聊天用户姓名
                $avatar = $chat_list[0]['userimgurl'];    //用户头像路径
                $ctype = $chat_list[0]['ctype'];          //聊天类型
                $chattime = $chat_list[0]['chattime'];    //聊天时间
                $to_examine = $chat_list[0]['to_examine'];    //是否审核聊天数据   1为需要审核

                //消息
                $message = [
                    'type' => 'adoptchat',
                    'data' => [
                        'username' => $username,
                        'avatar' => $avatar,
                        'id' => $chatid,   //置顶消息id
                        'ctype' => $ctype,
                        'content' => $content,  //置顶消息内容
                        'chattime' => $chattime,  //置顶消息时间
                        'to_examine' => $to_examine  //是否审核聊天数据
                    ]
                ];

                return Gateway::sendToGroup($cid, json_encode($message), $chatid);
            case 'chatexamine':  //审核通过互动聊天

                $cid = $message['cid'];
                $cli_d = $message['client_id'];  //谁发的id
                //消息
                $message = [
                    'message_type' => 'chatexamine',
                    'data' => [
                        'ctype' => '1',
                        'username' => '上海网友',
                        'avatar' => 'https://static.mudu.tv/index/avatar.png',
                        'id' => $cid,
                        'type' => 'group',
                        'content' => $message['content'],
                        'timestamp' => time() * 1000,
                        'chatid' =>  $cid, //客户端消息
                    ]


                ];



                return Gateway::sendToGroup($cid, json_encode($message), $cli_d);

            case 'removezhiding':  //置顶消息删除
                $cid = $message['cid'];
                $zdid = $message['data'];  //置顶消息主键id


                //消息
                $message = [
                    'type' => 'removezhiding',
                    'data' => [
                        'zdid' =>  $zdid, //把删除掉的置顶消息id传递给客户端
                    ]
                ];
                //保存聊天信息
                $db1 = Db::instance('db1');  //数据库链接
                $db1->delete('clt_notice')->where("id = $zdid")->query();



                return Gateway::sendToGroup($cid, json_encode($message), $client_id);
            case 'chatdelete':  //删除聊天信息
                $cid = $message['cid'];      //直播频道id
                $chatid = $message['chatid'];  //聊天互动数据id
                //消息
                $message = [
                    'type' => 'chatdelete',
                    'data' => [
                        'chatid' =>  $chatid, //聊天互动数据id
                    ]
                ];
                //保存聊天信息
                $db1 = Db::instance('db1');  //数据库链接
                $db1->delete('clt_chat')->where("id = $chatid")->query();



               Gateway::sendToGroup($cid, json_encode($message), $client_id);
                return;
            case 'chattopping':  //置顶聊天信息
                $cid = $message['cid'];      //直播频道id
                $chatid = $message['chatid'];  //聊天互动数据id
                $db1 = Db::instance('db1');  //数据库链接


                $chat_list = $db1->select()->from('clt_chat')->where("id = $chatid")->query();

                $content = $chat_list[0]['content'];      //聊天内容
                $username = $chat_list[0]['username'];    //聊天用户姓名
                $avatar = $chat_list[0]['userimgurl'];    //用户头像路径
                $ctype = $chat_list[0]['ctype'];          //聊天类型
                $chattime = $chat_list[0]['chattime'];    //聊天时间


                //消息
                $message = [
                    'type' => 'chattopping',
                    'data' => [
                        'username' => $username,
                        'avatar' => $avatar,
                        'id' => $chatid,   //置顶消息id
                        'ctype' => $ctype,
                        'content' => $content,  //置顶消息内容
                        'chattime' => $chattime,  //置顶消息时间
                    ]
                ];


                Gateway::sendToGroup($cid, json_encode($message), $client_id);
                return;

            case 'cancelchattopping':  //取消置顶聊天信息
                $cid = $message['cid'];      //直播频道id
                $chatid = $message['chatid'];  //聊天互动数据id
                $db1 = Db::instance('db1');  //数据库链接


                $chat_list = $db1->select()->from('clt_chat')->where("id = $chatid")->query();
                $content = $chat_list[0]['content'];      //聊天内容
                $username = $chat_list[0]['username'];    //聊天用户姓名
                $avatar = $chat_list[0]['userimgurl'];    //用户头像路径
                $ctype = $chat_list[0]['ctype'];          //聊天类型
                $chattime = $chat_list[0]['chattime'];    //聊天时间


                //消息
                $message = [
                    'type' => 'cancelchattopping',
                    'data' => [
                        'username' => $username,
                        'avatar' => $avatar,
                        'id' => $chatid,   //置顶消息id
                        'ctype' => $ctype,
                        'content' => $content,  //置顶消息内容
                        'chattime' => $chattime,  //置顶消息时间
                    ]
                ];



                Gateway::sendToGroup($cid, json_encode($message), $client_id);
                return;
            case 'live':  //直播状态

                //页面直播状态
                $message = json_decode($data,true);

                $countdowntime = $message['data']['countdowntime'];  //获取到的倒计时时间
                @date_default_timezone_set(PRC);
                $current_time = date("Y/m/d H:i:s");  //当前系统时间

                if($countdowntime <= $current_time){
                    $ctype = 1;  //时间已到
                }else{
                    $ctype = 2;  //时间未到
                }

                //实时监听直播状态
                include_once 'live.php';
                $live  =new Ali_Lite();
                $live_type_info = $live -> describeLiveStreamsOnlineList("zhibo.meetv.com.cn","AppName");


                $live_type=0;
                if(isset($live_type_info['OnlineInfo'])){
                    foreach($live_type_info['OnlineInfo']['LiveStreamOnlineInfo'] as $k => $v){
                        $p_stname = $v['StreamName']."/";
                        if($p_stname == $message['data']['streamname']){
                            $live_type = 1;   //直播
                        }
                    }
                }



                //判断页面直播状态和监听直播状态
                $status = 0;
                if($message['data']['wdata'] != $live_type){
                    $status = 1;  //页面与直播监听冲突
                }

                $date = [
                    'type' => 'live',
                    'id' => $client_id,
                    'data' => $live_type,
                    'status' => $status,
                    'ctype' => $ctype,
                ];




                 Gateway::sendToClient($client_id, json_encode($date));
                return;
            case 'countdown':  //倒计时
                $message = json_decode($data,true);
                $countdowntime = $message['data']['countdowntime'];  //获取到的倒计时时间
                @date_default_timezone_set(PRC);
                $current_time = date("Y/m/d H:i:s");  //当前系统时间

                if($countdowntime <= $current_time){
                    $ctype = 1;  //时间已到
                }else{
                    $ctype = 2;  //时间未到
                }

                $date = [
                    'type' => 'countdown',
                    'ctype' => $ctype,
                ];

                Gateway::sendToClient($client_id, json_encode($date));
                return;
            case 'chatMessage':


                $message = json_decode($data, true);
                $cid = $message['data']['to']['id'];





                //查询频道审核状态
                $ctype_where = "cid = ".$cid;
                $ctype_info = $db1->select()->from('clt_chattype')->where($ctype_where)->query();
                if(empty($ctype_info[0]['ctype'])){
                    $ctype_info[0]['ctype'] = 2;  //如果不存在审核状态，赋值为2，默认不审核
                }


                $content = $message['data']['mine']['content'];   //聊天内容
                $username = $message['data']['mine']['username'];   //聊天用户名姓名
                $avatar = $message['data']['mine']['avatar'];   //聊天用户头像
                $cid = $message['data']['to']['id'];   //聊天频道id
                $chattime = date("Y-m-d H:i:s");   //聊天用户发送消息时间
                $ctype = 1;   //消息类型1为普通
                $chatid = $db1->query("insert into `clt_chat` (`content`,username,avatar,cid,chattime,$ctype) value('$content','$username','$avatar',$cid,'$chattime',$ctype)");

                //$chatid = Db::name('chat')->getLastInsID();



                //测试
                $message = [
                    'message_type' => 'chatMessage',
                    'data' => [
                        'ctype' => $ctype_info[0]['ctype'],
                        'username' => $message['data']['mine']['username'],
                        'avatar' => $message['data']['mine']['avatar'],
                        'id' => $cid,
                        'type' => 'group',
                        'content' => $message['data']['mine']['content'],
                        'timestamp' => time() * 1000,
                        'chatid' =>  $chatid, //客户端消息
                        'client_id' => $client_id
                    ]


                ];


             /*  $aa=[
                   $client_id,
                   $message['client']
               ];*/
          

                return Gateway::sendToGroup($cid, json_encode($message), $client_id);

            default:
                return;
            //echo $data;
        }
    }
    public static function onClose($client_id)
    {

        //维持连接的worker退出才触发
        $db1 = Db::instance('db1');  //数据库链接
        $where_tuichu = "cid = ". $_SESSION['cid']." and `userid` = ".$_SESSION['userid'];
        $leave_time = "'".date("Y-m-d H:i:s")."'"; //退出时间

        //设置用户为退出状态
        $db1->query("update `clt_user_record` set `leave_time` = $leave_time where $where_tuichu");

    }
    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    /*public static function onClose($client_id)
    {
        //维持连接的worker退出才触发
        if(!empty($_SESSION['user_id'])){
            $db1 = Db::instance('db1');  //数据库链接
            $where_tuichu = "cid = ". $_SESSION['pid']." and userid = ".$_SESSION['user_id'];
            $leave_time = date("Y-m-d H:is"); //退出时间
            //设置用户为退出状态
            $db1->query("update `clt_user_record` set `leave_time` = $leave_time where $where_tuichu");
        }

      public static function onClose($client_id)
    {
        //维持连接的worker退出才触发
        if(!empty($_SESSION['user_id'])){
            $db1 = Db::instance('db1');  //数据库链接
            //通知该用户的好友，该用户下线
            $friends = $db1->query("select `user_id` from `nd_cases_friends` where `friend_id` = " . $_SESSION['id']);
            if (!empty($friends)) {
                foreach ($friends as $key => $vo) {
                    $user_client_id = Gateway::getClientIdByUid($vo['user_id']);
                    if (!empty($user_client_id)) {
                        $online_message = [
                            'message_type' => 'logout',
                            'id' => $_SESSION['id'],
                        ];
                        Gateway::sendToClient($user_client_id['0'], json_encode($online_message));
                    }
                }
            }
            //设置用户为退出状态
            $db1->query("update `nd_cases_chatuser` set `status` = 0 where id = " . $_SESSION['id']);
        }
    }
    }*/
}
