<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
//use Think\Log;
class YinlianController extends IndexController {
	/**
	 * 记录银行卡信息接口(绑定银行卡)
	 */
	public function yinlianBind() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $clientUUID = $_REQUEST['clientUUID'];
	    
	    $phone = $_REQUEST['phone'];
	    $user_bank = $_REQUEST['bankName'];
	    $model_yh = M ('yinhang');
	    $yh_info = $model_yh->field("id")->where("bank_name = '$user_bank'")->find();//获取银行id
	    $name = $_REQUEST['realname'];
	    $user_number = $_REQUEST['card'];
	    $model = M ("services");
	    $services = $model->field("id")->where("phone = '$phone'")->find();
	    $data = array(
	        'user_bank'=>$user_bank,
	        'user_number'=>$user_number,
	        'name'=>$name,
	        'yh_id'=>$yh_info['id']
	    );
	    $model = M ("bank");
	    $res = $model->where("bank_id = '{$services['id']}'")->save($data);
	    if($res){
	        //消息推送(绑定银行卡)
	        $time = date("Y.m.d H:i:s");
	        $weihao = substr($user_number, -4);
	        $content = sprintf(content11, $time,$weihao,$user_bank);
	        $title = title11;
	        $tz_content = sprintf(content11, $time,$weihao,$user_bank);
	        $re = $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	         //插入消息表
	         $this->add_message($services['id'], 4, '', 3, $content);
	     }
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
	 * 解绑银行卡接口
	 */
	public function deleteBank() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    $clientUUID = $_REQUEST['clientUUID'];
	    
	    $phone = $_REQUEST['phone'];
	    $smsCode = $_REQUEST['smsCode'];
	    $duanxin_model = M ("duanxin");
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
	    
	    $model = M ("services");
	    $services = $model->field("id")->where("phone = '$phone'")->find();
	    $id = $services['id'];
	    
	    $model = M ("bank");
	    $bank = $model->field("user_bank,user_number")->where("bank_id = '$id'")->find();
	    $data = array(
	        'user_bank'=>'',
	        'user_number'=>'',
	        'name'=>'',
	        'yh_id'=>''
	    );
	    $res = $model->where("bank_id = '$id'")->save($data);
	    if($res){
	        //消息推送(解绑银行卡)
	        $time = date("Y.m.d H:i:s");
	        $weihao = substr($bank['user_number'], -4);
	        $content = sprintf(content12, $time,$weihao,$bank['user_bank']);
	        $title = title2;
	        $tz_content = sprintf(content12, $time,$weihao,$bank['user_bank']);
	        $re = $this->pushMessageToSingle($content, $title,$tz_content,$phone);
	        //插入消息表
	        $this->add_message($id, 4, '', 3, $content);
	     }
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
	 * 银行列表
	 */
    public function banklist(){
        //验证token
        $secret = $this->checkAccess($_REQUEST);
         
        $pageIndex = $_REQUEST['pageIndex'];
        $pageSize = $_REQUEST['pageSize'];
        $model = M ('yinhang');
        $order = "sort desc";
        //分页算法
        if ($pageIndex != '' && $pageSize != '') {
            $pageID = ($pageIndex - 1);
            if($pageID < 0){
                $pageID =0;
            }
            $pageID=$pageID*$pageSize;
        }
        $roles = $model->field ( "id,sort,bank_name,bank_img,state " )->where("state=0")->order ( $order )->limit ( $pageID, $pageSize )->select ();
        if(isset($roles)){
            $msg = array (
                "code" => 101,
                "msg" => '操作成功',
                "banklist"=>$roles
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
     * 申请提现
     */
    public function tixian(){
        //验证token
        $secret = $this->checkAccess($_REQUEST);
        
        $please_money = $_REQUEST['money'];
        if($please_money < 500){
            $msg = array(
                "code"=>201,
                "msg"=>'提现金额500元起'
            );
            echo json_encode($msg);
            $this->remove_boms();
            exit();
        }
        $phone = $_REQUEST['phone'];
        $services_model = M ("services");
        $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
        $id = $s_id['id'];//获取服务商id
        
        $model_bank = M ("bank"); 
        $bank_info = $model_bank->field("id,user_money,money,balance_money,pay_money,user_number,name,user_bank")->where("bank_id = '$id'")->find();
        
        if(!empty($bank_info)){
            if($please_money > $bank_info['user_money']){
                $msg = array(
                    "code"=>501,
                    "msg"=>'提现金额超出账户可用金额'
                );
                echo json_encode($msg);
                $this->remove_boms();
                exit();
            }
            //$bank_info['money_daozhang'] = $bank_info['money'] * 0.97;
            $model_expend = M ("expend");
            $data = array(
                'expend_id'=>$bank_info['id'],
                'bank_state'=>1,
                'please_time'=>time(),
                'please_money'=>$please_money,
                'card_number'=>$bank_info['user_number'],
                'tixian_name'=>$bank_info['name'],
                'user_bank'=>$bank_info['user_bank']
            );
            $res = $model_expend->add($data);
            if($res){
                //扣除账户可用金额
                $data = array(
                    'user_money'=> ($bank_info['user_money']-$please_money > 0) ? $bank_info['user_money']-$please_money : '0',
                    'money'=> ($bank_info['money']-$please_money > 0) ? $bank_info['money']-$please_money : '0',
                    'balance_money'=> ($bank_info['balance_money']-$please_money > 0) ? $bank_info['balance_money']-$please_money : '0',
                    'pay_money'=> $bank_info['pay_money'] + $please_money
                );
                $res = $model_bank->where("bank_id = '$id'")->save($data);
                $bank = $model_bank->where("bank_id = '$id'")->find();
                if($res > 0){
                    //推送消息
                    $time = date("Y.m.d H:i:s");
                    $now_ketixian = $bank['user_money'];
                    $content = sprintf(content10_2, $time,$please_money,$now_ketixian);
                    $title = title10;
                    $tz_content = sprintf(content10_2, $time,$please_money,$now_ketixian);
                    $model = new IndexController();
                    $model->pushMessageToSingle($content, $title,$tz_content,$phone);
                    //插入消息表
                    $model->add_message($id, 4, '', 2, $content);
                }
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
    /**
     * 提现列表
     */
    public function tixian_list(){
        //验证token
        $secret = $this->checkAccess($_REQUEST);
        
        $pageIndex = $_REQUEST['pageIndex'];
        $pageSize = $_REQUEST['pageSize'];
        $phone = $_REQUEST['phone'];
        $model = M ('expend');
        //分页算法
        if ($pageIndex != '' && $pageSize != '') {
            $pageID = ($pageIndex - 1);
            if($pageID < 0){
                $pageID =0;
            }
            $pageID=$pageID*$pageSize;
        }
        $list = $model->field("e.id,e.please_money,e.please_time")->table('cw_expend as e')->join("cw_bank as b on e.expend_id = b.id")->join("cw_services as s on b.bank_id = s.id")->where("s.phone = '$phone'")->order(array('e.please_time'=>'DESC'))->limit ( $pageID, $pageSize )->select();
        foreach ($list as $k=>$l){
            $list[$k]['please_time'] = date("Y/m/d H:i",$l['please_time']);
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
}