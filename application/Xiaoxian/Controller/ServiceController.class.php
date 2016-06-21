<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
//use Think\Log;
class ServiceController extends IndexController {
	/**
	 * 项目列表
	 */
	public function service_list() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $violation_model = M ("violation");
	    //0分处罚总数
	    $num_0 = $violation_model->where("points = 0 and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    //1分处罚总数
	    $num_1 = $violation_model->where("points = 1 and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    //2分处罚总数
	    $num_2 = $violation_model->where("points = 2 and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    //3分处罚总数
	    $num_3 = $violation_model->where("points = 3 and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    //6分处罚总数
	    $num_6 = $violation_model->where("points = 6 and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    //12分处罚总数
	    $num_12 = $violation_model->where("points = 12 and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    //其他分处罚总数
	    $num_other = $violation_model->where("points not in ('0','1','2','3','6','12') and state = 0")->count();
	    
	    $services_model = M ("services");
	    $services = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $services['id'];
	    
	    $sc_model = M ( "services_code" );
	    $sc_list = $sc_model->where ( "services_id='$id' and state = '0'" )->select ();
	    $codes = "'0'";
	    foreach ( $sc_list as $v ) {
	        $codes .= ",'{$v['code']}'";
	    }
	    $violation_model = M ( "violation" );
	    
	    //0分处罚已设置项目数
	    $num_0_ed = $violation_model->where ( "code in ($codes) and state = 0 and points = 0 and CHAR_LENGTH(`code`) = 4" )->count ();
	    //1分处罚已设置项目数
	    $num_1_ed = $violation_model->where("code in ($codes) and state = 0 and points = 1 and CHAR_LENGTH(`code`) = 4")->count();
	    //2分处罚已设置项目数
	    $num_2_ed = $violation_model->where("code in ($codes) and state = 0 and points = 2 and CHAR_LENGTH(`code`) = 4")->count();
	    //3分处罚已设置项目数
	    $num_3_ed = $violation_model->where("code in ($codes) and state = 0 and points = 3 and CHAR_LENGTH(`code`) = 4")->count();;
	    //6分处罚已设置项目数
	    $num_6_ed = $violation_model->where("code in ($codes) and state = 0 and points = 6 and CHAR_LENGTH(`code`) = 4")->count();
	    
	    //12分处罚已设置项目数
	    $num_12_ed = $violation_model->where("code in ($codes) and state = 0 and points = 12 and CHAR_LENGTH(`code`) = 4")->count();
	    //其他分处罚已设置项目数
	    $num_other_ed = $violation_model->where("code in ($codes) and points not in ('0','1','2','3','6','12') and state= 0")->count();
	    
	    $list_0 = array(
	        "type"=>'0',
	        "zong"=>$num_0,
	        "yishezhi"=>$num_0_ed,
	    );
	    $list_1 = array(
	        "type"=>'1',
	        "zong"=>$num_1,
	        "yishezhi"=>$num_1_ed,
	    );
	    $list_2 = array(
	        "type"=>'2',
	        "zong"=>$num_2,
	        "yishezhi"=>$num_2_ed,
	    );
	    $list_3 = array(
	        "type"=>'3',
	        "zong"=>$num_3,
	        "yishezhi"=>$num_3_ed,
	    );
	    $list_6 = array(
	        "type"=>'6',
	        "zong"=>$num_6,
	        "yishezhi"=>$num_6_ed,
	    );
	    $list_12 = array(
	        "type"=>'12',
	        "zong"=>$num_12,
	        "yishezhi"=>$num_12_ed,
	    );
	    $list_other = array(
	        "type"=>'other',
	        "zong"=>$num_other,
	        "yishezhi"=>$num_other_ed,
	    );
	    if(isset($list_0) ||isset($list_1) || isset($list_2) || isset($list_3) || isset($list_6) || isset($list_12) || isset($list_other)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "list_0"=>$list_0,
	            "list_1"=>$list_1,
	            "list_2"=>$list_2,
	            "list_3"=>$list_3,
	            "list_6"=>$list_6,
	            "list_12"=>$list_12,
	            "list_other"=>$list_other,
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
	 * 项目详情列表
	 */
	public function dateil_list(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
        
	    $pageIndex = $_REQUEST['pageIndex'];
	    $pageSize = $_REQUEST['pageSize'];
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    
	    $service = $_REQUEST['service'];
	    $violation_model = M ("violation");
	    //分页算法
	    if ($pageIndex != '' && $pageSize != '') {
	        $pageID = ($pageIndex - 1);
	        if($pageID < 0){
	            $pageID =0;
	        }
	        $pageID=$pageID*$pageSize;
	    }
	    /* $count = $violation_model->where("points = '$service' and state = 0 and CHAR_LENGTH(`code`) = 4")->count();
	    $page = $this->page ( $count, $pageSize, $pageIndex );
	    if(ceil($count/$pageSize) < $pageIndex){
	        $msg = array (
	            "code" => 101,
	            "dateil_list"=>'',
	        );
	        echo json_encode($msg);
	        $this->remove_boms();
	        exit();
	    } */
	    if($service == 'other'){
	        $violation_list = $violation_model->field("code, money, points,content")->where("points not in (0,1,2,3,6,12) and state = 0")->order ( "code" )->limit ( $pageID, $pageSize )->select();
	    }else{
	        $violation_list = $violation_model->field("code, money, points,content")->where("points = '$service' and state = 0 and CHAR_LENGTH(`code`) = 4")->order ( "code" )->limit ( $pageID, $pageSize )->select();
	    }
	    foreach($violation_list as $k=>$v){
	        $scode_model = M ( "services_code" );
	        $scode_info = $scode_model->where ( "services_id = '{$s_id['id']}' and code = '{$v['code']}' and state = 0" )->find ();
	        if(!empty($scode_info)){
	            $violation_list[$k]['check_state'] = 1;//已选择
	        }else{
	            $violation_list[$k]['check_state'] = 0;//未选择
	        }
	    }
	    if(isset($violation_list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "dateil_list"=>$violation_list,
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
	 * 项目添加
	 */
	public function add(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();//获取服务商id
	    
	    $service_item = $_REQUEST['service_item'];
	    $code = explode("|", $service_item);
	    $sc_model = M ( "services_code" );
	    $violation_model = M ( "violation" );
	    foreach ($code as $c){
	        //$violation = $violation_model->field("points")->where ( "code = '$c' and state = 0" )->find ();
	        $sc_info = $sc_model->field("id,state")->where("code ='$c' and services_id = '{$s_id['id']}'")->find();
	        if(empty($sc_info)){
	            $data = array(
	                'services_id' => $s_id['id'], 
	                'code'=> $c,
	                'c_time'=> time(),
	                'state'=> 0,
	            );
	            $res = $sc_model->add($data);
	        }else {
	            if($sc_info['state'] == 1){
	                $data = array (
	                    "state" => 0,
	                    "c_time" => time ()
	                );
	             $res = $sc_model->where ( "id='{$sc_info['id']}'" )->save ( $data );
	            } 
	        }
	    }
	    if($res>0){
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
	 * 项目取消
	 */
	public function cancel(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();//获取服务商id
	     
	    $service_item = $_REQUEST['service_item'];
	    $code = explode("|", $service_item);
	    $sc_model = M ( "services_code" );
	    $violation_model = M ( "violation" );
	    foreach ($code as $c){
	        $sc_info = $sc_model->where("code ='$c' and services_id = '{$s_id['id']}' and state = 0")->find();
	        if(empty($sc_info)){
	            $msg = array (
	                "code" => 301,
	                "msg" => '未知错误',
	            );
	            echo json_encode($msg);
	            $this->remove_boms();
	            exit();
	        }
	            $data = array(
	                'c_time'=> time(),
	                'state'=> 1
	            );
	            $res = $sc_model->where("code ='$c' and services_id = '{$s_id['id']}'")->save($data);
	    }
	    if($res>0){
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
	
}