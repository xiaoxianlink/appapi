<?php

namespace Weixin\Controller;

use Weixin\Controller\IndexController;
use Think\Log;
use Think\Model;
use Think\Template\Driver\Mobile;

class ScanController extends IndexController {
	public function index() {
		$open_id = $_REQUEST ['openid'];
		$car_id = $_REQUEST ['carid'];
		$end_id = isset ( $_REQUEST ['end_id'] ) ? $_REQUEST ['end_id'] : 0;
		$user_model = M ( "User" );
		$where = array (
				'openid' => ( string ) $open_id 
		);
		$user = $user_model->where ( $where )->find ();
		$user_id = $user ['id'];
		$car_model = M ( "Car" );
		$where = array (
				'id' => ( string ) $car_id 
		);
		$car = $car_model->where ( $where )->find ();
		$l_num1 = mb_substr ( $car ['license_number'], 0, 2, 'utf-8' );
		$l_num2 = mb_substr ( $car ['license_number'], 2, strlen ( $car ['license_number'] ), 'utf-8' );
		$car ['license_number'] = $l_num1 . "·" . $l_num2;
		$license_number = $car ['license_number'];
		$endorsement = $this->get_endorsement ( $car_id );
		$endorsement_list = $this->get_endorsement_list ( $car_id, $user_id, $end_id );
		$this->assign ( 'user_id', $user_id );
		$this->assign ( 'license_number', $license_number );
		$this->assign ( 'endorsement', $endorsement );
		$this->assign ( 'endorsement_list', $endorsement_list );
		$this->display ( ":scan" );
	}
	function get_endorsement($car_id) {
		// 查询数据库违章信息
		$endorsement_model = M ( "Endorsement" );
		$where = array (
				"car_id" => $car_id,
				"is_manage" => 0 
		);
		$endorsement = $endorsement_model->field ( "count(*) as nums, sum(points) as all_points, sum(money) as all_money" )->where ( $where )->find ();
		if ($endorsement ['nums'] != 0) {
			return $endorsement;
		}
		return false;
	}
	function get_endorsement_list($car_id, $user_id, $end_id = 0) {
		// 查询数据库违章信息列表
		$endorsement_model = M ( "Endorsement" );
		$where = array (
				"car_id" => $car_id,
				"is_manage" => 0 
		);
		if ($end_id != 0) {
			$where ['id'] = $end_id;
		}
		$endorsementlist = $endorsement_model->where ( $where )->order ( "`time` desc" )->select ();
		$log = new Log ();
		foreach ( $endorsementlist as $k => $v ) {
			$city = $v ['area'];
			$region_model = M ( "Region" );
			$where = array (
					"city" => $city,
					"level" => 2,
					"is_dredge" => 0 
			);
			$region = $region_model->where ( $where )->order ( 'id' )->find ();
			if (empty ( $region )) {
				$city_id1 = 0;
			}
			else{
				$city_id1 = $region ['id'];
			}
			
			$where = array (
					"id" => $v ['car_id'] 
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
			// 算法说明：
		    // 1.先获取符合条件的静态报价列表，价格最低者优先
			// 2.再获取符合条件的动态报价列表，价格最低者优先
			// 3.比较两个价格列表顶部，价格最低者为本次违章处理的最低价
			// 4.根据最低价，反查能提供该价格的服务商，选择正在处理订单数量最少的服务商为本次违章处理的服务商
			// 潜在的性能问题：
			// 若同一个城市中针对某违章的报价完全一样，且服务商数量上万，则本算法会造成内存使用量以及多次sql查询性能问题，但考虑该情况为极端情况，故本算法占时不处理。待真有此性能问题时，再行处理
			
			$violation_model = M("violation");
			$violation = $violation_model->where("code = '{$v['code']}'")->find();
			if(empty($violation) || $violation['state'] == 1){
				continue;
			}
			
			$so_model = new Model(); // 1.a
			$so_sql = "select srv.id as services_id, so.id as so_id, so.money from cw_services as srv, cw_services_city as scity, cw_services_code as scode, cw_services_order as so where srv.id = scity.services_id and srv.id = scode.services_id and srv.id = so.services_id and srv.state = 0 and srv.grade > 4 and ((scity.code = $city_id1 and scity.state = 0) or (scity.code = $city_id2 and scity.state = 0)) and (scode.code = '{$v['code']}' and scode.state = 0 ) and so.violation = '{$v['code']}' and (so.code = $city_id1 or so.code = $city_id2) group by srv.id order by money asc ";
			//$log->write ( $so_sql );
			$solist = $so_model->query($so_sql);
			
			$sd_model = new Model(); // 1.b
			$sd_sql = "select * from (select dyna.services_id, dyna.id as so_id, ({$v['money']} + dyna.fee + dyna.point_fee * {$v['points']}) dyna_fee from cw_services as srv, cw_services_city as scity, cw_services_code as scode, cw_services_dyna as dyna where srv.id = scity.services_id and srv.id = scode.services_id and srv.id = dyna.services_id and srv.state = 0 and srv.grade > 4 and ((scity.code = $city_id1 and scity.state = 0) or (scity.code = $city_id2 and scity.state = 0)) and scode.code = '{$v['code']}' and scode.state = 0 and (dyna.code = $city_id1 or dyna.code = $city_id2) ORDER BY dyna_fee ASC) as service_dyna group by services_id";
			//$log->write ( $sd_sql );
			$sdlist = $sd_model->query($sd_sql);
			
			// we now get the lowest price
			$lowest_price = -1;
			$so_id = -1;
			$so_type = -1;
			if( ! empty($solist)){
				$lowest_price = $solist[0]['money'];
				$so_id = $solist[0]['so_id'];
				$so_type = 1;
			}
			if( ! empty($sdlist)){
				if($lowest_price > -1 ){
					if($lowest_price > $sdlist[0]['dyna_fee']){
						$lowest_price = $sdlist[0]['dyna_fee'];
						$so_id = $sdlist[0]['so_id'];
						$so_type = 2;
					}
				}
				else{
					$lowest_price = $sdlist[0]['dyna_fee'];
					$so_id = $sdlist[0]['so_id'];
					$so_type = 2;
				}
			}
			//$log->write ( "lowest_price=". $lowest_price );
			if($lowest_price == -1){
				continue;
			}
			
			$where = "";
			$firstCondition = false;
			$services_id_by_money = array ();
			if( ! empty($solist)){
				foreach ( $solist as $p => $c ) {
					if($c['money'] == $lowest_price){
						if ($firstCondition == false) {
							$where .= " services_id = {$c['services_id']}";
							$firstCondition = true;
						} else {
							$where .= " or services_id = {$c['services_id']}";
						}
						$services_id_by_money[] = $c['services_id'];
					}
					else{
						break;
					}
				}
			}
			if( ! empty($sdlist)){
				foreach ( $sdlist as $p => $c ) {
					if($c['dyna_fee'] == $lowest_price){
						if ($firstCondition == false) {
							$where .= " services_id = '{$c['services_id']}'";
							$firstCondition = true;
						} else {
							$where .= " or services_id = '{$c['services_id']}'";
						}
						$services_id_by_money[] = $c['services_id'];
					}
					else{
						break;
					}
				}
			}
			$order_model = new Model (); // 2
			$sql = "SELECT COUNT(*) as nums, `services_id` FROM `cw_order` WHERE $where GROUP BY `services_id` ORDER BY nums";
			//$log->write ( $sql);
			$orderlist = $order_model->query ( $sql );
			$services_id_by_ordernum = array ();
			foreach ( $orderlist as $p => $c ) {
				$services_id_by_ordernum [] = $c ['services_id'];
			}
			$services = array_diff ( $services_id_by_money, $services_id_by_ordernum );
			if (! empty ( $services )) {
				foreach ( $services as $r ) {
					$services_id = $r;
					break;
				}
			} else {
				$services_id = $orderlist [0] ['services_id'];
			}
			//$log->write ( "services_id=". $services_id );
			// 3
			$endorsementlist [$k] ['so_id'] = $so_id;
			$endorsementlist [$k] ['so_type'] = $so_type;
			$endorsementlist [$k] ['so_money'] = $lowest_price;
		}
		return $endorsementlist;
	}
	// 详情
	public function scan_info() {
		$id = $_REQUEST ['id'];
		$license_number = $_REQUEST ['license_number'];
		$so_id = $_REQUEST ['so_id'];
		$so_type = $_REQUEST ['so_type'];
		$user_id = $_REQUEST ['user_id'];
		$state = isset ( $_REQUEST ['state'] ) ? $_REQUEST ['state'] : '';
		
		$endorsement = $this->get_endorsement_info ( $id );
		if($so_type == 1){
			$so = $this->get_so_info ( $so_id);
		}
		if($so_type == 2){
			$so = $this->get_sd_info ($so_id);
			$so['money'] = $endorsement['money'] + $so['fee'] + $so['point_fee'] * $endorsement['points'];
		}
		
		$coupon_money = 0;
		// 创建订单
		if (empty ( $state )) {
			$order_id = $this->create_order ( $endorsement, $so, $so_type, $user_id );
		} else {
			$order_id = $_REQUEST ['order_id'];
			$cuc_id = isset ( $_REQUEST ['cuc_id'] ) ? $_REQUEST ['cuc_id'] : 0;
			$coupon = $this->get_coupon_info ( $cuc_id );
			if (! empty ( $coupon )) {
				$coupon_money = $coupon ['money'] > 0 ? $coupon ['money'] : 0;
			}
			$this->assign ( 'coupon', $coupon );
		}
		$order = $this->get_order ( $order_id );
		$ucouponlist = $this->get_ucoupon ( $user_id, $so ['money'] );
		
		$this->assign ( 'endorsement', $endorsement );
		$this->assign ( 'coupon_money', $coupon_money );
		$this->assign ( 'so', $so );
		$this->assign ( 'so_type', $so_type );
		$this->assign ( 'order', $order );
		$this->assign ( 'license_number', $license_number );
		$this->assign ( 'ucoupon_count', count ( $ucouponlist ) );
		$this->display ( ":scan_info" );
	}
	function get_endorsement_info($id) {
		// 查询数据库违章信息
		$endorsement_model = M ( "Endorsement" );
		$where = array (
				"id" => $id 
		);
		$endorsement = $endorsement_model->where ( $where )->find ();
		return $endorsement;
	}
	function get_so_info($id) {
		// 定价表信息
		$so_model = M ( "Services_order" );
		$where = array (
				"id" => $id 
		);
		$so = $so_model->where ( $where )->find ();
		return $so;
	}
	function get_sd_info($id) {
		// 定价表信息
		$sd_model = M ( "Services_dyna" );
		$where = array (
				"id" => $id 
		);
		$sd = $sd_model->where ( $where )->find ();
		return $sd;
	}
	
	function create_order($endorsement, $so, $so_type, $user_id) {
		$order_model = M ( "Order" );
		$order = $order_model->where ( "endorsement_id = '{$endorsement ['id']}' and order_status = 1" )->find ();
		if (! empty ( $order )) {
			// re-generate order
			// there are maybe another servie provider with a lower price after the order created
			$data = array (
				"money" => $so ['money'],
				"last_time" => time (),
				"services_id" => $so ['services_id'],
				"so_id" => $so ['id'],
				"so_type" => $so_type
			);
			$order_model->where("id = {$order['id']}" )->save ( $data );
			return $order ['id'];
		}
		$order = $order_model->where ( "endorsement_id = '{$endorsement ['id']}' and (order_status = 2 or order_status = 3 or order_status = 4 or order_status = 5)" )->find ();
		if (! empty ( $order )) {
			return 0;
		}
		$data = array (
				"user_id" => $user_id,
				"car_id" => $endorsement ['car_id'],
				"order_sn" => $user_id . $endorsement ['car_id'] . time (),
				"endorsement_id" => $endorsement ['id'],
				"order_status" => 1,
				"money" => $so ['money'],
				"last_time" => time (),
				"services_id" => $so ['services_id'],
				"so_id" => $so ['id'],
				"so_type" => $so_type,
				"c_time" => time () 
		);
		return $order_model->add ( $data );
	}
	function get_order($id) {
		// 订单表信息
		$order_model = M ( "Order" );
		$where = array (
				"id" => $id 
		);
		$order = $order_model->where ( $where )->find ();
		return $order;
	}
	function get_ucoupon($user_id, $money) {
		// 用户拥有优惠券信息
		$ucoupon_model = M ( "User_coupon" );
		$where = "cw_user_coupon.user_id='$user_id'";
		$where .= " and cw_user_coupon.is_used = 0";
		$where .= " and cw_coupon.start_time <= unix_timestamp(now()) and cw_coupon.expiration_time >= unix_timestamp(now())";
		$where .= " and cw_coupon.condition <= '$money'";
		$ucouponlist = $ucoupon_model->field ( "cw_coupon.*, cw_user_coupon.id as cuc_id" )->join ( "cw_coupon on cw_coupon.id = cw_user_coupon.coupon_id" )->where ( $where )->select ();
		return $ucouponlist;
	}
	function get_coupon_info($cuc_id) {
		$ucoupon_model = M ( "User_coupon" );
		$where = "cw_user_coupon.id='$cuc_id'";
		$ucoupon = $ucoupon_model->field ( "cw_coupon.*, cw_user_coupon.id as cuc_id" )->join ( "cw_coupon on cw_coupon.id = cw_user_coupon.coupon_id" )->where ( $where )->find ();
		return $ucoupon;
	}
	public function ucoupon_list() {
		$id = $_REQUEST ['id'];
		$license_number = $_REQUEST ['license_number'];
		$so_id = $_REQUEST ['so_id'];
		$so_type = $_REQUEST ['so_type'];
		$user_id = $_REQUEST ['user_id'];
		$order_id = $_REQUEST ['order_id'];
		$money = $_REQUEST ['money'];
		
		$uclist = $this->get_ucoupon ( $user_id, $money );
		$this->assign ( 'id', $id );
		$this->assign ( 'license_number', $license_number );
		$this->assign ( 'so_id', $so_id );
		$this->assign ( 'so_type', $so_type );
		$this->assign ( 'user_id', $user_id );
		$this->assign ( 'order_id', $order_id );
		$this->assign ( 'uuc_list', $uclist );
		$this->display ( ":ucoupon_list" );
	}
	public function wxpay() {
		$order_id = $_REQUEST ['order_id'];
		$cuc_id = $_REQUEST ['cuc_id'];
		$order_model = M ( "Order" );
		$data = array ();
		$order = $this->get_order ( $order_id );
		if ($order ['order_status'] != 1) {
			$url ['url'] = 1;
			$this->ajaxReturn ( $url );
		}
		if (! empty ( $cuc_id )) {
			$ucoupon = $this->get_coupon_info ( $cuc_id );
			if (! empty ( $ucoupon )) {
				$data ["ucoupon_id"] = $cuc_id;
				$data ["pay_money"] = $order ['money'] - $ucoupon ['money'];
			} else {
				$data ["pay_money"] = $order ['money'];
			}
		} else {
			$data ["pay_money"] = $order ['money'];
		}
		// re-generate order-sn
		// for the same order, the money MUST be the same in wxpay side
		// so if user apply an coupon to the order or choose another service provider with an lower price after an un-finish pay action, user will get an error with the un-changed order-sn when user try to pay again
		$data["order_sn"] = $order['user_id'] . $order['car_id'] . time ();
		$order_model->where ( "id='$order_id'" )->save ( $data );
		$order = $this->get_order ( $order_id );
		$data ["pay_money"]=intval($data ["pay_money"]*100);
		if(runEnv == 'production'){
			$url ['url'] = APIURL . "Wxpay/example/jsapi.php??money={$data['pay_money']}&order_sn={$order ['order_sn']}&body=违章缴费&url=" . APIURL;
		}
		else{
			$url ['url'] = "http://weixin.xiaoxianlink.com/Wxpay/example/jsapi.php??money=1&order_sn={$order ['order_sn']}&body=违章缴费&url=" . APIURL;
		}
		$log = new Log ();
		$log->write ( $url ['url'] );
		
		$this->ajaxReturn ( $url );
	}
	public function notify() {
		$order_sn = $_REQUEST ['out_trade_no'];
		$log = new Log ();
		$log->write ( json_encode ( $_REQUEST ) );
		$order_model = M ( "Order" );
		$order = $order_model->where ( "order_sn='$order_sn'" )->find ();
		if($order ['order_status'] != 1){
		    return true;
		}
		// 修改订单状态
		$data = array (
				"order_status" => 2,
				"last_time" => time (),
				"pay_type" => 2,
				"pay_sn" => $_REQUEST ['transaction_id'] 
		);
		
		$order_model->where ( "order_sn='$order_sn'" )->save ( $data );
		$order = $order_model->where ( "order_sn='$order_sn'" )->find ();
		$data = array (
				"order_id" => $order ['id'],
				"sod_id" => $order ['so_id'],
				"state" => '0',
				"c_time" => time (),
				"l_time" => time () 
		);
		$to_model = M ( "Turn_order" );
		$to_model->add ( $data );
		
		$services_model = M ( "services" );
		$services_info = $services_model->where ( "id='{$order['services_id']}'" )->find ();
		if (! empty ( $services_info )) {
			$data = array (
					"all_nums" => $services_info ['all_nums'] + 1 
			);
			$services_model->where ( "id='{$order['services_id']}'" )->save ();
		}
		// 修改违章状态
		$data = array (
				"is_manage" => 1,
				"manage_time" => time () 
		);
		$endorsement_model = M ( "Endorsement" );
		$endorsement_model->where ( "id={$order['endorsement_id']}" )->save ( $data );
		$log_model = M ( "Endorsement_log" );
		$data = array (
				"end_id" => $order['endorsement_id'],
				"state" => 2,
				"c_time" => time (),
				"type" => 1
		);
		$log_model->add ( $data );
		// 使用优惠券
		if ($order ['ucoupon_id'] > 0) {
			$data = array (
					"is_used" => 1,
					"use_time" => time () 
			);
			$uc_model = M ( "User_coupon" );
			$uc_model->where ( "id={$order ['ucoupon_id']}" )->save ( $data );
		}
		return true;
	}
	public function success_pay() {
		$order_sn = $_REQUEST ['order_sn'];
		$order_model = M ( "Order" );
		$order = $order_model->field ( "cw_order.id,u.openid, cw_order.car_id, cw_order.services_id, sod.money" )->join ( "cw_services_order as sod on sod.id = cw_order.so_id" )->join ( "cw_user as u on u.id = cw_order.user_id" )->where ( "cw_order.order_sn='$order_sn'" )->find ();
		// 服务商收入
		$s_model = M ( "bank" );
		$s_info = $s_model->where ( "bank_id = '{$order['services_id']}'" )->find ();
		if (! empty ( $s_info )) {
			$data = array (
					"money" => $s_info ['money'] + $order ['money'],
					"balance_money" => $s_info ['balance_money'] + $order ['money'],
					"end_money" => $s_info ['end_money'] + $order ['money'],
					"income_money" => $s_info ['income_money'] + $order ['money'] 
			);
			$s_model->where ( "id='{$s_info['id']}'" )->save ( $data );
			
			/* start 增加账户余额变动提醒推送*/
			if($re > 0){
			    $bank_info = $s_model->where ( "id='{$s_info['id']}'" )->find();
			    $services_model = M ( "services" );
			    $services_info = $services_model->where ( "id='{$order['services_id']}'" )->find ();
			    $time = date("Y.m.d H:i:s");
			    $content = sprintf(content9_1, $time,$order['money'],$bank_info['balance_money']);
			    $title = title9;
			    $tz_content = sprintf(content9_1, $time,$order['money'],$bank_info['balance_money']);
			    $this->pushMessageToSingle($content, $title,$tz_content,$services_info['phone']);
			    $this->add_message($services_info['id'], 4, '', 1, $content);
			}
			/* end */
			
		} else {
			$data = array (
					"bank_id" => $order ['services_id'],
					"name" => 0,
					"user_bank" => 0,
					"user_number" => 0,
					"money" => $order ['money'],
					"end_money" => $order ['money'],
					"user_money" => 0,
					"create_time" => time (),
					"pay_money" => 0,
					"balance_money" => $order ['money'],
					"income_money" => $order ['money'] 
			);
			$s_model->add ( $data );
			$s_info = $s_model->where ( "bank_id = '{$order['services_id']}'" )->find ();
		}
		// 评估
		$services_model = M ( "services" );
		$services_info = $services_model->where ( "id='{$order['services_id']}'" )->find ();
		$data = array (
				"all_nums" => $services_info ['all_nums'] + 1 
		);
		$services_model->where ( "id='{$order['services_id']}'" )->save ( $data );
		
		/* start 增加新订单提醒推送*/
		$content = sprintf(content4, $order_sn);
		$title = title4;
		$tz_content = sprintf(content4, $order_sn);
		$this->pushMessageToSingle($content, $title,$tz_content,$services_info['phone']);
		//插入消息表
		$this->add_message($services_info['id'], 3, 4, '', $content);
		/* end */
		
		// 记录
		$data = array (
				"services_id" => $s_info ['bank_id'],
				"income_money" => $s_info ['income_money'] + $order ['money'],
				"pay_money" => $s_info ['pay_money'],
				"end_money" => $s_info ['end_money'],
				"user_money" => $s_info ['user_money'],
				"money" => $s_info ['money'] + $order ['money'],
				"order_id" => $order ['id'],
				"c_time" => time () 
		);
		$jl_model = M ( "services_jilu" );
		$jl_model->add ( $data );
		
		$car_model = M ( "Car" );
		$car = $car_model->field ( "license_number" )->where ( "id = '{$order['car_id']}'" )->find ();
		$this->assign ( "license_number", $car ['license_number'] );
		$this->assign ( "order", $order );
		$this->display ( ":success_pay" );
	}
}