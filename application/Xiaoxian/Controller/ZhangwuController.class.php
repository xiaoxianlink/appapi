<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
use Think\Log;
class ZhangwuController extends IndexController {
	/**
	 * 账务详情接口
	 */
	public function detail() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $log = new Log();
	    $phone = $_REQUEST['phone'];
	    $model = M ("services");
	    $info = $model->field("b.money, b.user_money, b.user_bank, b.user_number,b.name,y.bank_img")->table("cw_services as s")->join("cw_bank as b on s.id = b.bank_id")->join("cw_yinhang as y on b.yh_id = y.id")
	            ->where("s.phone = '$phone' and y.state = 0")->find();
	    $info_notbind = $model->field("b.money as money_notbind, b.user_money as user_money_notbind")->table("cw_services as s")->join("cw_bank as b on s.id = b.bank_id")
	            ->where("s.phone = '$phone'")->find();
	    $model2 = M ("order");
	    $money_month = $model2->field("sum(o.pay_money) as money_month")->table("cw_order as o")->join("cw_turn_order as t on o.id = t.order_id")
	                   ->join("cw_services as s on o.services_id = s.id")->where("s.phone = '$phone' and o.order_status =  5 and DATE_FORMAT( FROM_UNIXTIME(o.last_time), '%Y-%m' ) = DATE_FORMAT( CURDATE() , '%Y-%m' )")->find();
	   
	    $info['money_month'] = $money_month['money_month'];
	    $info['money_month_notbind'] = $money_month['money_month'];
	    $info['money_notbind'] = $info_notbind['money_notbind'];
	    $info['user_money_notbind'] = $info_notbind['user_money_notbind'];
	    $info['tixian_fee'] = tixian_fee;
	    
	    if(isset($info)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "info"=>$info
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
	 * 收入列表
	 */
	 public function incomelist(){
	     //验证token
	     $secret = $this->checkAccess($_REQUEST);
	     
	     $type = $_REQUEST['type'];
	     $phone = $_REQUEST['phone'];
	     
	     $pageIndex = $_REQUEST['pageIndex'];
	     $pageSize = $_REQUEST['pageSize'];
	     //分页算法
	     if ($pageIndex != '' && $pageSize != '') {
	         $pageID = ($pageIndex - 1);
	         if($pageID < 0){
	             $pageID =0;
	         }
	         $pageID=$pageID*$pageSize;
	     }
	     if($type == '1001'){//收入列表
	         $model = M ("turn_order");
	         $where = "s.phone = '$phone' and o.order_status = 5";
	         $list = $model->field("t.id, o.pay_money ,o.last_time")->table("cw_turn_order as t")->join("cw_order as o on t.order_id = o.id")->join("cw_services_order as so on so.id = t.sod_id")->join("cw_services as s on so.services_id = s.id")->where($where)->order(array('o.last_time'=>'DESC'))->limit ( $pageID, $pageSize )->select();
	         foreach($list as $k=>$l){
	             $list[$k]['last_time'] = date("Y/m/d H:i",$l['last_time']);
	         }
	     }
	     if($type == '2001'){//提现列表
	         $model = M ("expend");
	         $list = $model->field("e.id,e.please_money,e.create_time")->table("cw_expend as e")->join("cw_bank as b on e.expend_id = b.id")->join("cw_services as s on b.bank_id = s.id")->where("s.phone = '$phone' and e.bank_state = 2")->order(array('e.create_time'=>'DESC'))->limit ( $pageID, $pageSize )->select();
	         foreach($list as $k=>$l){
	             $list[$k]['create_time'] = date("Y/m/d H:i",$l['create_time']);
	         }
	     }
	     if($type == '3001'){//冻结列表
	         $model = M ("turn_order");
	         $where = "s.phone = '$phone' and (o.order_status = 2 or o.order_status = 3)";
	         $list = $model->field("t.id, o.pay_money, o.last_time")->table("cw_turn_order as t")->join("cw_order as o on t.order_id = o.id")->join("cw_services_order as so on so.id = t.sod_id")->join("cw_services as s on so.services_id = s.id")->where($where)->order(array('o.last_time'=>'DESC'))->limit ( $pageID, $pageSize )->select();
	         foreach($list as $k=>$l){
	             $list[$k]['last_time'] = date("Y/m/d H:i",$l['last_time']);
	         }
	     }
	     if($type == 0){//全部(暂不包括提现)
	         $model = M ("turn_order");
	         $where = "s.phone = '$phone' and (o.order_status = 2 or o.order_status = 3 or o.order_status = 5)";
	         $list = $model->field("t.id, o.pay_money, o.last_time,o.order_status")->table("cw_turn_order as t")->join("cw_order as o on t.order_id = o.id")->join("cw_services_order as so on so.id = t.sod_id")->join("cw_services as s on so.services_id = s.id")->where($where)->order(array('o.last_time'=>'DESC'))->limit ( $pageID, $pageSize )->select();
	         foreach($list as $k=>$l){
	             $list[$k]['last_time'] = date("Y/m/d H:i",$l['last_time']);
	         }
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
	  * 收入详情
	  */
	 public function income(){
	     //验证token
	     $secret = $this->checkAccess($_REQUEST);
	     
	     $id = $_REQUEST['incomeid'];
	     $model = M ("turn_order");
	 
	     $info = $model->field("t.l_time, t.do_time ,t.finish_time,t.state, o.order_sn,o.last_time")->table("cw_turn_order as t")->join("cw_order as o on t.order_id = o.id")->where("t.id = '$id'")->find();
	     $info['c_time'] = date("Y/m/d H:i",$info['last_time']);
	     $info['l_time'] = date("Y/m/d",$info['l_time']);
	     $info['do_time'] = date("Y/m/d",$info['do_time']);
	     $info['finish_time'] = date("Y/m/d",$info['finish_time']);
	     if(isset($info)){
	         $msg = array (
	             "code" => 101,
	             "msg" => '操作成功',
	             "info"=>$info
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
	  * 提现详情
	  */
	 public function tixian_detail(){
	     //验证token
	     $secret = $this->checkAccess($_REQUEST);
	 
	     $id = $_REQUEST['tixian_id'];
	     $model = M ("expend");
	 
	     $info = $model->field("please_time,create_time,transfer_sn")->table("cw_expend")->where("id = '$id'")->find();
	     $info['c_time'] = date("Y/m/d H:i",$info['please_time']);
	     $info['l_time'] = date("Y/m/d",$info['please_time']);
	     $info['do_time'] = date("Y/m/d",$info['please_time']);
	     $info['finish_time'] = date("Y/m/d",$info['create_time']);
	     if(isset($info)){
	         $msg = array (
	             "code" => 101,
	             "msg" => '操作成功',
	             "info"=>$info
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
	  * 冻结详情
	  */
	 public function  frozen(){
	     //验证token
	     $secret = $this->checkAccess($_REQUEST);
	 
	     $id = $_REQUEST['dongjie_id'];
	     $model = M ("turn_order");
	 
	     $info = $model->field("o.last_time, t.l_time, o.order_sn")->table("cw_turn_order as t")->join("cw_order as o on t.order_id = o.id")->where("t.id = '$id'")->find();
	     $info['c_time'] = date("Y/m/d H:i",$info['last_time']);
	     $info['l_time'] = date("Y/m/d",$info['last_time']);
	     $info['do_time'] = date("Y/m/d",$info['l_time']);
	     $info['finish_time'] = date("Y/m/d",$info['l_time']);
	     if(isset($info)){
	         $msg = array (
	             "code" => 101,
	             "msg" => '操作成功',
	             "info"=>$info
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
