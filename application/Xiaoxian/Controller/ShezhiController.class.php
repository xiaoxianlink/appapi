<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
//use Think\Log;
class ShezhiController extends IndexController {
	/**
	 * 设置接单开关状态
	 */
	public function jiedanKaiguan() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $type = $_REQUEST['type'];
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    /* start */
	    if($type == 1){//打开
	        //判断是否设置服务城市
	        $sc_model = M ("services_city");
	        $sc_info = $sc_model->where ( "services_id='$id' and state = 0 and code != 0")->find ();
	        if(empty($sc_info)){//未设置
	            $msg = array (
	                "code" => 401,
	                "msg" => '服务城市还未设置，开始接单状态不能设置为是。'
	            );
	            echo json_encode($msg);
	            $this->remove_boms();
	            exit();
	        }
	        //判断是否设置服务项目
	        $s_model = M ("services_code");
	        $s_info = $s_model->where ( "services_id='$id' and state = 0 and code != ''" )->find ();
	        if(empty($s_info)){//未设置
	            $msg = array (
	                "code" => 402,
	                "msg" => '服务项目还未设置，开始接单状态不能设置为是。'
	            );
	            echo json_encode($msg);
	            $this->remove_boms();
	            exit();
	        }
	        //判断是否设置服务定价
	        $so_model = M ("services_order");
	        $so_info = $so_model->where ( "services_id='$id'")->find ();
	        $sd_model = M("services_dyna");
	        $sd_info = $sd_model->field("id")->where("services_id=$id")->find();
	        if(empty($so_info) && empty($sd_info)){//未设置
	            $msg = array (
	                "code" => 403,
	                "msg" => '服务定价还未设置，开始接单状态不能设置为是。'
	            );
	            echo json_encode($msg);
	            $this->remove_boms();
	            exit();
	        }
	    }
	    /* end */
	    $model = M ("services");
	    if($type == 1){//打开
	        $data = array(
	            'status' => 0
	        );
	        $res = $model->where("phone = '$phone'")->save($data);
	        //推送消息（开始接单状态更新提醒）
	        if($res > 0){
	            $time = date("Y.m.d H:i:s");
	            $content = sprintf(content1_1, $time);
	            $title = title1;
	            $tz_content = sprintf(content1_1, $time);
	            $re = $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	        }
	    }
	    if($type == 2){//关闭
	        $data = array(
	            'status' => 1
	        );
	        $res = $model->where("phone = '$phone'")->save($data);
	        if($res > 0){
	            $time = date("Y.m.d H:i:s");
	            $content = sprintf(content1_2, $time);
	            $title = title1;
	            $tz_content = sprintf(content1_2, $time);
	            $re = $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	        }
	    }
	        //插入消息表
	        $this->add_message($id, 3, 1, '', $content);
	    
	    if(isset($res)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
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
	 * 设置-个人状态
	 */
	public function gerenzhuangtai(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id,status,state")->where("phone = '$phone'")->find();
	    //账户封存状态
	    if($s_id['state'] == 0){
	        $fengcun_state = 1;//正常
	    }else{
	        $fengcun_state = 0;//封存
	    }
	   
	    $id = $s_id['id'];//获取服务商id
	    
	    //是否设置服务城市
	    $sc_model = M ("services_city");
	    $sc_info = $sc_model->field("id")->where ( "services_id='$id' and state = 0 and code != 0" )->find ();
	    if(!empty($sc_info)){
	        $city_shehzi_state = 1; //已设置
	    }else {
	        $city_shehzi_state = 0; //未设置
	    }
	    //是否设置服务项目
	    if($city_shehzi_state == 0){
	        $services_shehzi_state = 0;//未设置
	    }else{
	        $s_model = M ("services_code");
	        $s_info = $s_model->field("id")->where ( "services_id='$id' and state = 0 and code != ''" )->find ();
	        if(!empty($s_info)){
	            $services_shehzi_state = 1; //已设置
	        }else {
	            $services_shehzi_state = 0; //未设置
	        }
	    }
	    //是否设置服务定价
	    if($city_shehzi_state == 0 || $services_shehzi_state == 0){
	        $dingjia_shehzi_state = 0;//未设置
	    }else{
	        $so_model = M ("services_order");
	        $so_info = $so_model->field("id")->where ( "services_id='$id'")->find ();
	        $sd_model = M("services_dyna");
	        $sd_info = $sd_model->field("id")->where("services_id=$id")->find();
	        if(!empty($so_info) || !empty($sd_info)){
	            $dingjia_shehzi_state = 1; //已设置
	        }else {
	            $dingjia_shehzi_state = 0; //未设置
	        }
	    }
	    //接单开关状态
	    if($city_shehzi_state == 0 || $services_shehzi_state == 0){
	        $kaiguan_shehzi_state = 0; //未设置
	    }else{
	        if($s_id['status'] == 0){
	            $kaiguan_shehzi_state = 1; //已设置
	        }else {
	            $kaiguan_shehzi_state = 0; //未设置
	        }
	    }
	    $set_array = array(
	        'city_shehzi' => $city_shehzi_state,
	        'services_shehzi' => $services_shehzi_state,
	        'dingjia_shehzi' => $dingjia_shehzi_state,
	        'kaiguan_shehzi' => $kaiguan_shehzi_state,
	        'fengcun_state' => $fengcun_state
	    );
	    if(isset($set_array)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "state"=>$set_array
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
	 * 版本更新接口
	 */
	public function update_version(){
	    $model = M ("version");
	    $version = $model->field('*')->order("version desc")->find();
	    if(empty($version)){
	        $version = '';
	    }
	    if(isset($version) || $version == ''){
	        $msg = array(
	            "code"=> 101,
	            "msg"=> '操作成功',
	            "version" => $version
	        );
	    }else{
	        $msg = array(
	            "code"=> 301,
	            "msg"=> '未知错误'
	        );
	    }
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	/**
	 * 帮助与规则更新接口
	 */
	public function help_update(){
	    $secret = $this->checkAccess($_REQUEST);
	    $model = M ("help");
	    $help = $model->field('*')->order("c_time desc")->find();
	    if(empty($help)){
	        $help = '';
	    }
	    if(isset($help) || $help == ''){
	        $msg = array(
	            "code"=> 101,
	            "msg"=> '操作成功',
	            "help" => $help
	        );
	    }else{
	        $msg = array(
	            "code"=> 301,
	            "msg"=> '未知错误'
	        );
	    }
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}

}
