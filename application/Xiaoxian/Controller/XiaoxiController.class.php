<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
use Think\Model;
//use Think\Log;
class XiaoxiController extends IndexController {
	/**
	 * 消息列表接口
	 */
	public function xiaoxilist() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $pageIndex = $_REQUEST['pageIndex'];
	    $pageSize = $_REQUEST['pageSize'];
	    $type = $_REQUEST['type'];
	    $model = M ("message");
	    if($type == 0){//小仙提醒
	        $where = "msg_type = 3 and openid = '$id'";
	    }
	    if($type == '1001'){//客服小仙
	        $where = "msg_type = 5 and openid = '$id'";
	    }
	    if($type == '2001'){//小仙账务
	        $where = "msg_type = 4 and openid = '$id'";
	    }
	    //分页算法
	    if ($pageIndex != '' && $pageSize != '') {
	        $pageID = ($pageIndex - 1);
	        if($pageID < 0){
	            $pageID =0;
	        }
	        $pageID=$pageID*$pageSize;
	    }
	    $list = $model->field(" id, msg_type, content,create_time,tixing_type,zhangwu_type ")->table("cw_message")->where($where)->order(array('create_time'=>'DESC'))->limit ( $pageID, $pageSize )->select();
	    foreach($list as $k=>$l){
	        $list[$k]['create_time'] = date("Y-m-d H:i:s",$l['create_time']);
	    }
	    if(isset($list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "list"=>$list
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
	 * 消息阅读接口
	 */
	public function read() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id

	    $type = $_REQUEST['type'];
	    if($type == 0){//小仙提醒
	        $where = "msg_type = 3 and openid = '$id'";
	    }
	    if($type == '1001'){//客服小仙
	        $where = "msg_type = 5 and openid = '$id'";
	    }
	    if($type == '2001'){//小仙账务
	        $where = "msg_type = 4 and openid = '$id'";
	    }
	    $model = M ("message");
	    $data = array(
	        'is_readed'=>1
	    );
	    $res = $model->where($where)->save($data);
	    if($res){
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
	 * 最新消息接口
	 */
	public function listfirst() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	     
	    $model = M ("message");
	    
	    $num_3 = $model->table("cw_message")->where("msg_type = 3 and is_readed = 0 and openid = '$id'")->count();
	    $listfirst_3 = $model->field("id,msg_type,content,create_time")->table("cw_message")->where("msg_type = 3 and openid = '$id'")->order(array('create_time'=>'DESC'))->limit ("1")->find();
	    if(empty($listfirst_3)){
	        $listfirst_3['create_time'] = 0;
	    }else{
	        $listfirst_3['create_time'] = date("Y-m-d H:i:s",$listfirst_3['create_time']);
	    }
	    $listfirst_3['num'] = $num_3;
	    
	    $num_4 = $model->table("cw_message")->where("msg_type = 4 and is_readed = 0 and openid = '$id'")->count();
	    $listfirst_4 = $model->field("id,msg_type,content,create_time")->table("cw_message")->where("msg_type = 4 and openid = '$id'")->order(array('create_time'=>'DESC'))->limit ("1")->find();
	    if(empty($listfirst_4)){
	        $listfirst_4['create_time'] = 0;
	    }else{
	        $listfirst_4['create_time'] = date("Y-m-d H:i:s",$listfirst_4['create_time']);
	    }
	    $listfirst_4['num'] = $num_4;
	    
	    $num_5 = $model->table("cw_message")->where("msg_type = 5 and is_readed = 0 and openid = '$id'")->count();
	    $listfirst_5 = $model->field("id,msg_type,content,create_time")->table("cw_message")->where("msg_type = 5 and openid = '$id'")->order(array('create_time'=>'DESC'))->limit ("1")->find();
	    if(empty($listfirst_5)){
	        $listfirst_5['create_time'] = 0;
	    }else{
	        $listfirst_5['create_time'] = date("Y-m-d H:i:s",$listfirst_5['create_time']);
	    }
	    $listfirst_5['num'] = $num_5;
	    
	    if(isset($listfirst_3) || isset($listfirst_4) || isset($listfirst_5)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "msg_tixing"=>$listfirst_3,
	            "msg_zhangwu"=>$listfirst_4,
	            "msg_kefu"=>$listfirst_5
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
}