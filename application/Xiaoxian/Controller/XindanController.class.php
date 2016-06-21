<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
use Think\Model;
require_once "application/Weixin/Conf/config.php";
//use Think\Log;
class XindanController extends IndexController {
	/**
	 * 新单列表接口
	 */
	public function xindanlist() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $pageIndex = $_REQUEST['pageIndex'];
	    $pageSize = $_REQUEST['pageSize'];
	    $model = M ("turn_order");
	    //分页算法
	    if ($pageIndex != '' && $pageSize != '') {
	        $pageID = ($pageIndex - 1);
	        if($pageID < 0){
	            $pageID =0;
	        }
	        $pageID=$pageID*$pageSize;
	        //$sql .= " LIMIT $pageSize OFFSET $pageID";
	    }
	    $list = $model->field("b.order_sn,b.pay_money,c.frame_number,c.engine_number,c.license_number,d.code,d.time,d.address,d.points,d.money,d.content,t.l_time,t.id,t.state,t.c_time,so.services_id ")
	            ->table("cw_turn_order as t")->join ( "cw_services_order as so on t.sod_id=so.id" )->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")->join ( "cw_services as s on s.id = so.services_id" )
	            ->where("t.state = 0 and so.services_id = '$id'")->order(array('t.l_time'=>'ASC'))->limit ($pageID,$pageSize)->select();
	    foreach($list as $k=>$l){
	        $list[$k]['time'] = date("Y/m/d H:i:s",$l['time']);
	        $list[$k]['l_time'] = jishi1 + $l ['l_time'];
	        $list[$k]['order_sn'] = $l['order_sn'] . substr ( $l ['c_time'], - 2 ) . $l ['services_id'];
	        //$list[$k]['so_id'] = $l ['order_sn'] . substr ( $l ['c_time'], - 2 ) . $v ['s_id'];
	    }
	    if(isset($list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "xindanlist"=>$list
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
	 * 即将过期提醒(JAVA调用)
	 */
	public function overdue_remind(){
	    $model_services = M ("services");
	    $services = $model_services->where("state = 0")->select();
	    $model = M ("turn_order");
	    foreach ($services as $s){
	        $where = "s.id = $s[id] and (t.state = 0 or t.state = 3)";
	        $list = $model->field("t.id,t.state,t.l_time,b.order_sn,t.c_time,b.services_id")->table("cw_turn_order as t")->join ( "cw_services_order as so on t.sod_id=so.id" )->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")->join ( "cw_services as s on s.id = so.services_id" )
	        ->where($where)->order(array('t.c_time'=>'DESC'))->select();
	        foreach($list as $k=>$l){
	            $order_sn_to = $l['order_sn'] . substr ( $l ['c_time'], - 2 ) . $l ['services_id'];
	            if($l['state'] == 0){
	                //新单即将过期提醒
	                $time = jishi1 - (time () - $l ['l_time']);
	                if($time <= 6*3600){
	                    $model_msg = M ("message");
	                    $m_info = $model_msg->where("order_id = '{$l['id']}'")->find();
	                    if(empty($m_info)){
	                        $content = sprintf(content2_2, $order_sn_to);
	                        $title = title2;
	                        $tz_content = sprintf(content2_2, $order_sn_to);
	                        $this->pushMessageToSingle($content, $title,$tz_content,$s['phone']);
	                        //插入消息表
	                        $data = array(
	                            'from_userid'=>0,
	                            'openid'=>$s['id'],
	                            'msg_type'=>3,
	                            'tixing_type'=>2,
	                            'create_time'=>time(),
	                            'content'=>$content,
	                            'is_readed'=>0,
	                            'order_id'=>$l['id']
	                        );
	                        $model_msg->add($data);
	                    }
	                }
	                if($time <= 2*3600){
	                    $model_msg = M ("message");
	                    $content = sprintf(content2, $order_sn_to);
	                    $m_info = $model_msg->where("order_id = '{$l['id']}' and content = '$content'")->find();
	                    if(empty($m_info)){
	                        $title = title2;
	                        $tz_content = sprintf(content2, $order_sn_to);
	                        $this->pushMessageToSingle($content, $title,$tz_content,$s['phone']);
	                        //插入消息表
	                        $data = array(
	                            'from_userid'=>0,
	                            'openid'=>$s['id'],
	                            'msg_type'=>3,
	                            'tixing_type'=>2,
	                            'create_time'=>time(),
	                            'content'=>$content,
	                            'is_readed'=>0,
	                            'order_id'=>$l['id']
	                        );
	                        $model_msg->add($data);
	                    }
	                }
	            }
	            if($l['state'] == 3){
	                //接单处理即将过期提醒
	                $time = jishi2 - (time () - $l ['l_time']);
	                if($time <= 3600*24){
	                    $model_msg = M ("message");
	                    $m_info = $model_msg->where("order_id = '{$l['id']}'")->find();
	                    if(empty($m_info)){
	                        $content = sprintf(content3_1, $order_sn_to);
	                        $title = title3;
	                        $tz_content = sprintf(content3_1, $order_sn_to);
	                        $this->pushMessageToSingle($content, $title,$tz_content,$s['phone']);
	                        //插入消息表
	                        $data = array(
	                            'from_userid'=>0,
	                            'openid'=>$s['id'],
	                            'msg_type'=>3,
	                            'tixing_type'=>3,
	                            'create_time'=>time(),
	                            'content'=>$content,
	                            'is_readed'=>0,
	                            'order_id'=>$l['id']
	                        );
	                        $model_msg->add($data);
	                    }
	                }
	                if($time <= 3600*6){
	                    $model_msg = M ("message");
	                    $content = sprintf(content3_2, $order_sn_to);
	                    $m_info = $model_msg->where("order_id = '{$l['id']}' and content = '$content'")->find();
	                    if(empty($m_info)){
	                        $title = title3;
	                        $tz_content = sprintf(content3_2, $order_sn_to);
	                        $this->pushMessageToSingle($content, title3,$tz_content,$s['phone']);
	                        //插入消息表
	                        $data = array(
	                            'from_userid'=>0,
	                            'openid'=>$s['id'],
	                            'msg_type'=>3,
	                            'tixing_type'=>3,
	                            'create_time'=>time(),
	                            'content'=>$content,
	                            'is_readed'=>0,
	                            'order_id'=>$l['id']
	                        );
	                        $model_msg->add($data);
	                    }
	                }
	            }
	        }
	    }
	    $msg = array (
	        "code" => 101,
	        "msg" => '操作成功',
	    );
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	
	/**
	 * 新单详情接口
	 */
	public function detail() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $xindanId = $_REQUEST['xindanId'];
	    $model = M ("turn_order");
	    //$count=$model->table("cw_turn_order as t")->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")->where("t.id = '$xindanId'")->count ();
	    $info = $model->field("b.order_sn,b.pay_money,c.license_number,c.frame_number,c.engine_number,d.code,d.time,d.address,d.points,d.money,d.content,t.l_time,t.c_time,b.services_id ")
	            ->table("cw_turn_order as t")->join("cw_order as b on b.id=t.order_id")->join("cw_car as c on b.car_id=c.id")->join("cw_endorsement as d on b.endorsement_id=d.id")
	            ->where("t.id = '$xindanId'")->find();
	        $info['time'] = date("Y/m/d H:i:s",$info['time']);
	        $info['l_time'] = jishi1 + $info['l_time'];
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
	 * 新单办不了
	 */
	public function refuse(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $phone = $_REQUEST['phone'];
	    //判断账号状态是否封存
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
	    
	    $id = $_REQUEST ['xindanId'];
	    $model = M ( "turn_order" );
	    $info = $model->where ( "id='$id'" )->find ();
	    $to_list = $model->field ( "tos.id,so.id as so_id,s.id as s_id,s.phone,tos.c_time,tos.state,tos.l_time" )->table ( "cw_turn_order as tos" )->join ( "cw_services_order as so on so.id=tos.sod_id", 'left' )->join ( "cw_services as s on s.id=so.services_id", 'left' )->where ( "tos.order_id = '{$info['order_id']}'" )->select ();
	    $s_ids = "0";
	    foreach ( $to_list as $c => $p ) {
	        $s_ids .= ",{$p['s_id']}";
	    }
	    $order_model = M ( "order" );
	    $order_info = $order_model->where ( "id='{$info['order_id']}'" )->find ();
	    $so_model = M ( "services_order" );
	    $so_info = $so_model->where ( "id='{$info['sod_id']}'" )->find ();
	    $so_id = $this->screen ( $so_info ['violation'], $s_ids, $order_info ['endorsement_id'] );
	    if (! empty ( $so_id )) {
	        $data = array (
	            "last_time" => time (),
	            "so_id" => $so_id
	        );
	        $order_model->where ( "id='{$order_info['id']}'" )->save ( $data );
	        $data = array (
	            "order_id" => $order_info ['id'],
	            "sod_id" => $so_id,
	            "state" => 0,
	            "c_time" => time (),
	            "l_time" => time ()
	        );
	        $res = $model->add ( $data );
	        //推送消息
	        if($res){
	            $services_model = M ("services");
	            $services = $services_model->table("cw_services as s")->join("cw_services_order as so on s.id = so.services_id")->where("id = '$so_id'")->find();
	            $true_order_model = M ('turn_order');
	            $t_info = $true_order_model->field('tos.c_time')->table ( "cw_turn_order as tos" )->join ( "cw_order as o on o.id=tos.order_id", 'left' )->join ( "cw_services as s on s.id=o.services_id", 'left' )->where ( "o.order_sn='{$order_info['order_sn']}'" )->find ();
	            $order_sn_to = $order_info['order_sn'] . substr ( $t_info ['c_time'], - 2 ) . $order_info ['services_id'];
	            $content = sprintf(content4, $order_sn_to);
	            $title = title4;
	            $tz_content = sprintf(content4, $order_sn_to);
	            $this->pushMessageToSingle($content, $title,$tz_content,$services['phone']);
	            //插入消息表
	            $this->add_message($services ['id'], 3, 4, '', $content);
	        }
	        $data = array (
	            "state" => 1
	        );
	        $model->where ( "id='{$info['id']}'" )->save ( $data );
	        $so_info2 = $so_model->where ( "id='$so_id'" )->find ();
	        $data = array (
	            "services_id" => $so_info2 ['services_id'],
	            "so_id" => $so_id
	        );
	        $order_model->where ( "id='{$info['order_id']}'" )->save ( $data );
	        	
	        $services_model = M ( "services" );
	        $services_info = $services_model->where ( "id='{$so_info2['services_id']}'" )->find ();
	        if (! empty ( $services_info )) {
	            $data = array (
	                "all_nums" => $services_info ['all_nums'] + 1
	            );
	            $services_model->where ( "id='{$so_info2['services_id']}'" )->save ();
	        }
	        // 转钱
	        $bank_model = M ( "bank" );
	        $bank_info_older = $bank_model->where ( "bank_id='{$so_info['services_id']}'" )->find ();
	        if (! empty ( $bank_info_older )) {
	            $data = array (
	                "money" => ($bank_info_older ['money'] - $order_info ['money']) > 0 ? ($bank_info_older ['money'] - $order_info ['money']) : 0,
	                "balance_money" => ($bank_info_older ['balance_money'] - $order_info ['money']) > 0 ? ($bank_info_older ['balance_money'] - $order_info ['money']) : 0,
	                "end_money" => ($bank_info_older ['end_money'] - $order_info ['money']) > 0 ? ($bank_info_older ['end_money'] - $order_info ['money']) : 0,
	                "income_money" => ($bank_info_older ['income_money'] - $order_info ['money']) > 0 ? ($bank_info_older ['income_money'] - $order_info ['money']) : 0
	            );
	            $res = $bank_model->where ( "id='{$bank_info_older['id']}'" )->save ( $data );
	            if($res > 0){
	                $bank_info = $bank_model->where ( "id='{$bank_info_older['id']}'" )->find();
	                //推送消息
	                $time = date("Y.m.d H:i:s");
	                $content = sprintf(content9_2, $time,$order_info['money'],$bank_info['balance_money']);
	                $title = title9;
	                $tz_content = sprintf(content9_2, $time,$order_info['money'],$bank_info['balance_money']);
	                $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                $this->add_message($bank_info['bank_id'], 4, '', 1, $content);
	            }
	        }
	        // 记录
	        $bank_info_older = $bank_model->where ( "bank_id='{$so_info['services_id']}'" )->find ();
	        $data = array (
	            "services_id" => $bank_info_older ['bank_id'],
	            "income_money" => 0,
	            "pay_money" => $order_info ['money'],
	            "end_money" => $bank_info_older ['end_money'],
	            "user_money" => $bank_info_older ['user_money'],
	            "money" => $bank_info_older ['money'],
	            "order_id" => $info ['order_id'],
	            "c_time" => time ()
	        );
	        $jl_model = M ( "services_jilu" );
	        $jl_model->add ( $data );
	        	
	        $bank_info = $bank_model->where ( "bank_id='{$so_info2['bank_id']}'" )->find ();
	        if (! empty ( $bank_info )) {
	            $data = array (
	                "money" => $bank_info ['money'] + $order_info ['money'],
	                "balance_money" => $bank_info ['balance_money'] + $order_info ['money'],
	                "end_money" => $bank_info ['end_money'] + $order_info ['money'],
	                "income_money" => $bank_info ['income_money'] + $order_info ['money']
	            );
	            $bank_model->where ( "id='{$bank_info['id']}'" )->save ( $data );
	        }
	        // 记录
	        $bank_info = $bank_model->where ( "bank_id='{$so_info2['bank_id']}'" )->find ();
	        $data = array (
	            "services_id" => $bank_info ['bank_id'],
	            "income_money" => $order_info ['money'],
	            "pay_money" => 0,
	            "end_money" => $bank_info ['end_money'],
	            "user_money" => $bank_info ['user_money'],
	            "money" => $bank_info ['money'],
	            "order_id" => $info ['order_id'],
	            "c_time" => time ()
	        );
	        $jl_model = M ( "services_jilu" );
	        $jl_model->add ( $data );
	    } else {
	        $data = array (
	            "last_time" => time (),
	            "order_status" => 8
	        );
	        $order_model->where ( "id='{$order_info['id']}'" )->save ( $data );
	        $data = array (
	            "state" => 6
	        );
	        $model->where ( "id='{$info['id']}'" )->save ( $data );
	        	
	        // 修改违章状态
	        $data = array (
	            "is_manage" => 0,
	            "manage_time" => time ()
	        );
	        $endorsement_model = M ( "Endorsement" );
	        $endorsement_model->where ( "id={$order_info['endorsement_id']}" )->save ( $data );
	        /*start*/
	        $bank_model = M ( "bank" );
	        $bank_info_older = $bank_model->where ( "bank_id='{$so_info['services_id']}'" )->find ();
	        if (! empty ( $bank_info_older )) {
	            $data = array (
	                "money" => ($bank_info_older ['money'] - $order_info ['money']) > 0 ? ($bank_info_older ['money'] - $order_info ['money']) : 0,
	                "balance_money" => ($bank_info_older ['balance_money'] - $order_info ['money']) > 0 ? ($bank_info_older ['balance_money'] - $order_info ['money']) : 0,
	                "end_money" => ($bank_info_older ['end_money'] - $order_info ['money']) > 0 ? ($bank_info_older ['end_money'] - $order_info ['money']) : 0,
	                "income_money" => ($bank_info_older ['income_money'] - $order_info ['money']) > 0 ? ($bank_info_older ['income_money'] - $order_info ['money']) : 0
	            );
	            $res = $bank_model->where ( "id='{$bank_info_older['id']}'" )->save ( $data );
	        }
	        if($res > 0){
	            $bank_info = $bank_model->where ( "id='{$bank_info_older['id']}'" )->find();
	            //推送消息
	            $time = date("Y.m.d H:i:s");
	            $content = sprintf(content9_2, $time,$order_info['money'],$bank_info['balance_money']);
	            $title = title9;
	            $tz_content = sprintf(content9_2, $time,$order_info['money'],$bank_info['balance_money']);
	            $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	            $this->add_message($bank_info['bank_id'], 4, '', 1, $content);
	        }
	        /*end*/
	    }
	    $msg = array (
	        "code" => 101,
	        "msg" => '操作成功',
	    );
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	
	function screen($code, $s_ids, $e_id) {
	    $endorsement_model = M ( "Endorsement" );
	    $endorsemen_info = $endorsement_model->where ( "id='$e_id'" )->find ();
	    $city = $endorsemen_info ['area'];
	    $region_model = M ( "Region" );
	    $where = array (
	        "city" => $city,
	        "level" => 2,
	        "is_dredge" => 0
	    );
	    $region = $region_model->where ( $where )->order ( 'id' )->find ();
	    $city_id1 = $region ['id'];
	
	    $where = array (
	        "id" => $endorsemen_info ['car_id']
	    );
	    $car_model = M ( "Car" );
	    $car = $car_model->where ( $where )->find ();
	    $l_nums = mb_substr ( $car ['license_number'], 0, 2, 'utf-8' );
	    $region_model = M ( "Region" );
	    $region = $region_model->where ( "nums = '$l_nums'" )->find ();
	    $region = $region_model->where ( "city = '{$region['city']}'" )->order ( "id" )->find ();
	    if (empty ( $region )) {
	        $city_id2 = 0;
	    } else {
	        $city_id2 = $region ['id'];
	    }
	    // 筛选服务商
	    $so_model = M ( "Services_order" ); // 1
	    $solist = $so_model->where ( "violation = '$code' and services_id not in ($s_ids) and (code = '$city_id1' or code = '$city_id2')" )->order ( "money asc" )->group ( "services_id" )->limit ( NUMS1 )->select ();
	    $services_model = M ( "Services" ); // 2
	    $where = "state = 0";
	    if (! empty ( $solist )) {
	        foreach ( $solist as $p => $c ) {
	            if ($p == 0) {
	                $where .= " and (id = '{$c['services_id']}'";
	            } else {
	                $where .= " or id = '{$c['services_id']}'";
	            }
	        }
	        $where .= ")";
	        $serviceslist = $services_model->field ( "id" )->where ( $where )->order ( "`grade` desc" )->limit ( NUMS2 )->select ();
	        	
	        $order_model = new Model (); // 3
	        $where = "(order_status = 2 or order_status = 3)";
	        $services_id1 = array ();
	        if (! empty ( $serviceslist )) {
	            foreach ( $serviceslist as $p => $c ) {
	                if ($p == 0) {
	                    $where .= " and (services_id = '{$c['id']}'";
	                } else {
	                    $where .= " or services_id = '{$c['id']}'";
	                }
	                	
	                $services_id1 [] = $c ['id'];
	            }
	            $where .= ")";
	            $sql = "SELECT COUNT(*) as nums, `services_id` FROM `cw_order` WHERE $where GROUP BY `services_id` ORDER BY nums";
	            $orderlist = $order_model->query ( $sql );
	            $services_id2 = array ();
	            foreach ( $orderlist as $p => $c ) {
	                $services_id2 [] = $c ['services_id'];
	            }
	            $services = array_diff ( $services_id1, $services_id2 );
	            if (! empty ( $services )) {
	                $services_id = $services [0];
	            } else {
	                $services_id = $orderlist [0] ['services_id'];
	            }
	            // 4
	            $so = $so_model->where ( "violation = '$code' and services_id = '$services_id' and (code = '$city_id1' or code = '$city_id2')" )->order ( "money asc" )->find ();
	            return $so ['id'];
	        }
	    }
	    return false;
	}
	/**
	 * 新单我来办理
	 */
	public function accept(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
        $clientUUID = $_REQUEST['clientUUID'];
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->where("phone = '$phone'")->find();//获取服务商id
	    //判断账号是否封存
	    if($s_id['state'] == 1){
	        $msg = array (
	            "code" => 401,
	            "msg" => '账号已被封存，操作失败。',
	        );
	        echo json_encode($msg);
	        $this->remove_boms();
	        exit();
	    }
	    
	    $id = $_REQUEST ['xindanId'];
	    $model = M ( "turn_order" );
	    $info = $model->where ( "id='$id'" )->find ();
	    $order_model = M ( "order" );
	    $order_info = $order_model->where ( "id='{$info['order_id']}'" )->find ();
	    
	    $data = array (
	        "state" => 3,
	        'l_time' => time (),
	        'do_time' => time ()
	    );
	    $model->where ( "id='{$info['id']}'" )->save ( $data );
	    $data = array (
	        "last_time" => time (),
	        "order_status" => 3
	    );
	    $re = $order_model->where ( "id='{$order_info['id']}'" )->save ( $data );
	    // 评估
	    $services_model = M ( "services" );
	    $services_info = $services_model->where ( "id='{$order_info['services_id']}'" )->find ();
	    if (! empty ( $services_info )) {
	        $data = array (
	            "nums" => $services_info ['nums'] + 1
	        );
	        $services_model->where ( "id='{$order_info['services_id']}'" )->save ( $data );
	    }
	    /* start 增加账户余额变动提醒推送*/
	    if($re > 0){
	        $so_model = M ("services_order");
	        $so_info = $so_model->field ( "so.money" )->table("cw_services_order as so")->join ( "cw_order as o on so.id = o.so_id" )->where ( "o.id ={$order_info['id']} " )->find ();
	        $s_model = M ("bank");
	        $bank_info = $s_model->where ( "bank_id='{$s_id['id']}'" )->find();
	        $time = date("Y.m.d H:i:s");
	        $content = sprintf(content9_1, $time,$so_info['money'],$bank_info['balance_money']);
	        $title = title9;
	        $tz_content = sprintf(content9_1, $time,$so_info['money'],$bank_info['balance_money']);
	        $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	        $this->add_message($s_id['id'], 4, '', 1, $content);
	    }
	    /* end */
	        
	    $msg = array (
	        "code" => 101,
	        "msg" => '操作成功',
	    );
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	
	/**
	 * 超时取消，点击删除
	 */
	public function outtime(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $clientUUID = $_REQUEST['clientUUID'];
	    
	    $id = $_REQUEST ['xindanId'];
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->where("phone = '$phone'")->find();
	    //判断账号是否封存
	    if($s_id['state'] == 1){
	        $msg = array (
	            "code" => 401,
	            "msg" => '账号已被封存，操作失败。',
	        );
	        echo json_encode($msg);
	        $this->remove_boms();
	        exit();
	    }
	    $model = M ("turn_order");
	    $tos = $model->where("id = '$id'")->find();
	    
	    $model_order = M ("order");
	    $info = $model_order->field ( "a.id as order_id,a.car_id,a.order_sn,c.area,c.code,a.order_status,a.services_id,a.money as order_money,a.endorsement_id" )->table ( "cw_order as a" )->join ( "cw_car as b on a.car_id=b.id" )->join ( "cw_endorsement as c on c.id=a.endorsement_id" )->join ( "cw_services as d on a.services_id=d.id" )->where ( "a.id = '{$tos['order_id']}'" )->find ();
	        $city = $info['area'];
	        $region_model = M ( "Region" );
	        $where = array (
	            "city" => $city,
	            "level" => 2,
	            "is_dredge" => 0
	        );
	        $region = $region_model->where ( $where )->order ( 'id' )->find ();
	        $city_id1 = $region ['id'];
	        	
	        $where = array (
	            "id" => $info ['car_id']
	        );
	        $car_model = M ( "Car" );
	        $car = $car_model->where ( $where )->find ();
	        $l_nums = mb_substr ( $car ['license_number'], 0, 2, 'utf-8' );
	        $region_model = M ( "Region" );
	        $region = $region_model->where ( "nums = '$l_nums'" )->find ();
	        $region = $region_model->where ( "city = '{$region['city']}'" )->order ( "id" )->find ();
	        if (empty ( $region )) {
	            $city_id2 = 0;
	        } else {
	            $city_id2 = $region ['id'];
	        }
	        	
	        $true_order_model = M ( "turn_order" );
	        $to_list = $true_order_model->field ( "tos.id,so.id as so_id,s.id as s_id,s.phone,tos.c_time,tos.state,tos.l_time,tos.order_id" )->table ( "cw_turn_order as tos" )->join ( "cw_services_order as so on so.id=tos.sod_id", 'left' )->join ( "cw_services as s on s.id=so.services_id", 'left' )->where ( "tos.id = '$id'" )->find ();
	        $s_ids = "0";
	        if ($to_list ['s_id'] != '' && $to_list ['s_id'] != null) {
	                $s_ids .= ",{$to_list['s_id']}";
	        }
	        $order_model = M ( "Order" );
	        $so_model = M ( "Services_order" );
	        $solist = $so_model->where ( "violation = '{$info['code']}' and services_id not in ($s_ids) and (code = '$city_id1' or code = '$city_id2')" )->order ( "money asc" )->group ( "services_id" )->find ();
	        // 推单状态处理
	            if ($to_list ['state'] == 0) {
	                $time = jishi1 - (time () - $to_list ['l_time']);
	                if ($time <= 0) { // 超时
	                    //推送消息
	                    $content = sprintf(content5, $info['order_sn']);
	                    $title = title5;
	                    $tz_content = sprintf(content5, $info['order_sn']);
	                    $re = $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                    //插入消息表
	                    $this->add_message($s_id['id'], 3, 5, '', $content);
	                    
	                    if (! empty ( $solist )) { // 转单
	                        $data = array (
	                            "state" => 2,
	                            "l_time" => time ()
	                        );
	                        $true_order_model->where ( "id='{$to_list['id']}'" )->save ( $data );
	                        $data = array (
	                            "order_id" => $info ['order_id'],
	                            "sod_id" => $solist ['id'],
	                            "state" => 0,
	                            "c_time" => time (),
	                            "l_time" => time ()
	                        );
	                        $to_model = M ( "Turn_order" );
	                        $res= $to_model->add ( $data );
	                        if($res){
	                            //推送消息
	                            $services_model = M ("services");
	                            $services = $services_model->field("phone")->where("id = '{$solist ['services_id']}'")->find();
	                            $true_order_model = M ('turn_order');
	                            $t_info = $true_order_model->field('tos.c_time')->table ( "cw_turn_order as tos" )->join ( "cw_order as o on o.id=tos.order_id", 'left' )->join ( "cw_services as s on s.id=o.services_id", 'left' )->where ( "o.order_sn='{$info['order_sn']}'" )->find ();
	                            $order_sn_to = $info['order_sn'] . substr ( $t_info ['c_time'], - 2 ) . $info ['services_id'];
	                            $content = sprintf(content4, $order_sn_to);
	                            $title = title4;
	                            $tz_content = sprintf(content4, $order_sn_to);
	                            $this->pushMessageToSingle($content, $title,$tz_content,$services['phone']);
	                            //插入消息表
	                            $this->add_message($solist ['services_id'], 3, 4, '', $content);
	                        }
	                        $data = array (
	                            "services_id" => $solist ['services_id'],
	                            "so_id" => $solist ['id']
	                        );
	                        $order_model->where ( "id='{$info['order_id']}'" )->save ( $data );
	                        	
	                        $services_model = M ( "services" );
	                        $services_info = $services_model->where ( "id='{$solist['services_id']}'" )->find ();
	                        if (! empty ( $services_info )) {
	                            $data = array (
	                                "all_nums" => $services_info ['all_nums'] + 1
	                            );
	                            $services_model->where ( "id='{$solist['services_id']}'" )->save ();
	                        }
	                        // 转钱
	                        $bank_model = M ( "bank" );
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        if (! empty ( $bank_info_older )) {
	                            $data = array (
	                                "money" => ($bank_info_older ['money'] - $info ['order_money']) > 0 ? ($bank_info_older ['money'] - $info ['order_money']) : 0,
	                                "balance_money" => ($bank_info_older ['balance_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['balance_money'] - $info ['order_money']) : 0,
	                                "end_money" => ($bank_info_older ['end_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['end_money'] - $info ['order_money']) : 0,
	                                "income_money" => ($bank_info_older ['income_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['income_money'] - $info ['order_money']) : 0
	                            );
	                            $res = $bank_model->where ( "id='{$bank_info_older['id']}'" )->save ( $data );
	                            if($res > 0){
	                                $bank_info = $bank_model->where ( "id='{$bank_info_older['id']}'" )->find();
	                                //推送消息
	                                $time = date("Y.m.d H:i:s");
	                                $content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $title = title9;
	                                $tz_content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                                $this->add_message($bank_info['bank_id'], 4, '', 1, $content);
	                            }
	                        }
	                        // 记录
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        $data = array (
	                            "services_id" => $bank_info_older ['bank_id'],
	                            "income_money" => 0,
	                            "pay_money" => $info ['order_money'],
	                            "end_money" => $bank_info_older ['end_money'],
	                            "user_money" => $bank_info_older ['user_money'],
	                            "money" => $bank_info_older ['money'],
	                            "order_id" => $info ['order_id'],
	                            "c_time" => time ()
	                        );
	                        $jl_model = M ( "services_jilu" );
	                        $jl_model->add ( $data );
	                        	
	                        $bank_info = $bank_model->where ( "bank_id='{$solist['services_id']}'" )->find ();
	                        if (! empty ( $bank_info )) {
	                            $data = array (
	                                "money" => $bank_info ['money'] + $info ['order_money'],
	                                "balance_money" => $bank_info ['balance_money'] + $info ['order_money'],
	                                "end_money" => $bank_info ['end_money'] + $info ['order_money'],
	                                "income_money" => $bank_info ['income_money'] + $info ['order_money']
	                            );
	                            $bank_model->where ( "id='{$bank_info['id']}'" )->save ( $data );
	                        }
	                        // 记录
	                        $bank_info = $bank_model->where ( "bank_id='{$solist['services_id']}'" )->find ();
	                        $data = array (
	                            "services_id" => $bank_info ['bank_id'],
	                            "income_money" => $info ['order_money'],
	                            "pay_money" => 0,
	                            "end_money" => $bank_info ['end_money'],
	                            "user_money" => $bank_info ['user_money'],
	                            "money" => $bank_info ['money'],
	                            "order_id" => $info ['order_id'],
	                            "c_time" => time ()
	                        );
	                        $jl_model = M ( "services_jilu" );
	                        $jl_model->add ( $data );
	                    } else { // 取消订单
	                        $data = array (
	                            "state" => 2,
	                            "l_time" => time ()
	                        );
	                        $true_order_model->where ( "id='{$to_list['id']}'" )->save ( $data );
	                        $data = array (
	                            "order_status" => 8
	                        );
	                        $order_model->where ( "id='{$info['order_id']}'" )->save ( $data );
	                        $data = array (
	                            "state" => 6
	                        );
	                        $true_order_model->where ( "id='{$to_list['id']}'" )->save ( $data );
	                        // 修改违章状态
	                        $data = array (
	                            "is_manage" => 0,
	                            "manage_time" => time ()
	                        );
	                        $endorsement_model = M ( "Endorsement" );
	                        $endorsement_model->where ( "id={$info['endorsement_id']}" )->save ( $data );
	                        //扣钱
	                        $bank_model = M ( "bank" );
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        if (! empty ( $bank_info_older )) {
	                            $data = array (
	                                "money" => ($bank_info_older ['money'] - $info ['order_money']) > 0 ? ($bank_info_older ['money'] - $info ['order_money']) : 0,
	                                "balance_money" => ($bank_info_older ['balance_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['balance_money'] - $info ['order_money']) : 0,
	                                "end_money" => ($bank_info_older ['end_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['end_money'] - $info ['order_money']) : 0,
	                                "income_money" => ($bank_info_older ['income_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['income_money'] - $info ['order_money']) : 0
	                            );
	                            $res = $bank_model->where ( "id='{$bank_info_older['id']}'" )->save ( $data );
	                            if($res > 0){
	                                $bank_info = $bank_model->where ( "id='{$bank_info_older['id']}'" )->find();
	                                //推送消息
	                                $time = date("Y.m.d H:i:s");
	                                $content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $title = title9;
	                                $tz_content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                                $this->add_message($bank_info['bank_id'], 4, '', 1, $content);
	                            }
	                        }
	                        // 记录
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        $data = array (
	                            "services_id" => $bank_info_older ['bank_id'],
	                            "income_money" => 0,
	                            "pay_money" => $info ['order_money'],
	                            "end_money" => $bank_info_older ['end_money'],
	                            "user_money" => $bank_info_older ['user_money'],
	                            "money" => $bank_info_older ['money'],
	                            "order_id" => $info ['order_id'],
	                            "c_time" => time ()
	                        );
	                        $jl_model = M ( "services_jilu" );
	                        $jl_model->add ( $data );
	                    }
	                }
	            } else if ($to_list ['state'] == 3) {
	                $time = jishi2 - (time () - $to_list ['l_time']);
	                if ($time <= 0) { // 超时
	                    //推送消息
	                    $content = sprintf(content6, $info['order_sn']);
	                    $title = title6;
	                    $tz_content = sprintf(content6, $info['order_sn']);
	                    $re = $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                    //插入消息表
	                    $this->add_message($s_id['id'], 3, 6, '', $content);
	                    
	                    if (! empty ( $solist )) { // 转单
	                        $data = array (
	                            "state" => 2,
	                            "l_time" => time ()
	                        );
	                        $true_order_model->where ( "id='{$to_list['id']}'" )->save ( $data );
	                        $data = array (
	                            "order_id" => $info ['order_id'],
	                            "sod_id" => $solist ['id'],
	                            "state" => 0,
	                            "c_time" => time (),
	                            "l_time" => time ()
	                        );
	                        $to_model = M ( "Turn_order" );
	                        $res = $to_model->add ( $data );
	                        if($res){
	                            //推送消息
	                            $services_model = M ("services");
	                            $services = $services_model->field("phone")->where("id = '{$solist ['services_id']}'")->find();
	                            $true_order_model = M ('turn_order');
	                            $t_info = $true_order_model->field('tos.c_time')->table ( "cw_turn_order as tos" )->join ( "cw_order as o on o.id=tos.order_id", 'left' )->join ( "cw_services as s on s.id=o.services_id", 'left' )->where ( "o.order_sn='{$info['order_sn']}'" )->find ();
	                            $order_sn_to = $info['order_sn'] . substr ( $t_info ['c_time'], - 2 ) . $info ['services_id'];
	                            $content = sprintf(content4, $order_sn_to);
	                            $title = title4;
	                            $tz_content = sprintf(content4, $order_sn_to);
	                            $this->pushMessageToSingle($content, $title,$tz_content,$services['phone']);
	                            //插入消息表
	                            $this->add_message($solist ['services_id'], 3, 4, '', $content);
	                        }
	                        $data = array (
	                            "services_id" => $solist ['services_id'],
	                            "so_id" => $solist ['id']
	                        );
	                        $order_model->where ( "id='{$info['order_id']}'" )->save ( $data );
	                        	
	                        $services_model = M ( "services" );
	                        $services_info = $services_model->where ( "id='{$solist['services_id']}'" )->find ();
	                        if (! empty ( $services_info )) {
	                            $data = array (
	                                "all_nums" => $services_info ['all_nums'] + 1
	                            );
	                            $services_model->where ( "id='{$solist['services_id']}'" )->save ();
	                        }
	                        // 转钱
	                        $bank_model = M ( "bank" );
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        if (! empty ( $bank_info_older )) {
	                            $data = array (
	                                "money" => ($bank_info_older ['money'] - $info ['order_money']) > 0 ? ($bank_info_older ['money'] - $info ['order_money']) : 0,
	                                "balance_money" => ($bank_info_older ['balance_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['balance_money'] - $info ['order_money']) : 0,
	                                "end_money" => ($bank_info_older ['end_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['end_money'] - $info ['order_money']) : 0,
	                                "income_money" => ($bank_info_older ['income_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['income_money'] - $info ['order_money']) : 0
	                            );
	                            $res = $bank_model->where ( "id='{$bank_info_older['id']}'" )->save ( $data );
	                            if($res > 0){
	                                $bank_info = $bank_model->where ( "id='{$bank_info_older['id']}'" )->find();
	                                //推送消息
	                                $time = date("Y.m.d H:i:s");
	                                $content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $title = title9;
	                                $tz_content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                                $this->add_message($bank_info['bank_id'], 4, '', 1, $content);
	                            }
	                        }
	                        // 记录
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        $data = array (
	                            "services_id" => $bank_info_older ['bank_id'],
	                            "income_money" => 0,
	                            "pay_money" => $info ['order_money'],
	                            "end_money" => $bank_info_older ['end_money'],
	                            "user_money" => $bank_info_older ['user_money'],
	                            "money" => $bank_info_older ['money'],
	                            "order_id" => $info ['order_id'],
	                            "c_time" => time ()
	                        );
	                        $jl_model = M ( "services_jilu" );
	                        $jl_model->add ( $data );
	                        	
	                        $bank_info = $bank_model->where ( "bank_id='{$solist['services_id']}'" )->find ();
	                        if (! empty ( $bank_info )) {
	                            $data = array (
	                                "money" => $bank_info ['money'] + $info ['order_money'],
	                                "balance_money" => $bank_info ['balance_money'] + $info ['order_money'],
	                                "end_money" => $bank_info ['end_money'] + $info ['order_money'],
	                                "income_money" => $bank_info ['income_money'] + $info ['order_money']
	                            );
	                            $bank_model->where ( "id='{$bank_info['id']}'" )->save ( $data );
	                        }
	                        // 记录
	                        $bank_info = $bank_model->where ( "bank_id='{$solist['services_id']}'" )->find ();
	                        $data = array (
	                            "services_id" => $bank_info ['bank_id'],
	                            "income_money" => $info ['order_money'],
	                            "pay_money" => 0,
	                            "end_money" => $bank_info ['end_money'],
	                            "user_money" => $bank_info ['user_money'],
	                            "money" => $bank_info ['money'],
	                            "order_id" => $info ['order_id'],
	                            "c_time" => time ()
	                        );
	                        $jl_model = M ( "services_jilu" );
	                        $jl_model->add ( $data );
	                    } else { // 取消订单
	                        $data = array (
	                            "state" => 2,
	                            "l_time" => time ()
	                        );
	                        $true_order_model->where ( "id='{$to_list['id']}'" )->save ( $data );
	                        $data = array (
	                            "order_status" => 8
	                        );
	                        $order_model->where ( "id='{$info['order_id']}'" )->save ( $data );
	                        $data = array (
	                            "state" => 6
	                        );
	                        $true_order_model->where ( "id='{$to_list['id']}'" )->save ( $data );
	                        // 修改违章状态
	                        $data = array (
	                            "is_manage" => 0,
	                            "manage_time" => time ()
	                        );
	                        $endorsement_model = M ( "Endorsement" );
	                        $endorsement_model->where ( "id={$info['endorsement_id']}" )->save ( $data );
	                        //扣钱
	                        $bank_model = M ( "bank" );
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        if (! empty ( $bank_info_older )) {
	                            $data = array (
	                                "money" => ($bank_info_older ['money'] - $info ['order_money']) > 0 ? ($bank_info_older ['money'] - $info ['order_money']) : 0,
	                                "balance_money" => ($bank_info_older ['balance_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['balance_money'] - $info ['order_money']) : 0,
	                                "end_money" => ($bank_info_older ['end_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['end_money'] - $info ['order_money']) : 0,
	                                "income_money" => ($bank_info_older ['income_money'] - $info ['order_money']) > 0 ? ($bank_info_older ['income_money'] - $info ['order_money']) : 0
	                            );
	                            $res = $bank_model->where ( "id='{$bank_info_older['id']}'" )->save ( $data );
	                            if($res > 0){
	                                $bank_info = $bank_model->where ( "id='{$bank_info_older['id']}'" )->find();
	                                //推送消息
	                                $time = date("Y.m.d H:i:s");
	                                $content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $title = title9;
	                                $tz_content = sprintf(content9_2, $time,$info ['order_money'],$bank_info['balance_money']);
	                                $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	                                $this->add_message($bank_info['bank_id'], 4, '', 1, $content);
	                            }
	                        }
	                        // 记录
	                        $bank_info_older = $bank_model->where ( "bank_id='{$info['services_id']}'" )->find ();
	                        $data = array (
	                            "services_id" => $bank_info_older ['bank_id'],
	                            "income_money" => 0,
	                            "pay_money" => $info ['order_money'],
	                            "end_money" => $bank_info_older ['end_money'],
	                            "user_money" => $bank_info_older ['user_money'],
	                            "money" => $bank_info_older ['money'],
	                            "order_id" => $info ['order_id'],
	                            "c_time" => time ()
	                        );
	                        $jl_model = M ( "services_jilu" );
	                        $jl_model->add ( $data );
	                    }
	                }
	            }
	    
	    $msg = array (
	        "code" => 101,
	        "msg" => '操作成功',
	    );
	    echo json_encode($msg);
	    $this->remove_boms();
	    exit();
	}
	
}