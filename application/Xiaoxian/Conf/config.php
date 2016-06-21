<?php
/* require_once ("alipay/alipay.config.php");
define ( "partner", $alipay_config['partner'] );
define ( "seller_id", $alipay_config['partner'] );
define ( "private_key_path", $alipay_config['private_key_path'] );
define ( "ali_public_key_path", $alipay_config['ali_public_key_path'] );
define ( "sign_type", $alipay_config['sign_type'] );
define ( "input_charset", $alipay_config['input_charset'] );
define ( "cacert", $alipay_config['cacert'] );
define ( "transport", $alipay_config['transport'] ); */
//define ( "TOKEN", "weixin" );
//define ( "APPID", "wx6ccce8752525b8cc" );
//define ( "APPSECRET", "7d87a2a84199b99cf6c5765f657fcac4" );
/* define ( "MCHID", "1259143901" ); // 商户id
define ( "KEY", "B7sFIXk9v6pEWacUruuObzUjOuGJu0Vb" ); // 微信支付秘钥
define ( "MUBAN1", "8mK1zGUm62k1Gnmd8dGXbtbIPeVJ_g3Mrs-TmbheJf8" ); // 违章扫描目标消息id
define ( "MUBAN2", "LV-tiD3RdHBuExmm1WhYab7sZIdrsiMt_fINROi_m5Q" ); // 违章扫描目标新消息id
define ( "MUBAN3", "N1qeUloyDmg-LerLJyg0--PHeVqtuiIHeKOEyoKWN1Y" ); // 订单状态更新消息id
define ( "URL1", "http://" . $_SERVER ['SERVER_NAME'] . "/chewu/index.php?g=weixin&m=sub&a=index" ); // 违章订阅地址
define ( "URL2", "http://" . $_SERVER ['SERVER_NAME'] . "/chewu/index.php?g=weixin&m=order&a=index" ); // 我的订单地址
define ( "URL3", "http://" . $_SERVER ['SERVER_NAME'] . "/chewu/index.php?g=weixin&m=scan&a=index" ); // 违章扫描地址
define ( "APIURL", "http://" . $_SERVER ['SERVER_NAME'] . "/chewu/" ); // 根目录
define ( "NUMS1", "10" ); // 筛选服务商数量(第一条件：定价)
define ( "NUMS2", "5" ); // 筛选服务商数量(第二条件：评分)
define ( "app_id", "928" ); // 车首页appid
define ( "app_key", "edd9312406d6ed867262f0d50a49029c" ); // 车首页appkey
define ( "scan_time", "10" ); // 扫描间隔时间
define ( "merKey", "9a5eae4723e87befc85459d5b0c585dc" ); // 爱车坊merKey
define ( "merCode", "2500000002" ); // 爱车坊merCode
define ( "acfapi", "120.26.57.239" ); // 爱车坊端口
define ( 'csyapi', "cheshouye.com" ); // 车首页端口
define ( 'bdkey', "53cb9129fd47522b71620094c6f06020" ); // 车首页端口
define ( "timing_count1", "20" ); // 每周定时查询数量
define ( "timing_count2", "4" ); // 每月定时查询数量 */

/* define ( "number1", "10" ); // 筛选服务商数量(第一条件：定价)
define ( "number2", "5" ); // 筛选服务商数量(第二条件：评分)

define ( "tuisong1", 3600 * 48 ); // 推送计时
define ( "tuisong2", 3600 * 168 ); // 办理计时

define ( "versions", 'v1.3' ); // 版本号 */

/* 订单状态更改, 处理中/处理完成/已退款状态更改没有对应的推送消息，文字提示信息 */
/* define ( "first_key", '尊敬的用户，您的违章代缴办理结果如下' );
define ( "last_key", '感谢您使用我们的服务，如有疑问可直接回复文字联系客服。' );
define ( "status1", '处理中' );
define ( "status2", '处理完成' );
define ( "status3", '已退款' ); */


//环信参数
/* define ('client_id', 'YXA64C51wKM6EeWpEYsWb4PIOA');
define ('client_secret', 'YXA6_1pOWamYMMEfwKsm5TCdHnQiw-w');
define ('url', 'https://a1.easemob.com/xiaoxianlink/xiaoxianchewu/');


//短信验证码过期时间
define ( "DUANXIN_TIME", 5 * 60 );
//容联云通信参数
define('AccountSid', 'aaf98f894b353559014b38f04fd601f1');
define('AccountToken', 'c9aa7a85305a40dea2d0f8c835b7a489');
define('AppId', '8a48b55151a4acb50151a61212cd054b');
//沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
//生产环境（用户应用上线使用）：app.cloopen.com
define('ServerIP', 'sandboxapp.cloopen.com');
define('ServerPort', '8883');
define('SoftVersion', '2013-12-26');
//短信模板id
define ('tempId', '1');
//secret 调试模式开关，允许接口secret参数传 'debug'
define('SECRET_DEBUG_ENABLE',true);
//secret 有效天数 15天
define('VALID_DAYS',30);
//secret 是否支持一个账户只能登陆一个设备 ture:支持
define('ONE_DEVICE_ENABLE', true);
//secret 将在过期时间之前的1天时更新
define('UPDATE_TIME',1*60*60*24);

//定义极光推送参数
/* define("appkeys", "");
define("masterSecret", "");
define("platform", "android,ios"); */

//个推参数
/* define('HOST','http://sdk.open.api.igexin.com/apiex.htm');

define('APPKEY','O0ESNemOMR6zVsVXQWKsK2');
define('appid','aeLGotClWN78uXFkcuCBx6');
define('MASTERSECRET','2Wrqc6W4MC8gT36efVY1B3') */;
//define('CID','');






