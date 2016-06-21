<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
//use Think\Log;
require_once "application/Weixin/Conf/config.php";
class JiedanController extends IndexController {
	/**
	 * 已接单列表接口
	 */
	public function yijiedanlist() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $pageIndex = $_REQUEST['pageIndex'];
	    $pageSize = $_REQUEST['pageSize'];
	    $type = $_REQUEST['type'];
	    $model = M ("turn_order");
	    if($type == 0){//正在进行中
	       $where = "t.state=3 and so.id = '$id'";
	    }
	    if($type == 1001){//办理完成
	        $where = "t.state=4 and so.id = '$id'";
	    }
	    if($type == 2001){//结算完成
	        $where = "t.state=5 and so.id = '$id' and b.order_status = 5";
	    }
	    //分页算法
	    if ($pageIndex != '' && $pageSize != '') {
	        $pageID = ($pageIndex - 1);
	        if($pageID < 0){
	            $pageID =0;
	        }
	        $pageID=$pageID*$pageSize;
	    }
	   
	    $list = $model->field("t.id,b.order_sn,b.pay_money,c.license_number,d.code,d.time,d.address,d.points,d.money,d.content,t.l_time,t.state,t.finish_time,t.c_time,b.services_id ")
	    ->table("cw_turn_order as t")->join ( "cw_services_order as so on t.sod_id=so.id" )->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")->join ( "cw_services as s on s.id = so.services_id" )
	    ->where($where)->order(array('t.l_time'=>'ASC'))->limit ( $pageID, $pageSize )->select();
	    foreach($list as $k=>$l){
	        $list[$k]['time'] = date("Y/m/d H:i:s",$l['time']);
	        $list[$k]['finish_time'] = date("Y/m/d H:i:s",$l['finish_time']);
	        $list[$k]['l_time'] = jishi2 + $l['l_time'];
	        $list[$k]['order_sn'] = $l['order_sn'] . substr ( $l ['c_time'], - 2 ) . $l ['services_id'];
	    }
	    if(isset($list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "yijiedanlist"=>$list
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
	 * 已结算列表接口
	 */
	/* public function yijiesuanlist() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $pageIndex = $_REQUEST['pageIndex'];
	    $pageSize = $_REQUEST['pageSize'];
	    $model = M ("turn_order");
	    $count = $model->field("b.order_sn,b.pay_money,c.license_number,d.code,d.time,d.address,d.points,d.money,d.content,t.l_time ")
	            ->table("cw_turn_order as t")->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")
	            ->where("t.state = 5")->count ();
	    $page = $this->page ( $count, $pageSize );
	    $list = $model->field("b.order_sn,b.pay_money,c.license_number,d.code,d.time,d.address,d.points,d.money,d.content,t.l_time ")
	            ->table("cw_turn_order as t")->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")
	            ->where("t.state = 5")->order(array('t.c_time'=>'DESC'))->limit ( $page->firstRow . ',' . $page->listRows )->select();
	    foreach($list as $k=>$l){
	        $list[$k]['time'] = date("Y/m/d H:i:s",$l['time']);
	        $list[$k]['l_time'] = date("H:i:s",$l['l_time']);
	    }
	    if(isset($list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "yijiesuanlist"=>$list
	        );
	    }else{
	        $msg = array (
	            "code" => 301,
	            "msg" => '未知错误',
	        );
	    }
	    echo json_encode($msg);
	    exit();
	} */
	
	/**
	 * 接单详情接口
	 */
	public function detail() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $id = $_REQUEST['id'];
	    $model = M ("turn_order");
	    $count=$model->table("cw_turn_order as t")->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")->where("t.id = '$id'")->count ();
	    $info = $model->field("b.order_sn,b.pay_money,c.license_number,c.frame_number,c.engine_number,d.code,d.time,d.address,d.points,d.money,d.content,t.l_time,t.state,t.c_time,b.services_id ")
	    ->table("cw_turn_order as t")->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")
	    ->where("t.id = '$id'")->find();
	    $info['time'] = date("Y/m/d H:i:s",$info['time']);
	    $info['l_time'] = jishi2 + $info['l_time'];
	    $info['order_sn'] = $info['order_sn'] . substr ( $info ['c_time'], - 2 ) . $info ['services_id'];
	    if($info){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "detail"=>$info
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
	 * 接单完成
	 */
	public function complete(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $id = $_REQUEST['id'];
	    
	    //判断账号状态是否封存
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $services_info = $services_model->where("phone = '$phone'")->find();
	    if($services_info['state'] == 1){
	        $msg = array (
	            "code" => 401,
	            "msg" => '账号已被封存，操作失败。',
	        );
	        echo json_encode($msg);
	        $this->remove_boms();
	        exit();
	    }
	    
	    $model = M ("turn_order");
	    $data = array(
	        'state' => 4,
	        'l_time' => time (),
	        'finish_time'=>time()
	    );
	    $res = $model->where("id = $id")->save($data);
	    if($res>0){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功'
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