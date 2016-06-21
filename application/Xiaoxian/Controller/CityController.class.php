<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
//use Think\Log;
class CityController extends IndexController {
	/**
	 * 设置-城市列表接口
	 */
	public function city_list() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    
	    $province = $_REQUEST['province'];
	    $model = M ("region");
	    $where = "level = 2 and is_dredge = 0 and province = '$province'";
	    $city_list = $model->field("id,city,nums")->where($where)->order("id")->group ( "city" )->select();
	    $sc_model = M ( "services_city" );
	    $nums_choose = 0;
	    foreach ($city_list as $k=>$v){
	        $sc_info = $sc_model->field("id")->where ( "services_id='{$s_id['id']}' and code = '{$v[id]}' and state = 0" )->find ();
	        if(! empty ( $sc_info )){
	            $nums_choose++;
	            $city_list[$k]['check_state'] = 1;//已选择
	        }else {
	            $city_list[$k]['check_state'] = 0;//未选择
	        }
	    }
	    //获取省份下的所有城市数量
	    $nums_zong = $model->where($where)->count();
	    if($nums_choose < $nums_zong){
	        $state = 0;//省份没有被全选
	    }else{
	        $state = 1;//省份被全选
	    }
	    if(isset($city_list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "city_list"=>$city_list,
	            "state"=>$state
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
	 * 设置-城市添加接口
	 */
	public function city_add() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
        
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    
	    $provincename = $_REQUEST['provincename'];
	    $cityname = $_REQUEST['cityname'];
	    $city = explode("|", $cityname);
	    $model = M ("region");
	    $sc_model = M ( "services_city" );
	    foreach($city as $k=>$c){
	        $city_info = $model->field("id")->where("city = '$c' and level = 2 and is_dredge = 0")->find();
	        $sc_info = $sc_model->where ( "services_id='{$s_id['id']}' and code = '{$city_info['id']}'" )->find ();
	        if (empty ( $sc_info )) {
	            $data = array (
	                "services_id" => $s_id['id'],
	                "code" => ($city_info['id'] ? $city_info['id'] : ''),
	                "state" => 0,
	                "time" => time ()
	            );
	            $res = $sc_model->add ( $data );
	        } else {
	            if ($sc_info ['state'] == 1) {
	                $data = array (
	                    "state" => 0,
	                    "time" => time ()
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
	 * 取消城市接口
	 */
	public function cancel(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	     
	    //$provincename = $_REQUEST['provincename'];
	    $cityname = $_REQUEST['cityname'];
	    $city = explode("|", $cityname);
	    $model = M ("region");
	    $sc_model = M ( "services_city" );
	    foreach($city as $k=>$c){
	        $city_info = $model->field("id")->where("city = '$c' and level = 2 and is_dredge = 0")->find();
	        $sc_info = $sc_model->where("services_id='{$s_id['id']}' and code = '{$city_info['id']}' and state = 0")->find();
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
	            'state'=>1,
	            'time'=> time()
	        );
	        $res = $sc_model->where("services_id='{$s_id['id']}' and code = '{$city_info['id']}'")->save($data);
	        //去除定价表里面对应城市的定价
	        $model = M ("services_order");
	        $model->where("services_id='{$s_id['id']}' and code = '{$city_info['id']}'")->delete();
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
	 * 全选取消接口
	 */
	public function cancel_all(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	
	    $provincename = $_REQUEST['provincename'];
	    $model = M ("region");
	    $citys = $model->where("province='$provincename' and level = 2 and is_dredge = 0")->select();
	    $sc_model = M ( "services_city" );
	    foreach($citys as $k=>$c){
	        $sc_info = $sc_model->field("id")->where("services_id='{$s_id['id']}' and code = '{$c['id']}'")->find();
	        if(!empty($sc_info)){
	            $data = array(
	                'state'=>1,
	                'time'=> time()
	            );
	           $res =  $sc_model->where("services_id='{$s_id['id']}' and code = '{$c['id']}'")->save($data);
	           //去除定价表里面对应城市的定价
	           $model = M ("services_order");
	           $model->where("services_id='{$s_id['id']}' and code = '{$c['id']}'")->delete();
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
	 * 设置-省份列表接口
	 */
	public function province_list() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);

	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];
	    
	    $model = M ("region");
	    $where = "level = 1 and is_dredge = 0";
	    $province_list = $model->field("id,province,abbreviation")->where($where)->order("id")->select();
	    $sc_model = M ("services_city");
	    foreach ($province_list as $k=>$v){
	        //总的城市数
	        $c_num = $model->field('count(distinct city) as num')->where("province = '{$v['province']}' and is_dredge = 0 and level = 2")->find();
	        $province_list[$k]['city_num'] = $c_num['num'];
	        //已选择的城市数
	        $city_my = $sc_model->field("count(*) as num")->table("cw_services_city as sc")->join("cw_region as r on sc.code = r.id")->where("sc.services_id = '$id' and sc.state = 0 and r.province = '{$v['province']}'")->find();
	        $province_list[$k]['city_num_ed'] = $city_my['num'];
	    }
	    if(isset($province_list)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "province_list"=>$province_list
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