<?php

namespace Xiaoxian\Controller;
use Xiaoxian\Controller\IndexController;
//use Think\Log;
class DingjiaController extends IndexController {
   /*  public function __construct() {
        $this->remove_boms();
    } */
	/**
	 * 设置-服务定价-我的省份列表
	 */
	public function ownerprovince_list() {
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $sc_model = M ("services_city");
	    $roles = $sc_model->field ( "r.province" )->table ( "cw_services_city as sc" )->join ( "cw_region as r on r.id=sc.code" )->where ( "sc.services_id = '{$s_id['id']}' and sc.state = 0" )->select ();
	    $provinces = "'0'";
	    foreach ( $roles as $v ) {
	        $provinces .= ",'{$v['province']}'";
	    }
	    $region = M ( "region" );
	    $province = $region->field("id,province, abbreviation")->where ( "is_dredge=0 and level=1 and province in ($provinces)" )->select ();
	    $so_model = M ("services_order");
	    foreach ($province as $k=>$v){
	        //总的城市数
	        $c_num = $sc_model->table("cw_services_city as sc")->join("cw_region as r on sc.code = r.id")->where("sc.services_id = '$id' and sc.state = 0 and r.province = '{$v['province']}'")->count();
	        //$c_num = $region->where("province = '{$v['province']}' and is_dredge = 0 and level = 2")->count();
	        $province[$k]['city_num'] = $c_num; 
	        //已选择的城市数
	        $city_my = $so_model->field("count(distinct so.code) as num")->table("cw_services_order as so")->join("cw_region as r on so.code = r.id")->where("so.services_id = '$id' and  r.province = '{$v['province']}'")->find();
	        //$city_my = $sc_model->field("count(*) as num")->table("cw_services_city as sc")->join("cw_region as r on sc.code = r.id")->where("sc.services_id = '$id' and sc.state = 0 and r.province = '{$v['province']}'")->find();
	        $province[$k]['city_num_ed'] = $city_my['num']; 
	    }
	    if(isset($province)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "ownerprovince_list"=>$province
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
	 * 设置-服务定价-我的城市列表
	 */
	public function ownercity_list(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $province = $_REQUEST ['province'];
	    //获取总的服务数
	    $model = M ( "services_code" );
	    $sc_list = $model->where ( "services_id='$id' and state = '0'" )->select ();
	    $codes = "'0'";
	    foreach ( $sc_list as $v ) {
	        $codes .= ",'{$v['code']}'";
	    }
	    $violation_model = M ( "violation" );
	    $violation = $violation_model->field("count(id) as num_zong")->where ( "code in ($codes) and state = 0" )->find ();
	    
	    $sc_model = M ("services_city");
	    //已选择的城市列表
	    $city = $sc_model->field ( "r.city, r.nums, r.id" )->table ( "cw_services_city as sc" )->join ( "cw_region as r on r.id=sc.code" )->where ( "r.is_dredge=0 and r.level=2 and sc.services_id = '$id' and r.province = '$province' and sc.state = 0" )->group ( "city" )->select ();
	    $so_model = M ( "services_order" );
	    foreach ($city as $k=>$c){
	        $city_id = $c['id'];
	        //每个城市已经定价的服务数
	        $city[$k]['num_yixuanze'] = $so_model->table("cw_services_order")->where("services_id = '$id' and code='$city_id'")->count();
	        $city[$k]['num_zong'] = $violation['num_zong'];
	        $province_fuwu_num += $city[$k]['num_yixuanze'];//省份的服务数
	    }
	    if(isset($city)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "province_fuwu_num"=>$province_fuwu_num,
	            "ownercity_list"=>$city
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
	 * 设置-服务定价-项目列表
	 */
	public function item_list(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $city_name = $_REQUEST['city_name'];
	    $model = M ('region');
	    $city = $model->where("city='$city_name'")->find();
	    $city_id = $city['id'];
	    
	    $sc_model = M ( "services_code" );
	    $sc_list = $sc_model->where ( "services_id='$id' and state = '0'" )->select ();
	    $codes = "'0'";
	    foreach ( $sc_list as $v ) {
	        $codes .= ",'{$v['code']}'";
	    }
	    $violation_model = M ("violation");
	    $so_model = M ( "services_order" );
	     //0分处罚已设置项目数
	    $num_0 = $violation_model->where ( "code in ($codes) and state = 0 and points = 0 and CHAR_LENGTH(`code`) = 4" )->count ();
	    //0分已定价数
	    $violation = $violation_model->where ( "code in ($codes) and state = 0 and points = 0 and CHAR_LENGTH(`code`) = 4" )->select();
	    $num0=0;
	    foreach ($violation as $v){
	        $code_0 = $v['code'];
	        $num_0_ed = $so_model->where ( "services_id = '$id' and violation in ($code_0) and code = '$city_id' " )->find ();
	        if(!empty($num_0_ed)){
	            $num0++;
	        }
	    }
	    $num_0_ed = $num0;
	    //1分处罚已设置项目数
	    $num_1 = $violation_model->where("code in ($codes) and state = 0 and points = 1 and CHAR_LENGTH(`code`) = 4")->count();
	    $violation1 = $violation_model->where ( "code in ($codes) and state = 0 and points = 1 and CHAR_LENGTH(`code`) = 4" )->select();
	    $num1=0;
	    foreach ($violation1 as $v){
	        $code_1 = $v['code'];
	        $num_1_ed = $so_model->where ( "services_id = '$id' and violation in ($code_1) and code = '$city_id' " )->find ();
	        if(!empty($num_1_ed)){
	            $num1++;
	        }
	    }
	    $num_1_ed = $num1;
	    //2分处罚已设置项目数
	    $num_2 = $violation_model->where("code in ($codes) and state = 0 and points = 2 and CHAR_LENGTH(`code`) = 4")->count();
	    $violation2 = $violation_model->where ( "code in ($codes) and state = 0 and points = 2 and CHAR_LENGTH(`code`) = 4" )->select();
	    $num2=0;
	    foreach ($violation2 as $v){
	        $code_2 = $v['code'];
	        $num_2_ed = $so_model->where ( "services_id = '$id' and violation in ($code_2) and code = '$city_id' " )->find ();
	        if(!empty($num_2_ed)){
	            $num2++;
	        }
	    }
	    $num_2_ed = $num2;
	    //3分处罚已设置项目数
	    $num_3 = $violation_model->where("code in ($codes) and state = 0 and points = 3 and CHAR_LENGTH(`code`) = 4")->count();
	    $violation3 = $violation_model->where ( "code in ($codes) and state = 0 and points = 3 and CHAR_LENGTH(`code`) = 4" )->select();
	    $num3=0;
	    foreach ($violation3 as $v){
	        $code_3 = $v['code'];
	        $num_3_ed = $so_model->where ( "services_id = '$id' and violation in ($code_3) and code = '$city_id' " )->find ();
	        if(!empty($num_3_ed)){
	            $num3++;
	        }
	    }
	    $num_3_ed = $num3;
	    //6分处罚已设置项目数
	    $num_6 = $violation_model->where("code in ($codes) and state = 0 and points = 6 and CHAR_LENGTH(`code`) = 4")->count();
	    $violation6 = $violation_model->where ( "code in ($codes) and state = 0 and points = 6 and CHAR_LENGTH(`code`) = 4" )->select();
	    $num6=0;
	    foreach ($violation6 as $v){
	        $code_6 = $v['code'];
	        $num_6_ed = $so_model->where ( "services_id = '$id' and violation in ($code_6) and code = '$city_id' " )->find ();
	        if(!empty($num_6_ed)){
	            $num6++;
	        }
	    }
	    $num_6_ed = $num6;
	    //12分处罚已设置项目数
	    $num_12 = $violation_model->where("code in ($codes) and state = 0 and points = 12 and CHAR_LENGTH(`code`) = 4")->count();
	    $violation12 = $violation_model->where ( "code in ($codes) and state = 0 and points = 12 and CHAR_LENGTH(`code`) = 4" )->select();
	    $num12=0;
	    foreach ($violation12 as $v){
	        $code_12 = $v['code'];
	        $num_12_ed = $so_model->where ( "services_id = '$id' and violation in ($code_12) and code = '$city_id' " )->find ();
	        if(!empty($num_12_ed)){
	            $num12++;
	        }
	    }
	    $num_12_ed = $num12;;
	    //其他分处罚已设置项目数
	    $num_other = $violation_model->where("code in ($codes) and points not in ('0','1','2','3','6','12') and state= 0")->count();
	    $violation_other = $violation_model->where ( "code in ($codes) and state = 0 and points not in ('0','1','2','3','6','12')" )->select();
	    $numother=0;
	    foreach ($violation_other as $v){
	        $code_other = $v['code'];
	        $num_other_ed = $so_model->where ( "services_id = '$id' and violation in ($code_other) and code = '$city_id' " )->find ();
	        if(!empty($num_other_ed)){
	            $numother++;
	        }
	    }
	    $num_other_ed = $numother;
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
	 * 服务定价-详情列表
	 */
	public function ownerservice_dateil_list(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $pageIndex = $_REQUEST['pageIndex'];
	    $pageSize = $_REQUEST['pageSize'];
	    $service = $_REQUEST['service'];
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    //获取城市id
	    $cityname = isset($_REQUEST['cityname']) ? $_REQUEST['cityname'] : '';
	    $region_model = M ("region");
	    $cityinfo = $region_model->field("id")->where("city='$cityname' and level = 2 and is_dredge = 0")->find();
	    $city_id = $cityinfo['id'];
	    
	    $sc_model = M ( "services_code" );
	    $sc_list = $sc_model->where ( "services_id='$id' and state = '0'" )->select ();
	    $codes = "'0'";
	    foreach ( $sc_list as $v ) {
	        $codes .= ",'{$v['code']}'";
	    }
	    $violation_model = M ( "violation" );
	    //分页算法
	    if ($pageIndex != '' && $pageSize != '') {
	        $pageID = ($pageIndex - 1);
	        if($pageID < 0){
	            $pageID =0;
	        }
	        $pageID=$pageID*$pageSize;
	    }
	    if($service == 'other'){
	        $violation = $violation_model->field("code,money,points,content")->where ( "code in ($codes) and state = 0 and points not in ('0','1','2','3','6','12') " )->order ( "code" )->limit ( $pageID, $pageSize )->select ();
	    }else{
	        $violation = $violation_model->field("code,money,points,content")->where ( "code in ($codes) and state = 0 and points = '$service' and CHAR_LENGTH(`code`) = 4" )->order ( "code" )->limit ( $pageID, $pageSize )->select ();
	    }
	    $so_model = M ( "services_order" );
	    if($cityname != ''){
	        foreach ( $violation as $k=>$v ) {
	            $so_info = $so_model->where ( "services_id = '$id' and violation = '{$v['code']}' and code = '$city_id'" )->find ();
	            if (empty ( $so_info )) {
	                $violation[$k]['dingjia'] = $v['money'] + $v['points'] * 100 + 30;
	                $violation[$k]['set_state'] = 0;//未设置过
	               /*  $data = array(
	                    'services_id'=>$id,
	                    'code'=>$city_id,
	                    'violation'=>$v['code'],
	                    'money'=>$violation[$k]['dingjia'],
	                    'create_time'=>time()
	                );
	                $so_model->add($data); */
	            } else {
	                if ($so_info ['money'] > 0) {
	                    $violation[$k]['dingjia'] = $so_info ['money'];
	                    $violation[$k]['set_state'] = 1;//设置过
	                }
	            }
	        }
	    }
	    //获取省份下的所有城市id
	    $provincename = isset($_REQUEST['provincename']) ? $_REQUEST['provincename'] : '';
	    if($provincename != ''){
	        //获取全省的
	        $provinceinfo = $region_model->field("id")->where("province='$provincename' and level = 2 and is_dredge = 0")->select();
	        $list_quansheng = array();
	        /* foreach ($provinceinfo as $key=>$p) { */
	            foreach ( $violation as $k=>$v ) {
	                $so_info = $so_model->where ( "services_id = '$id' and violation = '{$v['code']}' and code = '{$provinceinfo['0']['id']}'" )->find ();
	                if (empty ( $so_info )) {
	                    $violation[$k]['dingjia'] = $v['money'] + $v['points'] * 100 + 30;
	                    $violation[$k]['set_state'] = 0;//未设置过
	                     $data = array(
	                        'services_id'=>$id,
	                        'code'=>$provinceinfo['0']['id'],
	                        'violation'=>$v['code'],
	                        'money'=>$violation[$k]['dingjia'],
	                        'create_time'=>time()
	                    );
	                    $so_model->add($data); 
	                } else {
	                    if ($so_info ['money'] > 0) {
	                        $violation[$k]['dingjia'] = $so_info ['money'];
	                        $violation[$k]['set_state'] = 1;//设置过
	                    }
	                }
	            }
	            $list_quansheng = $violation;
	        /* } */
	    }
	    if(isset($violation)){
	        if($cityname == ''){
	            $msg = array (
	                "code" => 101,
	                "msg" => '操作成功',
	                "list_quansheng"=>$list_quansheng
	            );
	        }else{
	            $msg = array (
	                "code" => 101,
	                "msg" => '操作成功',
	                "ownerservice_dateil_list"=>$violation,
	                "list_quansheng"=>$list_quansheng
	            );
	        }
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
	 * 服务定价-添加
	 */
	public function makeprice(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $provincename = isset($_REQUEST['provincename']) ? $_REQUEST['provincename'] : '';
	    $cityname = isset($_REQUEST['cityname']) ? $_REQUEST['cityname'] : '';
	    $servicename = $_REQUEST['servicename'];
	    $servicecode = $_REQUEST['servicecode'];
	    $money = $_REQUEST['money'];
	    
	    $servicecode = explode("|", $servicecode);
	    $money = explode("|", $money);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $model = M ("services_order");
	    $region_model = M ("region");
	    if($cityname != ''){//定价单个城市
	        $cityinfo = $region_model->field("id")->where("city='$cityname' and level = 2 and is_dredge = 0")->find();
	        $city_id = $cityinfo['id'];//获取城市id
	        foreach ($servicecode as $k=>$v){
	            $so_info = $model->where ( "services_id = '$id' and violation = '$v' and code = '$city_id'" )->find ();
	            $data = array(
	                "services_id" => $id,
	                "code" => $city_id,
	                "violation" => $v,
	                "money" => $money[$k],
	                "create_time" => time ()
	            );
	            if (empty ( $so_info )) {
	                $res = $model->add ( $data );
	            } else {
	                $res = $model->where ( "id='{$so_info['id']}'" )->save ( $data );
	            }
	        }
	    }
	    if($provincename != ''){//定价全省下面我选择的城市
	        $citys = $region_model->field("r.id")->table("cw_region as r")->join("cw_services_city as sc on r.id = sc.code")->where("r.province='$provincename' and r.level = 2 and r.is_dredge = 0 and sc.state = 0 and sc.services_id = '$id'")->select();//获取省份下面所有城市id
	        foreach ($citys as $c){
	            $city_id = $c['id'];
	            foreach ($servicecode as $k=>$v){
	                $so_info = $model->where ( "services_id = '$id' and violation = '$v' and code = '$city_id'" )->find ();
	                $data = array(
	                    "services_id" => $id,
	                    "code" => $city_id,
	                    "violation" => $v,
	                    "money" => $money[$k],
	                    "create_time" => time ()
	                );
	                if (empty ( $so_info )) {
	                    $res = $model->add ( $data );
	                } else {
	                    $res = $model->where ( "id='{$so_info['id']}'" )->save ( $data );
	                }
	            }
	        }
	    }
	   
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
	 * 设置-服务定价-动态定价
	 */
	public function dongdingjia(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
        $code = $_REQUEST['code']; 	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $cityname = isset($_REQUEST['cityname']) ? $_REQUEST['cityname'] : '';
	    $money = $_REQUEST['money'];
	    $shouxu = $_REQUEST['shouxu'];
	    
	    $region_model = M ("region");
	    $city = $region_model->field("id")->where("city='$cityname' and level = 2 and is_dredge = 0")->find();//获取城市id
	    $city_id = $city['id'];
	    
	    /* $sc_model = M ( "services_code" );
	    $sc_list = $sc_model->where ( "services_id='$id' and state = '0'" )->select ();
	    $codes = "'0'";
	    foreach ( $sc_list as $v ) {
	        $codes .= ",'{$v['code']}'";
	    } */
	    /* $violation_model = M ( "violation" );
	    $violation = $violation_model->where ( "code ='$code' and state = 0" )->find (); */
	    
	    $so_model = M ( "services_dyna" );
	    /* foreach ($violation as $v){ */
	       // $money = $violation['money'] + $violation['points'] * $money + $shouxu;
	        $data = array(
	            "fee"=>$shouxu,
	            'create_time'=>time(),
	            'point_fee'=>$money,
	            'code'=>$city_id,
	            'services_id'=>$id
	        );
	        $where = "services_id = '$id' and code = '$city_id'";
	        $sd_info = $so_model->where($where)->find();
	        if (empty ( $sd_info )) {
	            $res = $so_model->add ( $data );
	        } else {
	            $res = $so_model->where ( "id='{$sd_info['id']}'" )->save ( $data );
	        }
	    /* } */
	   /*  
	    $so_model = M ( "services_dyna" );
	        if($provincename != ''){//设置全省
	            $region_model = M ("region");
	            $citys = $region_model->field("id")->where("province='$provincename' and level = 2 and is_dredge = 0")->select();//获取省份下面所有城市id
	            foreach ($citys as $c){
	                $city_id = $c['id'];
	                foreach ($violation as $v){
	                    $money = $v['money'] + $v['points'] * $money + $shouxu;
	                    $data = array("money"=>$money,'create_time'=>time());
	                    $where = "services_id = '$id' and point_fee = '{$v['code']}' and code = '$city_id'";
	                    $res = $so_model->where($where)->save($data);
	                }
	            }
	        }else{//设置某一个城市
	            $region_model = M ("region");
	            $city = $region_model->field("id")->where("city='$cityname' and level = 2 and is_dredge = 0")->find();//获取城市id
	            $city_id = $city['id'];
	            foreach ($violation as $v){
	                $money = $v['money'] + $v['points'] * $money + $shouxu;
	                $data = array("money"=>$money,'create_time'=>time());
	                $where = "services_id = '$id' and point_fee = '{$v['code']}' and code = '$city_id'";
	                $res = $so_model->where($where)->save($data);
	            }
	        } */
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
	 * 设置-服务定价-静态定价
	 */
	public function jingdingjia(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	     
	    $type = $_REQUEST['type'];
	    $provincename = isset($_REQUEST['provincename']) ? $_REQUEST['provincename'] : '';
	    $cityname = isset($_REQUEST['cityname']) ? $_REQUEST['cityname'] : '';
	    //$fen = $_REQUEST['fen'];
	     
	    $sc_model = M ( "services_code" );
	    $sc_list = $sc_model->where ( "services_id='$id' and state = '0'" )->select ();
	    $codes = "'0'";
	    foreach ( $sc_list as $v ) {
	        $codes .= ",'{$v['code']}'";
	    }
	    $violation_model = M ( "violation" );
	    $violation = $violation_model->where ( "code in ($codes) and state = 0" )->order ( "code" )->select ();
	     
	    $so_model = M ( "services_order" );
	        if($provincename != ''){//设置全省
	            $region_model = M ("region");
	            $citys = $region_model->field("id")->where("province='$provincename' and level = 2 and is_dredge = 0")->select();//获取省份下面所有城市id
	            foreach ($citys as $c){
	                $city_id = $c['id'];
	                foreach ($violation as $v){
	                    $data = array("money"=>'200','create_time'=>time());
	                    $where = "services_id = '$id' and violation = '{$v['code']}' and code = '$city_id'";
	                    $res = $so_model->where($where)->save($data);
	                }
	            }
	        }else{//设置某一个城市
	            $region_model = M ("region");
	            $city = $region_model->field("id")->where("city='$cityname' and level = 2 and is_dredge = 0")->find();//获取城市id
	            $city_id = $city['id'];
	            foreach ($violation as $v){
	                $data = array("money"=>'200','create_time'=>time());
	                $where = "services_id = '$id' and violation = '{$v['code']}' and code = '$city_id'";
	                $res = $so_model->where($where)->save($data);
	            }
	        }
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
	 * 获取选择的违章代码
	 */
	public function getCode(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	    
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    
	    $model = M ("violation");
	    $list = $model->field("code,money,points,content")->table("cw_violation")->where("state=0")->select();
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
	 * 获取动态定价的劳务费和手续费
	 */
	public function getMoney(){
	    //验证token
	    $secret = $this->checkAccess($_REQUEST);
	     
	    $phone = $_REQUEST['phone'];
	    $services_model = M ("services");
	    $s_id = $services_model->field("id")->where("phone = '$phone'")->find();
	    $id = $s_id['id'];//获取服务商id
	    $cityname = $_REQUEST['cityname'];
	    $region_model = M ("region");
	    $city = $region_model->field("id")->where("city='$cityname' and level = 2 and is_dredge = 0")->find();//获取城市id
	    $city_id = $city['id'];
	     
	    $model = M ("services_dyna");
	    $dyna = $model->field("point_fee,fee")->table("cw_services_dyna")->where("services_id = $id and code = $city_id")->find();
	    if(isset($dyna)){
	        $msg = array (
	            "code" => 101,
	            "msg" => '操作成功',
	            "info"=>$dyna
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