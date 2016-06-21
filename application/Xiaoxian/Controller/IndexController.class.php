<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Xiaoxian\Controller;

use Common\Controller\HomeBaseController;
use Think\Log;
require_once "application/Common/getui/IGt.Push.php";
require_once "application/Weixin/Conf/config.php";

class IndexController extends HomeBaseController {
    /**
     * 生产secret，并更新secret表中的记录,登录时候会更新记录，secret即将过期时候也会更新记录
     *
     * @param unknown $clientUUID
     * @param unknown $username
     * @param unknown $data
     * @return string
     */
    public function getAccessKey($clientUUID, $username) {
        $key_offset = time ();
        $key = md5 ( $clientUUID . $username . $key_offset );
        $key = base64_encode ( $key );
        $key = base64_encode ( $key );
        $key = substr ( $key, 0, 32 );
        $data = array ();
        $data['update_time'] = date("Y-m-d H:i:s");
        $data ['secret'] = $key;
        if(isset($_SERVER ['HTTP_USER_AGENT'])&&!empty($_SERVER ['HTTP_USER_AGENT'])){
            $data ['user_agent'] = $_SERVER ['HTTP_USER_AGENT'];
        }else{
            $data ['user_agent'] = "no User-Agent";
        }
    
    
        $time = time ();
        $expires_time = date ( 'Y-m-d H:i:s', strtotime ( '+' . VALID_DAYS . ' day', $time ) );
        $data ['expires_time'] = $expires_time;
         
        $model = M ("secret");
    
        $record = $model->field ("id")->where ( "username = '$username' and clientUUID = '$clientUUID'" )->find();
        if (isset ( $record ) && ! empty ( $record )) {
            $model->where("id = '{$record['id']}'")->save($data);
        } else {
            $data ['clientUUID'] = $clientUUID;
            $data ['username'] = $username;
            $model->add ( $data );
        }
        return $key;
    }
    /**
     * 验证secret
     * @param unknown $req  必须有$req ['secret']，$req ['username']，$req ['clientUUID']
     * @return string
     */
    public function checkAccess($req) {
        $log = new Log();
        $debugAccessKey = "debug";
        $needCheckAcccessKey = true;
        $ErrCodeAccessKey = "401";
    
        $secret = trim ( $req['token'] );
        $username = trim ( $req['phone'] );
        $clientUUID = trim ( $req['clientUUID']);
    
        $currentSecret = '';
    
        if ($debugAccessKey == $secret) {
            // 调试模式下
            if (SECRET_DEBUG_ENABLE) {
                return "debug";
            } else {
                $needCheckAcccessKey = true;
            }
        } else {
           $model = M ("secret");
           $record = $model->field ( 'id,secret,expires_time' )->where("username = '$username'")->order('update_time desc')->find();
            // 此处可实现一个账户同时只能一台设备登录
            if (!ONE_DEVICE_ENABLE) {
                $record = $model->field ( 'id,secret,expires_time' )->where("username = '$username' and clientUUID = '$clientUUID'")->find();
            }
            if (isset ( $record ) && ! empty ( $record )) {
                $db_secret = $record['secret'];
                if ($secret == $db_secret) {
                    // 检查是否将要过期
                    $expires_time = strtotime ( $record['expires_time'] );
                    $d = $expires_time - time ();
                    if ($d< UPDATE_TIME&&$d>0) {
                        // 将要在24小时内过期，则更新secret
                        $currentSecret = $this->getAccessKey ( $clientUUID, $username );
                        return $currentSecret;
                    } else if($d>=UPDATE_TIME){
                        return '';
                    }else{
                        $model->where("id = '{$record['id']}'")->delete();
                        $needCheckAcccessKey = true;
                    }
                } else {
                    $needCheckAcccessKey = true;
                }
            } else {
                $needCheckAcccessKey = true;
            }
        }
        // secret 认证错误
        if ($needCheckAcccessKey) {
            $res = array ();
            $res ['code'] = $ErrCodeAccessKey;
            $res ['msg'] = 'token认证失败';
            echo json_encode ( $res );
            $this->remove_boms();
            exit();
        }
    }
    /**
     * 去除BOM
     */
    public function remove_boms(){
        $basedir = $_GET['dir'];
        
        $auto = 1;
        
        $this->checkdir($basedir);
    }
    function checkdir($basedir){
        if ($dh = opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..'){
                    if (!is_dir($basedir."/".$file)) {
                        echo "filename
                        $basedir/$file ".$this->checkBOM("$basedir/$file")." <br>";
                    }else{
                        $dirname = $basedir."/".$file;
                        $this->checkdir($dirname);
                    }
                }
            }
            closedir($dh);
        }
    }
    
    function checkBOM ($filename) {
        global $auto;
        $contents = file_get_contents($filename);
        $charset[1] = substr($contents, 0, 1);
        $charset[2] = substr($contents, 1, 1);
        $charset[3] = substr($contents, 2, 1);
        if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
            if ($auto == 1) {
                $rest = substr($contents, 3);
                $this->rewrite ($filename, $rest);
                return ("<font color=red>BOM found, automatically removed.</font>");
            } else {
                return ("<font color=red>BOM found.</font>");
            }
        }
        else return ("BOM Not Found.");
    }
    
    function rewrite ($filename, $data) {
        $filenum = fopen($filename, "w");
        flock($filenum, LOCK_EX);
        fwrite($filenum, $data);
        fclose($filenum);
    }
    
    /**
     * 开放注册模式
     *
     * @param $options['username'] 用户名
     * @param $options['password'] 密码
     */
    public function openRegister($options) {
        $url = url . "users";
        $header = array();
        array_push($header, 'Content-Type:application/json');
        $result = $this->postCurl ( $url, $options, $header );
    
        return $result;
    }
    /**
     * 获取指定用户详情
     *
     * @param $username 用户名
     */
   public function userDetails($username) {
        $url = url . "users/" . $username;
        $access_token = $this->getToken ();
        $header [] = 'Authorization: Bearer ' . $access_token;
        $result = $this->postCurl ( $url, '', $header, $type = 'GET' );
        return $result;
    }
    
    /**
     * 发送消息(环信)
     *
     * @param string $from_user
     *        	发送方用户名
     * @param array $username
     *        	array('1','2')
     * @param string $target_type
     *        	默认为：users 描述：给一个或者多个用户(users)或者群组发送消息(chatgroups)
     * @param string $content
     * @param array $ext
     *        	自定义参数
     */
   /*  function yy_hxSend($username, $content, $target_type = "users", $from_user = "admin") {
        $option ['target_type'] = $target_type;
        $option ['target'] = $username;
        $params ['type'] = "txt";
        $params ['msg'] = $content;
        $option ['msg'] = $params;
        $option ['from'] = $from_user;
        //$option ['ext'] = $ext;
        $url = url . "messages";
        $access_token = $this->getToken ();
        $header [] = 'Authorization: Bearer ' . $access_token;
        $result = $this->postCurl ( $url, $option, $header );
        return $result;
    } */
    /**
     * 获取Token
     */
    public function getToken() {
        $option ['grant_type'] = "client_credentials";
        $option ['client_id'] = client_id;
        $option ['client_secret'] = client_secret;
        $url = url . "token";
        /* 		$fp = @fopen ( "easemob.txt", 'r' );
         if ($fp) {
         $arr = unserialize ( fgets ( $fp ) );
         if ($arr ['expires_in'] < time ()) {
         $result = $this->postCurl ( $url, $option, $head = 0 );
         $result ['expires_in'] = $result ['expires_in'] + time ();
         @fwrite ( $fp, serialize ( $result ) );
         return $result ['access_token'];
         fclose ( $fp );
         exit ();
         }
         return $arr ['access_token'];
         fclose ( $fp );
         exit ();
         }
         */
        $header = array();
        array_push($header, 'Content-Type:application/json');
        $result = $this->postCurl ( $url, $option, $header );
        $result = json_decode($result);
        //$result ['expires_in'] = $result ['expires_in'] + time ();
        /* $fp = @fopen ( "easemob.txt", 'w' );
         @fwrite ( $fp, serialize ( $result ) ); */
        return $result->access_token;
        //fclose ( $fp );
    }
    
    /**
     * CURL Post
     */
    private function postCurl($url, $option, $header, $type = 'POST') {
        $curl = curl_init (); // 启动一个CURL会话
        curl_setopt ( $curl, CURLOPT_URL, $url ); // 要访问的地址
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE ); // 对认证证书来源的检查
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, FALSE ); // 从证书中检查SSL加密算法是否存在
        curl_setopt ( $curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)' ); // 模拟用户使用的浏览器
        if (! empty ( $option )) {
            $options = json_encode ( $option );
            curl_setopt ( $curl, CURLOPT_POSTFIELDS, $options ); // Post提交的数据包
        }
        curl_setopt ( $curl, CURLOPT_TIMEOUT, 30 ); // 设置超时限制防止死循环
        curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header ); // 设置HTTP头
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 ); // 获取的信息以文件流的形式返回
        curl_setopt ( $curl, CURLOPT_CUSTOMREQUEST, $type );
        $result = curl_exec ( $curl ); // 执行操作
        //$res = object_array ( json_decode ( $result ) );
        //$res ['status'] = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
        //pre ( $res );
        curl_close ( $curl ); // 关闭CURL会话
        return $result;
    }
    
    
    //单推接口案例（个推）
    function pushMessageToSingle($content,$title,$tz_content,$alias){
        
        $igt = new \IGeTui(NULL,APPKEY,MASTERSECRET,false);
    
        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板
    
        //    	$template = IGtNotyPopLoadTemplateDemo();
        //    	$template = IGtLinkTemplateDemo();
        $template = $this->IGtNotificationTemplateDemo($content,$title,$tz_content);
        //$template = IGtTransmissionTemplateDemo();
    
        //个推信息体
        $message = new \IGtSingleMessage();
    
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600*12*1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        //	$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //接收方
        $target = new \IGtTarget();
        $target->set_appId(appid);
        //$target->set_clientId(dd55bafc6d02330b3aebfc48b5eb2dc4);
        $target->set_alias($alias);
    
        try {
            $rep = $igt->pushMessageToSingle($message, $target);
            //var_dump($rep);
            //echo ("<br><br>");
    
        }catch(\RequestException $e){
            $requstId =e.getRequestId();
            $rep = $igt->pushMessageToSingle($message, $target,$requstId);
            //var_dump($rep);
            //echo ("<br><br>");
        }
    
    }
    
    function IGtNotificationTemplateDemo($content,$title,$tz_content){
        $template =  new \IGtNotificationTemplate();
        $template->set_appId(appid);//应用appid
        $template->set_appkey(APPKEY);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($content);//透传内容
        $template->set_title($title);//通知栏标题
        $template->set_text($tz_content);//通知栏内容
        $template->set_logo("");//通知栏logo
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(true);//通知栏是否可清除
        return $template;
    }
    /**
     * 插入消息表
     */
    public function add_message($services_id, $msg_type, $tixing_type, $zhangwu_type, $content){
        $model_msg = M ("message");
        $data = array(
            'from_userid'=>0,
            'openid'=>$services_id,
            'msg_type'=>$msg_type,
            'tixing_type'=>$tixing_type,
            'zhangwu_type'=>$zhangwu_type,
            'create_time'=>time(),
            'content'=>$content,
            'is_readed'=>0
        );
        $model_msg->add($data);
    }
    
    
}
