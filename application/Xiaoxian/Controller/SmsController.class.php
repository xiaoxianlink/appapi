<?php

namespace Xiaoxian\Controller;

use Xiaoxian\Controller\IndexController;

class SmsController extends IndexController {
    /**
     * 获取验证码
     */
	public function getCode(){
	    $phone = $_REQUEST['phone'];
	    $type = $_REQUEST['type'];
	    //验证手机号码
	    if(!preg_match("/^13[0-9]{1}[0-9]{8}$|147[0-9]{8}|15[0-9]{1}[0-9]{8}|18[0-9]{1}[0-9]{8}$/", $phone)){
    	        $msg = array (
    	            "code" => 205,
    	            "msg" => '手机号码格式不正确'
    	        );
    	        echo json_encode ( $msg );
    	        $this->remove_boms();
    	        exit();
	    }
	    //判断请求是否超过60s
	    $model = M ("duanxin");
	    $now = date("Y-m-d H:i:s", time());
	    $record = $model->field("id,create_time")->where("phone = '$phone'")->find();
	    if(isset($record) && !empty($record)){
	        $ctime = strtotime($now) - strtotime($record['create_time']);
	        if($ctime < req_time){
	            $msg = array (
	                "code" => 201,
	                "msg" => '请求过于频繁'
	            );
	            echo json_encode ( $msg );
	            $this->remove_boms();
	            exit();
	        }
	    }
	    $model_services = M ("services");
	    //注册使用
	    /* if($type == 1){//判断是否已经注册
	        $res = $model_services->field("id")->where("phone = '$phone'")->find();
	        if(!empty($res)){
	            $msg = array (
	                "code" => 202,
	                "msg" => '已注册，请直接登录'
	            );
	            echo json_encode ( $msg );
	            $this->remove_boms();
	            exit();
	        }
	    } */
	    //登录使用
	    /* if($type == 2){
    	     $res = $model_services->field("id,state")->where("phone = '$phone'")->find();
    	     if($res['state'] == 1){
    	         $msg = array (
    	             "code" => 204,
    	             "msg" => '该账户已冻结'
    	         );
    	         echo json_encode ( $msg );
    	         $this->remove_boms();
    	         exit();
    	     }
	    } */
	    //生成验证码
	    $phone = trim ( $phone );
	    $key = '';
	    $pattern = '1234567890';
	    for($i = 0; $i < 6; $i ++) {
	        $key .= $pattern {mt_rand ( 0, 9 )}; // 生成php随机数
	    }
	    $time = date('Y-m-d H:i:s');
	    $r = $model->field("code")->where("phone = '$phone' and expires_time > '$time'")->find();//验证码未过期直接取出
	    if(empty($r)){
	        $num = $key;
	    }else{
	        $num = $r['code'];
	    }
	    $data = array ();
	    $data ['phone'] = $phone;
	    $data ['code'] = $num;
	    $data ['expires_time'] = date ( "Y-m-d H:i:s", time () + DUANXIN_TIME );
	    $data ['create_time'] = date ( 'Y-m-d H:i:s' );
	    if (isset ( $record ) && ! empty ( $record )) {
	        $model->where("id = '{$record['id']}'")->save($data);
	    } else {
	        $model->add($data);
	    }
	    $datas=array();
	    $datas [0] =$num;
	    $datas [1] =5;//提示有效时间
	    $res = $this->sendTemplateSMS($phone,$datas,tempId);
	    $msg = array (
	        "code" => 101,
	        "msg" => '短信发送成功',
	        "smsCode"=>$num,
	        "req_time"=>req_time
	    );
	    echo json_encode ( $msg );
	    $this->remove_boms();
	    exit();
	}
	
	
	/**
	 *
	 * 容联云通信API接入
	 */
	/**
	 * 发送模板短信
	 * @param to 短信接收彿手机号码集合,用英文逗号分开
	 * @param datas 内容数据
	 * @param $tempId 模板Id
	 */
	function sendTemplateSMS($to,$datas,$tempId)
	{
	    $AccountSid = AccountSid;
	    $AccountToken = AccountToken;
	    $AppId = AppId;
	    $ServerIP = ServerIP;
	    $ServerPort = ServerPort;
	    $SoftVersion = SoftVersion;
	    $Batch = date("YmdHis");  //时间戳
	    $BodyType = "json";//包体格式，可填值：json 、xml
	    //主帐号鉴权信息验证，对必选参数进行判空。
	    $auth=$this->accAuth($ServerIP,$ServerPort,$SoftVersion,$AccountSid,$AccountToken,$AppId);
	    if($auth!=""){
	        return $auth;
	    }
	    // 拼接请求包体
	    if($BodyType=="json"){
	        $data="";
	        for($i=0;$i<count($datas);$i++){
	            $data = $data. "'".$datas[$i]."',";
	        }
	        $body= "{'to':'$to','templateId':'$tempId','appId':'$AppId','datas':[  ".$data."]}";
	    }else{
	        $data="";
	        for($i=0;$i<count($datas);$i++){
	            $data = $data. "<data>".$datas[$i]."</data>";
	        }
	        $body="<TemplateSMS>
	        <to>$to</to>
	        <appId>$AppId</appId>
	        <templateId>$tempId</templateId>
	        <datas>".$data."</datas>
			</TemplateSMS>";
	    }
	    // 大写的sig参数
	    $sig =  strtoupper(md5($AccountSid . $AccountToken . $Batch));
	    // 生成请求URL
	    $url="https://$ServerIP:$ServerPort/$SoftVersion/Accounts/$AccountSid/SMS/TemplateSMS?sig=$sig";
	    // 生成授权：主帐户Id + 英文冒号 + 时间戳。
	    $authen = base64_encode($AccountSid . ":" . $Batch);
	    // 生成包头
	    $header = array("Accept:application/$BodyType","Content-Type:application/$BodyType;charset=utf-8","Authorization:$authen");
	    // 发送请求
	    $result = $this->curl_post($url,$body,$header,1,$BodyType);
	    $this->showlog ( "sms:$result" );
	    if($BodyType=="json"){//JSON格式
	        $datas=json_decode($result);
	    }else{ //xml格式
	        $datas = simplexml_load_string(trim($result," \t\n\r"));
	    }
	    //  if($datas == FALSE){
	    //            $datas = new stdClass();
	    //            $datas->statusCode = '172003';
	    //            $datas->statusMsg = '返回包体错误';
	    //        }
	    //重新装填数据
	    if($datas->statusCode==0){
	        if($BodyType=="json"){
	            $datas->TemplateSMS =$datas->templateSMS;
	            unset($datas->templateSMS);
	        }
	    }
	    return $datas;
	}
	
	/**
	 * 打印日志
	 *
	 * @param log 日志内容
	 */
	function showlog($log){
	    if($this->enabeLog){
	        fwrite($this->Handle,$log."\n");
	    }
	}
	
	/**
	 * 发起HTTPS请求
	 */
	function curl_post($url,$data,$header,$post=1)
	{
	    //初始化curl
	    $ch = curl_init();
	    //参数设置
	    $res= curl_setopt ($ch, CURLOPT_URL,$url);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt ($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_POST, $post);
	    if($post)
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
	    $result = curl_exec ($ch);
	    //连接失败
	    if($result == FALSE){
	        if($this->BodyType=='json'){
	            $result = "{\"statusCode\":\"172001\",\"statusMsg\":\"网络错误\"}";
	        } else {
	            $result = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><Response><statusCode>172001</statusCode><statusMsg>网络错误</statusMsg></Response>";
	        }
	    }
	
	    curl_close($ch);
	    return $result;
	}
	/**
	 * 主帐号鉴权
	 */
	function accAuth()
	{
	    if(ServerIP==""){
	        $data = new \stdClass();
	        $data->statusCode = '172004';
	        $data->statusMsg = 'IP为空';
	        return $data;
	    }
	    if(ServerPort<=0){
	        $data = new \stdClass();
	        $data->statusCode = '172005';
	        $data->statusMsg = '端口错误（小于等于0）';
	        return $data;
	    }
	    if(SoftVersion==""){
	        $data = new \stdClass();
	        $data->statusCode = '172013';
	        $data->statusMsg = '版本号为空';
	        return $data;
	    }
	    if(AccountSid==""){
	        $data = new\ stdClass();
	        $data->statusCode = '172006';
	        $data->statusMsg = '主帐号为空';
	        return $data;
	    }
	    if(AccountToken==""){
	        $data = new \stdClass();
	        $data->statusCode = '172007';
	        $data->statusMsg = '主帐号令牌为空';
	        return $data;
	    }
	    if(AppId==""){
	        $data = new \stdClass();
	        $data->statusCode = '172012';
	        $data->statusMsg = '应用ID为空';
	        return $data;
	    }
	}
}