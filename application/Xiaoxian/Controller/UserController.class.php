<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
use Think\Log;
class UserController extends IndexController {
	/**
	 * 登录接口
	 */
	public function login() {
	    $phone = $_REQUEST['phone'];
	    $smsCode = $_REQUEST['smsCode'];
	    $clientUUID = $_REQUEST['clientUUID'];
	    //$password = $_REQUEST['password'];
	    if(empty($phone) || empty($smsCode) || empty($clientUUID)){
	        $msg = array (
	            "code" => 201,
	            "msg" => '请填写验证码'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    $duanxin_model = M ("duanxin");
	    //判断验证码是否正确
	    $res = $duanxin_model->field("id")->where("phone = '$phone' and code = '$smsCode'")->find();
	    if(empty($res)){
	        $msg = array (
	            "code" => 204,
	            "msg" => '您输入的验证码有误'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //查询短信验证码是否过期
	    $time = date("Y-m-d H:i:s");
	    $record = $duanxin_model->field("id")->where("phone = '$phone' and code = '$smsCode' and expires_time >'$time'")->find();
	    if(empty($record)){
	        $msg = array (
	            "code" => 202,
	            "msg" => '登录失败'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //判断是否第一次登录
	    $services_model = M ("services");
	    $services = $services_model->field("id,state,nickname,phone,(5-(all_nums-nums)*0.1 + nums*0.1) as grade,services_sn")->where("phone = '$phone'")->find();
	    if(!empty($services)){
	        //验证环信是否注册
	        $user_easemob = $this->userDetails($services['services_sn']);
	        $user_easemob = json_decode($user_easemob,TRUE);
	        if($user_easemob['count'] != 1){
	            //环信注册
	            $options['username'] = $services['services_sn'];
	            $options['password'] = '123456';
	            $easemob = $this->openRegister($options);
	            $easemob= json_decode($easemob,TRUE);
	            if($easemob['error']){
	                $msg = array (
	                    "code" => 205,
	                    "msg" => '环信注册失败'
	                );
	                echo json_encode ( $msg );
	                $this->remove_boms();
	                exit();
	            }
	        }
	    }
	    if(empty($services)){
	        //第一次登录，保存用户信息
	        $data = array();
	        $data['phone'] = $phone;
	        $data['create_time'] = time();
	        $data['state'] = 0;
	        $data['status'] = 1;
	        $data['grade'] = 5.00;
	        $data['nickname'] = substr_replace($phone,'****',3,4);
	        $services_id = $services_model->add($data);
	        $services_sn = $phone.$services_id;
	        $data2 = array("services_sn"=>$services_sn);
	        $services_model->where("id='$services_id'")->save($data2);
	        //查询第一次登录用户信息
	        $services = $services_model->field("id,state,nickname,phone,services_sn,(5-(all_nums-nums)*0.1 + nums*0.1) as grade")->where("phone = '$phone'")->find();
	        //账户管理表
	        $data2 = array(
	            'bank_id'=>$services['id'],
	            'create_time'=>time(),
	        );
	        $bank_model = M ('bank');
	        $bank_model->add($data2);
	        
	        //验证环信是否注册
	        $user_easemob = $this->userDetails($services['services_sn']);
	        $user_easemob = json_decode($user_easemob,TRUE);
	        if($user_easemob['count'] != 1){
	            //环信注册
	            $options['username'] = $services['services_sn'];
	            $options['password'] = '123456';
	            $easemob = $this->openRegister($options);
	            $easemob= json_decode($easemob,TRUE);
	            if($easemob['error']){
	                $msg = array (
	                    "code" => 205,
	                    "msg" => '环信注册失败'
	                );
	                echo json_encode ( $msg );
	                $this->remove_boms();
	                exit();
	            }
	        }
	    }
	    $token = $this->getAccessKey($clientUUID, $phone);
	  
	    $msg = array (
	             "code" => 101,
	             "msg" => '登录成功',
	             "token"=>$token,
	             "user"=>$services
	     );
	    
	
		echo json_encode ( $msg );
		$this->remove_boms();
		exit();
	}
	/**
	 * 注册接口
	 */
	/* public function register() {
	    $duanxin_model = M ("duanxin");
	    $phone = $_REQUEST['phone'];
	    $smsCode = $_REQUEST['smsCode'];
	    $clientUUID = $_REQUEST['clientUUID'];
	    $password = $_REQUEST['password'];
	    if(empty($phone) or empty($smsCode) or empty($clientUUID) or empty($password)){
	        $msg = array (
	            "code" => 201,
	            "msg" => '缺少必填参数'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //判断验证码是否正确
	    $res = $duanxin_model->field("id")->where("phone = '$phone' and code = '$smsCode'")->find();
	    if(empty($res)){
	        $msg = array (
	            "code" => 203,
	            "msg" => '您输入的验证码有误'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //查询短信验证码是否过期
	    $time = date("Y-m-d H:i:s");
	    $record = $duanxin_model->field("id")->where("phone = '$phone' and code = '$smsCode' and expires_time >'$time'")->find();
	    if(empty($record)){
	        $msg = array (
	            "code" => 202,
	            "msg" => '短信验证码已过期'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //注册
	    $services_model = M ("services");
	    if($_REQUEST['nickname'] != ''){
	        $data['nickname'] = $_REQUEST['nickname'];
	    }
	    if($_REQUEST['avatar'] != ''){
	        $data['avatar'] = $_REQUEST['avatar'];
	    }
	    $data['phone'] = $_REQUEST['phone'];
	    $data['create_time'] = date("Y-m-d H:i:s");
	    $data['state'] = 0;
	    $data['password'] = $_REQUEST['password'];
	    $services_model->add($data);
	    $user = $services_model->field('id,state,phone,nickname,avatar,password')->where("phone = '$phone'")->find();
	    $token = $this->getAccessKey($clientUUID, $phone);
	    $msg = array (
	        "code" => 101,
	        "msg" => '注册成功',
	        "token" => $token,
	        "user"=>$user
	    );
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	} */
	
	/**
	 * 修改用户信息接口
	 */
	public function editInfo() {
	    //验证token
		$secret = $this->checkAccess($_REQUEST);
		$phone = $_REQUEST['phone'];
		$data = array();
		/* if(!empty($_REQUEST[avatar])){
		    $data['avatar'] = $_REQUEST['avatar'];
		} */
		if($_REQUEST[nickname] != ''){
		    $data['nickname'] = $_REQUEST['nickname'];
		}
		if($_REQUEST[serverId] != ''){
		    $data['services_sn'] = $_REQUEST['serverId'];
		}
		$model = M ("services");
		$res = $model->where("phone = '$phone'")->save($data);
		if($res){
		    $msg = array (
		        "code" => 101,
		        "msg" => '修改成功',
		    );
		}else{
		    $msg = array (
		        "code" => 201,
		        "msg" => '修改失败',
		    );
		}
		echo json_encode($msg);
		$this->remove_boms();
		exit();
	}
	/**
	 * 修改手机号码接口
	 */
	public function bindNewPhone(){
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $newPhone = $_REQUEST['newPhone'];
	    $newPhoneSmsCode = $_REQUEST['newPhoneSmsCode'];
	    $oldPhoneSmsCode = $_REQUEST['oldPhoneSmsCode'];
	    $phone = $_REQUEST['phone'];
	    $duanxin_model = M ("duanxin");
	    //判断新号码是否和旧号码相同
	    if($newPhone == $phone){
	        $msg = array (
	            "code" => 501,
	            "msg" => '请输入新号码'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //判断验证码是否正确
	    $res = $duanxin_model->field("id")->where("phone = '$phone' and code = '$oldPhoneSmsCode'")->find();
	    if(empty($res)){
	        $msg = array (
	            "code" => 203,
	            "msg" => '您输入的验证码有误'
	        );
	        echo json_encode ( $msg );
	        $this->remove_boms();
	        exit();
	    }
	    //查询短信验证码是否过期
	     $time = date("Y-m-d H:i:s");
	     $record = $duanxin_model->field("id")->where("phone = '$phone' and code = '$oldPhoneSmsCode' and expires_time >'$time'")->find();
	     if(empty($record)){
	     $msg = array (
	     "code" => 202,
	     "msg" => '短信验证码已过期'
	     );
	     echo json_encode ( $msg );
	     $this->remove_boms();
	     exit();
	     }
	    $model = M ("services");
	    $res = $model->field("id")->where("phone = '$newPhone'")->find();
	    if(!empty($res)){
	        $msg = array (
	            "code" => 201,
	            "msg" => '新手机号码已经被注册',
	        );
	    }else{
	        //判断验证码是否正确
	        $res = $duanxin_model->field("id")->where("phone = '$newPhone' and code = '$newPhoneSmsCode'")->find();
	        if(empty($res)){
	            $msg = array (
	                "code" => 203,
	                "msg" => '您输入的验证码有误'
	            );
	            echo json_encode ( $msg );
	            $this->remove_boms();
	            exit();
	        }
	        //查询短信验证码是否过期
	         $duanxin_model = M ("duanxin");
	         $time = date("Y-m-d H:i:s");
	         $record = $duanxin_model->field("id")->where("phone = '$newPhone' and code = '$newPhoneSmsCode' and expires_time >'$time'")->find();
	         if(empty($record)){
    	         $msg = array (
        	         "code" => 202,
        	         "msg" => '短信验证码已过期'
    	         );
	         echo json_encode ( $msg );
	         $this->remove_boms();
	         exit();
	         }
	        $data = array();
	        $data['phone'] = $newPhone;
	        //$data['smsCode'] = $newPhoneSmsCode;
	        $model->where("phone = '$phone'")->save($data);
	        $msg = array (
	            "code" => 101,
	            "msg" => '修改成功',
	        );
	    }
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	
	
	/**
	 * 文件上传接口
	 */
	public function fileUpdate(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    if($_FILES == ''){
	        $msg = array (
	            "code" => 201,
	            "msg" => '缺少必填参数',
	        );
	        echo json_encode($msg);
	        $this->remove_boms();
	        exit();
	    }
	    $config = array (
	        'FILE_UPLOAD_TYPE' => sp_is_sae () ? "Sae" : 'Local', // TODO 其它存储类型暂不考虑
	        'rootPath' => './' . C ( "UPLOADPATH" ),
	        'savePath' => './file/',
	        'maxSize' => 2097152, // 2M
	        'saveName' => array (
	            'uniqid',
	            ''
	        ),
	        'exts' => array (
	            'jpg',
	            'png',
	            'jpeg'
	        ),
	        'autoSub' => false
	    );
	    $upload = new \Think\Upload ( $config ); //
	    $info = $upload->upload ( $_FILES );
	    $data = array ();
        $data ['avatar'] = $info ['file'] ['savename'];
	    $model = M ("services");
	    $res = $model->where("phone = '$phone'")->save($data);
	    if($res){
		    $msg = array (
		        "code" => 101,
		        "msg" => '上传成功',
		    );
		}else{
		    $msg = array (
		        "code" => 301,
		        "msg" => '未知错误',
		    );
		}
		echo json_encode($msg);
		$this->remove_boms();
		exit();
	}
	/**
	 * 获取服务商评分
	 */
	public function getGrade(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $phone = $_REQUEST['phone'];
	    $model = M ("services");
	    $info = $model->field("(5-(all_nums-nums)*0.1 + nums*0.1) as grade")->where("phone = '$phone'")->find();
	    if($info){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "grade"=>$info
	        );
	    }else{
	        $msg = array (
	            "code" => 201,
	            "msg" => '操作失败',
	        );
	    }
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	
}