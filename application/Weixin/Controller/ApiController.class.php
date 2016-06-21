<?php

namespace Weixin\Controller;
//use Common\Controller\HomeBaseController;
use Weixin\Controller\IndexController;
use Think\Log;

class ApiController extends IndexController {
	
	function index() {
		$this->display();
	}
	
	function push_weizhang(){
		$car_id = $_REQUEST['car_id'];
		$end_id = $_REQUEST['end_id'];
		$this->push($car_id, $end_id);
	}
	
	// 推送
	function push($car_id, $end_id) {
		$log = new Log ();
		$log->write ( "send---------------------------$end_id", 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		$end_model = M ( "endorsement" );
		$end_info = $end_model->where ( "id='$end_id'" )->find ();
		if (empty ( $end_info )) {
			return false;
		}
		$log->write ( "senddata---------------------------" . json_encode ( $end_info ), 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		$car_model = M ( "Car" );
		$car_info = $car_model->where ( "id='$car_id'" )->find ();
		$user_model = M ();
		$user = $user_model->table ( "cw_user as u" )->join ( "cw_user_car as uc on uc.user_id = u.id" )->field ( "u.id, u.openid, u.nickname, u.channel, u.channel_key" )->where ( "uc.car_id='$car_id' and uc.is_sub = 0" )->select ();
		$date = date ( "Y年m月d日 H:i", $end_info ['time'] );
		foreach ( $user as $p ) {
			if($p['channel'] == 0){
				$data = array (
					'first' => array (
							'value' => urlencode ( "{$p['nickname']}，{$car_info ['license_number']}有一条新违章" ),
							'color' => "#000000" 
					),
					'violationTime' => array (
							'value' => urlencode ( $date ),
							'color' => '#000000' 
					),
					'violationAddress' => array (
							'value' => urlencode ( "{$end_info['address']}" ),
							'color' => '#000000' 
					),
					'violationType' => array (
							'value' => urlencode ( "{$end_info['content']}" ),
							'color' => '#000000' 
					),
					'violationFine' => array (
							'value' => urlencode ( "{$end_info['money']}" ),
							'color' => '#000000' 
					),
					'violationPoints' => array (
							'value' => urlencode ( "{$end_info['points']}" ),
							'color' => '#000000' 
					),
					'remark' => array (
							'value' => urlencode ( "" ),
							'color' => '#000000' 
					) 
				);
				$this->sendWeiXin ($p ['openid'], MUBAN2, URL3 . "&openid=" . $p ['openid'] . "&carid=" . $car_id . "&end_id=" . $end_id, $data);
				
				$data = array (
					"from_userid" => 0,
					"openid" => $p ['openid'],
					"tar_id" => $end_info ['id'],
					"create_time" => time (),
					"msg_type" => 2,
					"nums" => 1,
					"all_points" => $end_info ['points'],
					"all_money" => $end_info ['money'] 
				);
				$model = M ( "Message" );
				$model->add ( $data );
			}
		}
	}
	
	// 发送自定义的模板消息
	public function sendWeiXin($touser, $template_id, $url, $data, $topcolor = '#7B68EE') {
		$log = new Log ();
		$template = array (
				'touser' => $touser,
				'template_id' => $template_id,
				'url' => $url,
				'topcolor' => $topcolor,
				'data' => $data 
		);
		$json_template = json_encode ( $template );
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->get_access_token ();
		$dataRes = $this->request_post ( $url, urldecode ( $json_template ) );
		$log->write ( serialize ( $dataRes ), 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		if ($dataRes ['errcode'] == 0) {
			return true;
		} else {
			return false;
		}
	}
	
	function finish_order($e_id) {
	    //$log = new Log();
		$order_model = M ( "order" );
		$order_info = $order_model->where ( "endorsement_id='$e_id'" )->find ();
		if (! empty ( $order_info )) {
			$data = array (
					"order_status" => 5,
					"last_time" => time () 
			);
			$order_model->where ( "id='{$order_info['id']}'" )->save ( $data );
			
			$t_order_model = M ( "turn_order" );
			$data = array (
					"state" => 5,
					"l_time" => time () 
			);
			$t_order_info = $t_order_model->where ( "order_id = '{$order_info['id']}' and sod_id = '{$order_info['so_id']}'" )->find ();
			$t_order_model->where ( "order_id = '{$order_info['id']}' and sod_id = '{$order_info['so_id']}'" )->save ( $data );
			if (! empty ( $t_order_info )) {
				$so_model = M ( "services_order" );
				$so_info = $so_model->where ( "id='{$order_info['so_id']}'" )->find ();
				$bank_model = M ( "bank" );
				$bank_info = $bank_model->where ( "bank_id='{$order_info['services_id']}'" )->find ();
				if (! empty ( $bank_info )) {
					$data = array (
							"end_money" => ($bank_info ['end_money'] - $so_info ['money']) > 0 ? ($bank_info ['end_money'] - $so_info ['money']) : 0,
							"user_money" => $bank_info ['user_money'] + $so_info ['money'],
							"income_money" => $bank_info ['income_money'] + $so_info ['money'] 
					);
					$res = $bank_model->where ( "id='{$bank_info['id']}'" )->save ( $data );
					
					/* start 增加可提现金额变动提醒推送*/
					if($res > 0){
					    //可提现金额变动提醒
					    $model_services = M ("services");
					    $services = $model_services->where ("id = '{$order_info['services_id']}'")->find();
					    $time = date("Y.m.d H:i:s");
					    $money = $so_info ['money'];
					    $user_money = $bank_info ['user_money'];
					    $content = sprintf(content10_1, $time,$money,$user_money);
					    $title = title10;
					    $tz_content = sprintf(content10_1, $time,$money,$user_money);
					    $this->pushMessageToSingle($content, $title,$tz_content,$services['phone']);
					    $this->add_message($services['id'], 4, '', 2, $content);
					}
					/* end */
				}
				// 记录
				$bank_info = $bank_model->where ( "bank_id='{$order_info['services_id']}'" )->find ();
				$data = array (
						"services_id" => $bank_info ['bank_id'],
						"income_money" => $bank_info ['income_money'],
						"pay_money" => $bank_info ['pay_money'],
						"end_money" => $bank_info ['end_money'],
						"user_money" => $bank_info ['user_money'],
						"money" => $bank_info ['money'],
						"order_id" => $order_info ['id'],
						"c_time" => time () 
				);
				$jl_model = M ( "services_jilu" );
				$jl_model->add ( $data );
			}
			// 推送消息
			$model = M ();
			$user = $model->table ( "cw_order as o" )->join ( "cw_user as u on u.id=o.user_id" )->join ( "cw_car as c on c.id=o.car_id" )->field ( "u.openid, o.order_sn, c.license_number" )->where ( "o.id = '{$order_info['id']}'" )->find ();
			if (! empty ( $user )) {
				$data = array (
						'first' => array (
								'value' => urlencode ( first_key ),
								'color' => "#000000" 
						),
						'keyword1' => array (
								'value' => urlencode ( "{$user ['order_sn']}" ),
								'color' => '#000000' 
						),
						'keyword2' => array (
								'value' => urlencode ( "{$user['license_number']}" ),
								'color' => '#000000' 
						),
						'keyword3' => array (
								'value' => urlencode ( status2 ),
								'color' => '#000000' 
						),
						'remark' => array (
								'value' => urlencode ( last_key ),
								'color' => '#000000' 
						) 
				);
				$this->doSend ( 0, '', $user ['openid'], MUBAN3, URL2, $data );
			}
			
			/*start 增加订单结算提醒推送 */
			$model_services = M ("services");
			$services = $model_services->where ("id = '{$order_info['services_id']}'")->find();
			$content = sprintf(content7, $order_info['order_sn']);
			$title = title7;
			$tz_content = sprintf(content7, $order_info['order_sn']);
			$this->pushMessageToSingle($content, $title,$tz_content,$services['phone']);
			//插入消息表
			$this->add_message($services['id'], 3, 7, '', $content);
			/* end */
		}
	}
}