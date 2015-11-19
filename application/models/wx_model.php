<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wx_model extends CI_Model{
	private $url = '';
	private $overtime = '';
	private $appid = '';
	private $appsecret = '';
	private $token_access = '';
	public function __construct() {	
		parent::__construct();
		$this->load->model( 'User_model', 'user' );
		$this->load->model( 'Device_model', 'device' );
		define("TOKEN", "");
	}
	
	/**
	 * 确认皮肤
	 * @param string $openid
	 * @return boolean|unknown
	 */
	public function skin( $openid = '' ){
		if( empty( $openid ) ){
			return false;
		}
		$this->db->select( 'skin' );
		$this->db->from( 'wechat_user' );
		$this->db->where( array( 'openid'=>$openid ) );
		$query = $this->db->get();
		$info = $query->row_array();
		if( empty( $info['skin'] ) ){
			return false;
		}else{
			return $info['skin'];
		}
	}
	
	
	/**
	 * 写入
	 * @param string $param
	 * @return boolean|unknown
	 */
	public function wechat_insert( $param = '' ){
		if( empty( $param ) ){
			return false;
		}
		$str = $this->db->insert( 'wechat_user', $param );
		return $str;
	}
	
	/**
	 * 修改
	 * @param string $param
	 * @param string $where
	 * @return boolean
	 */
	public function wechat_edit( $param = '', $where = '' ){
		if( empty( $param ) || empty( $where ) ){
			return false;
		}
		return $this->db->update( 'wechat_user', $param, $where );
	}
	
	/**
	 * 用户绑定
	 * @param string $username
	 * @param string $openid
	 * @return boolean
	 */
	public function user_bind( $username = '', $openid = '' ){
		if( empty( $username ) || empty( $openid ) ){
			return false;
		}
		$uinfo = $this->user->user_record( array( 'username'=>$username ), 1 );	
		$str = $this->wechat_edit( array( 'user_id'=>$uinfo['id'] ), array( 'openid'=>$openid ) );
		return $str;
	}
	
	
	
	/**
	 * 查询微信绑定信息
	 * @param string $openid
	 * @return boolean
	 */
	public function wechat_info( $where = '' ){
		if( empty( $where ) ){
			return false;
		}
		$this->db->from( 'wechat_user' );
		$this->db->where( $where );
		$query = $this->db->get();
		return $query->row_array();
	}
	
	
	/**
	 * 判断绑定状态
	 * @param string $openid
	 * @return boolean
	 */
	public function bind_info( $openid = '' ){
		if( empty( $openid ) ){
			return false;
		}
		$where = array( 'openid'=>$openid );
		//查询微信已存信息
		$info = $this->wechat_info( $where );
		if( empty( $info ) ){
			//无微信信息,需要把当前用户数据写入
			$param = array( 'openid'=>$openid, 'last_login'=>time(), 'user_id'=>0 );
			$this->wechat_insert( $param );
			return false;
		}else{
			//有微信信息，查询是否存在绑定信息
			if( !empty( $info['user_id'] ) ){
				//已经绑定，返回用户信息
				$res = $this->user->user_record( array( 'id'=>$info['user_id'] ) );
				$head = $this->user->user_face( $info['user_id'] );
				$res['headimgurl'] = $head['src'];
				$res['path'] = $head['path'];
				return $res;
			}else{
				//未绑定
				return false;
			}
		}
	}
	
	
	
	/**
	 * 获取微信通行证
	 * @return multitype:string |multitype:NULL |multitype:number string
	 */
	public function token_access(){
		$token = $this->get_token( array( 'id'=>1 ) );
		if( empty( $token ) || $token['overdue'] < time() ){
			//过期或者没有数据，重新获取
			//pass
		}else{
			//直接使用
			$this->token_access = $token['token'];
			return array( 'errcode'=>'0' );
		}
		$target = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
		$res = $this->curl_get( $target );
		if( $res && empty( $res->errcode ) ){
			//通过
			$this->token_access = $res->access_token;
			$this->update_token( $res->access_token, array( 'id'=>1 ) );
			return array( 'errcode'=>'0' );
		}elseif( !empty( $res->errcode ) ){
			return array( 'errcode'=>$res->errcode, 'errmsg'=>$res->errmsg );
		}else{
			return array( 'errcode'=>40000, 'errmsg'=>'返回数据失败' );
		}
	}
	
	/**
	 * 获取当前存储的token
	 * @return unknown
	 */
	public function get_token( $where = '' ){
		$this->db->where( $where );
		$query = $this->db->get('token_access');
		$row = $query->row_array();
		return $row;
	}
	
	/**
	 * 更新token库
	 * @param unknown $token
	 */
	public function update_token( $token, $where = '' ){
		if( empty( $token ) || empty( $where ) ){
			return false;
		}
		//7200 是腾讯2小时时限
		$param = array( 'token'=>$token, 'overdue'=>time() + 7200 );
		$str = $this->db->update( 'token_access', $param, $where );
		if( $str ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取当前访问人员的open
	 * @param string $code
	 * @return boolean
	 */
	public function code_access_token( $code = '' ){
		if( empty( $code ) ){
			return false;
		}else{
			$target = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
			$res = $this->curl_get( $target );
			if( empty( $res->openid ) ){
				return false;
			}else{
				return $res->openid;
			}
		}
	}
	
	
	/**
	 * 发起微信服务端访问
	 * @param string $target
	 * @return boolean|mixed
	 */
	private function curl_get( $target = '' ){
		if( empty( $target ) ){
			return false;
		}
		// 初始化一个 cURL 对象
		$curl = curl_init();
		// 设置你需要抓取的URL
		curl_setopt($curl, CURLOPT_URL, $target);
		// 设置header
		curl_setopt($curl, CURLOPT_HEADER, 0);
		// 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);	
		//https请求 不验证证书和hosts
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		//https请求需要
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		// 运行cURL，请求网页
		$data = curl_exec($curl);
		// 关闭URL请求
		curl_close($curl);
		if( $data ){
			//获取数据成功
			$res = json_decode( $data );
			return $res;
		}else{
			return false;
		}
	}
	
	/**
	 * 接收文本
	 * @param unknown $obj
	 * @return string
	 */
	public function getText( $obj ){
		if( empty( $obj ) ){
			return '';
		}
		$this->writelog( $obj->Content, 1 );
		$this->writelog( $obj->FromUserName, 2 );
		$this->writelog( $obj->ToUserName, 3 );
	}
	
	
	/**
	 * 接收到事件
	 * @param unknown $obj
	 * @return string
	 */
	public function eventModel( $obj ){
		if( empty( $obj ) ){
			return '';
		}
		$this->writelog( $obj->EventKey );
		switch ( $obj->EventKey ){
			case 'ReadyToWork':		//环境质量   待定~
				$this->responseText( $obj );
				break;
		}
	}
	
	/**
	 * 返回信息
	 * @param unknown $obj
	 */
	public function responseText( $obj ){
		$textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[%s]]></MsgType>
			<Content><![CDATA[%s]]></Content>
		</xml>";
		$msgType = "text";
		$contentStr = "开发中..敬请期待~";
		$resultStr = sprintf($textTpl, $obj->FromUserName, $obj->ToUserName, time(), $msgType, $contentStr);
		echo $resultStr;
	}
	
	
/*****************下面的测试与认证函数*******************/	
	public function valid()
    {
    	if( !empty( $_GET["echostr"] ) ){
        	$echoStr = $_GET["echostr"];
    	}
        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }
    
    public function fileurl( $url = '' ){
    	$this->url = $url;
    }

    public function writelog($str, $model = 1 ){
    	$open=fopen($this->url."/log".$model.".txt","a" );
    	fwrite($open,$str);
    	fclose($open);
    }
    
    public function responseMsg(){
    	//get post data, May be due to the different environments
    	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    
    	//extract post data
    	if (!empty($postStr)){
    		/* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
    		 the best way is to check the validity of xml by yourself */
    		libxml_disable_entity_loader(true);
    		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    		$fromUsername = $postObj->FromUserName;
    		$toUsername = $postObj->ToUserName;
    		$keyword = trim($postObj->Content);
    		$time = time();
    		$textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
						</xml>";
    		if(!empty( $keyword )){
    			$msgType = "text";
    			$contentStr = "应该能通用的吧~！";
    			$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
    			echo $resultStr;
    		}else{
    			echo "Input something...";
    		}
    	}else {
    		echo "";
    		exit;
    	}
    }
    
    /*
     * 响应易信平台推送消息
    * 可以根据消息类型处理不同的消息
    * 除了验证开发者、创建推广二维码等，其余基本需要该函数来统一调度；
    */
    public function listen(){
    	// 接收易信post方式推送来的消息；
    	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
    	//  写入日志；
    	if (!empty($postStr)){
    		// 将推送的XML消息解析为对象；
    		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    		// MsgType类型为：text（文本消息）image（图片消息）audio（语音消息）video（视频消息）
    		// event location（地理位置消息）
    		// event（事件消息）：subscribe（订阅） unsubscribe（取消订阅）YIXINSCAN（扫描推广二维码）CLICK（自定义菜单点击）
    		$msgType = trim($postObj->MsgType); // 消息类型；文本、菜单点击等
    		// 可以直接调用 handleMessage()函数，switch一下是为了清晰明了；
    		switch($msgType){
    			case 'text': // 文本消息类型；
    				$this->getText($postObj);
    				break;
    			case 'event': // 事件消息类型 包括关注、取消关注、自定义菜单点击等；
    				$this->eventModel($postObj);
    				break;
    			case 'image': // 图片消息类型；
    				$this->handleMessage($postObj);
    				break;
    			case 'location': // 地理位置信息（用户主动）；
    				$this->handleMessage($postObj);
    				break;
    			default:
    				$resultStr = "未处理事件: " . $msgType;
    			//	$this->log($resultStr);
    				break;
    		}
    	}else{
    		echo ''; // 收到的推送内容为空，直接响应空值给微信平台；
    	}
    }
    
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        if( !empty( $_GET["signature"] ) ){
        	$signature = $_GET["signature"];
        }else{
        	$signature = '';
        }
        if( !empty( $_GET["timestamp"] ) ){
        	$timestamp = $_GET["timestamp"];
        }else{
        	$timestamp = '';
        }
        if( !empty( $_GET["nonce"] ) ){
        	$nonce = $_GET["nonce"];
        }else{
        	$nonce = '';
        }
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
	//	$this->writelog( $tmpStr, 1, $url );
	//	$this->writelog( $signature, 2, $url );
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	
/*****************************************************
 ****************************************************/	
	public function getUserInfo( $openid ){
		$access = $this->token_access();
		if( $access['errcode'] === '0' ){
			//获取权限成功~！
			//获取当前访问用户的OPENID	
			$target ="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->token_access."&openid=".$openid."&lang=zh_CN";
			$info = $this->curl_get( $target );
			return $info;
		}else{
			//权限获取失败
			return $access;
		}
	}
	

	
	public function get_signature(){
		//查看jsapi_ticket状态
		$token = $this->get_token( array( 'id'=>2 ) );
		if( empty( $token ) || $token['overdue'] < time() ){
			//验证token是否过期
			$access = $this->token_access();
			if( $access['errcode'] === '0' ){
				//token没问题，获取jsapi_ticket
				$target = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->token_access."&type=jsapi";
				$info = $this->curl_get( $target );
				if( $info && empty( $info->errcode ) ){
					//获取成功并更新库
					$this->update_token( $info->ticket, array( 'id'=>2 ) );
					$jsapi_ticket = $info->ticket;
				}else{
					return array( 'errcode'=>$res->errcode, 'errmsg'=>$res->errmsg );
				}
			}
		}else{
			//直接使用
			$jsapi_ticket = $token['token'];
		}
		$param = array(
			'jsapi_ticket'	=>	$jsapi_ticket,					
			'noncestr'		=>	random_string( 'alnum', 16 ),	
			'timestamp'		=>	time(),
			'url'			=>	current_url()
		);
		$sign = '';
		foreach ( $param as $k => $v ){
			if( empty( $sign ) ){
				$sign = $k.'='.$v;
			}else{
				$sign .= '&'.$k.'='.$v;
			}
		}
		$param['signature'] = sha1( $sign );
		$param['appid'] = $this->appid;
		$param['errcode'] = '0';
		return $param;
	}
	
	
	public function keyreg( $openid = '' ){
		if( empty( $openid ) ){
			return false;
		}
		$this->load->library( 'password' );
		$salt = random_string('alpha',6);
		$username = $this->used_name();
		//加密默认密码 8个8
		$password = $this->password->makePass( 88888888, $salt );
		$data_main = array(
			'username' 	=> $username,
			'password' 	=> $password,
			'salt' 		=> $salt,	//注册随机码
			'reg_time'	=> time(),
			'reg_ip'	=> $this->input->ip_address(),
		);
		$this->user->user_reg( $data_main );
		$uid = $this->db->insert_id();
		$data_info = array('id'=>$uid);
		//插入用户信息表
		$this->user->user_reg($data_info, 'user_info');
		//绑定信息
		$bind_info = $this->wechat_info( array( 'openid'=>$openid ) );
		if( $bind_info ){
			$str = $this->wechat_edit( array( 'user_id'=>$uid ), array( 'openid'=>$openid ) );
		}else{
			$param = array('openid'=>$openid,'user_id'=>$uid);
			$str = $this->wechat_insert( $param );
		}
		return $str;
	}
	
	/**
	 * 获取一个可用的随机id
	 * @return string
	 */
	private function used_name(){
		$username = strtolower( random_string('alpha',6) ).random_string( 'numeric', 6 );
		$row = $this->user->user_record( array( 'username' => $username ), 1 );
		//是否重名
		if( empty( $row['id'] ) ){
			return $username;
		}else{
			//重名递归
			return $this->used_name();
		}
	}
	
/***********************下面是设备相关********************************/	
	public function show_device( $uid = '', $limit = 2 ){
		$this->db->select( 'w.*,ud.name' );
		$this->db->from( 'wechat_show as w' );
		$this->db->where( array( 'w.user_id'=>$uid ) );
		$this->db->join('user_device as ud', 'ud.user_id = w.user_id AND ud.code = w.code');
		$this->db->limit( $limit );
		$this->db->order_by( 'ascno,id desc' );
		$query = $this->db->get();
		$shows = $query->result_array();
		return $shows;
	}
	
	
	/**
	 * 插入显示
	 * @param unknown $param
	 * @return boolean
	 */
	public function nwshow( $param ){
		if( empty( $param ) ){
			return false;
		}
		return $this->db->insert( 'wechat_show', $param );
	}
	
	/**
	 * 删除
	 * @param unknown $where
	 * @return boolean
	 */
	public function delshow( $where ){
		if( empty( $where ) ){
			return false;
		}
		return $this->db->delete( 'wechat_show', $where );
	}
	
	
	
	public function bind( $param = array() ){
		//验证设备码校验码是否合格
		$info = $this->device->device_info( array( 'code'=>$param['code'], 'salt'=>strtolower( $param['salt'] ) ) );
		if( !empty( $info['id'] ) ){
			$data = array(
				'name'		=>	$param['device_name'],
				'user_id'	=>	$param['uid'],
				'code'		=>	$param['code'],
				//	'img'		=>
				'add_time'	=>	time(),
				'pid'		=>	0
			);
			$res = $this->device->user_device_bind( $data, 'user_device' );
			if( $res ){
				return 1;
			}else{
				return '设备添加失败,请稍后再试';
			}
		}else{
			return '校验码验证失败';
		}
	}
	
	/**
	 * 数据信息详情页
	 * @param string $code
	 * @return boolean|multitype:string multitype:number
	 */
	public function site_infomation( $code = '' ){
		if( empty( $code ) ){
			return false;
		}
		$bench = strlen( $code ) - 8;
		$token = substr( $code, 0, $bench );
		$type = (int)$token;
		$nowtime = time();
		$result = array();
		$where = array( 'device_code'=>$code );
		$list = array();
		$days = '';
		$totle = array( 'day'=>0, 'week'=>0, 'month'=>0 );
		if( $type > 0 && $type < 1000  ){
			$result = $this->elect_data( $where, $token );
			foreach ( $result as $key => $rt ){
				if( $rt ){
					foreach ( $rt as $v ){
						$v['electricity'] = round($v['electricity'] / 1000, 3, PHP_ROUND_HALF_UP);
						if( $token == '0001' ){
							$totle[$key] += $v['electricity'];
							$list[$key][] = $v['electricity'];
						}elseif( $token == '0002' ){
							$v['electricity1'] = round($v['electricity1'] / 1000, 3, PHP_ROUND_HALF_UP);
							$v['electricity2'] = round($v['electricity2'] / 1000, 3, PHP_ROUND_HALF_UP);
							$v['electricity3'] = round($v['electricity3'] / 1000, 3, PHP_ROUND_HALF_UP);
							$totle[$key] += $v['electricity'];
							$list[$key]['p1'][] = $v['electricity1'];
							$list[$key]['p2'][] = $v['electricity2'];
							$list[$key]['p3'][] = $v['electricity3'];
						}
					}
					if( $token == '0001' ){
						$list[$key] = implode( ',', $list[$key]);
					}elseif ( $token == '0002' ){
						$list[$key]['p1'] = implode( ',', $list[$key]['p1']);
						$list[$key]['p2'] = implode( ',', $list[$key]['p2']);
						$list[$key]['p3'] = implode( ',', $list[$key]['p3']);
					}
				}else{
					//查不到数据
					if( $token == '0001' ){
						$list[$key] = array();
					}elseif ( $token == '0002' ){
						$list[$key]['p1'] = array();
						$list[$key]['p2'] = array();
						$list[$key]['p3'] = array();
					}
				}
			}
			//插板	
			$model = 'socket';
		}elseif( $type > 3000 && $type < 4000 ){
			//空测
			$result = $this->elect_data( $where, $token );
			foreach ( $result as $key => $rt ){
				if( $rt ){
					foreach ( $rt as $v ){
						$v['hcho'] = $v['hcho'] / 10;
						$list[$key]['temp'][] = $v['temp'];
						$list[$key]['rh'][] = $v['rh'];
						$list[$key]['hcho'][] = $v['hcho'];
						$list[$key]['pm'][] = $v['pm'];
					}
					$list[$key]['temp'] = implode( ',', $list[$key]['temp'] );
					$list[$key]['rh'] = implode( ',', $list[$key]['rh'] );
					$list[$key]['hcho'] = implode( ',', $list[$key]['hcho'] );
					$list[$key]['pm'] = implode( ',', $list[$key]['pm'] );
				}else{
					$list[$key]['temp'] = array();
					$list[$key]['rh'] = array();
					$list[$key]['hcho'] = array();
					$list[$key]['pm'] = array();
				}
			}
			$model = 'airck';
		}elseif( $type > 4000 && $type < 5000 ){
			//获取数据
			$result = $this->elect_data( $where, $token );
			foreach ( $result as $key => $rt ){
				foreach ( $rt as $v ){
					$v['electricity'] = round($v['electricity'] / 3200, 3, PHP_ROUND_HALF_UP);
					$totle[$key] += $v['electricity'];
					$list[$key][] = $v['electricity'];
				}
				if( !empty( $list[$key] ) ){
					$list[$key] = implode( ',', $list[$key]);
				}
			}
			//云灯
			$model = 'light';
		}elseif ( $type > 6000 && $type < 7000 ){
			//自行车
		}
		//本月天数
		for( $i = 1; $i <= date('t') ; $i++ ){
			if( empty( $days ) ){
				$days = '"'.$i.'日"';
			}else{
				$days .= ',"'.$i.'日"';
			}
		}
		return array( 'totle'=>$totle, 'list'=>json_encode( $list ), 'model'=>$model, 'code'=>$code, 'days'=>$days );
	}
	
	
	private function elect_data( $where, $token ){
		$nowtime = time();
		//获取数据
		$database = $this->device->change_db( $token, 'type' );
		$dinfo = $this->device->time_fomate( $nowtime, 1 );
		$where['time >='] = $dinfo['go_time'];
		$where['time <='] = $dinfo['end_time'];
		$result['day'] = $this->device->collect_data( $where, $dinfo['db'], '', 1, $database );
		$dinfo = $this->device->time_fomate( $nowtime, 2 );
		$where['time >='] = $dinfo['go_time'];
		$where['time <='] = $dinfo['end_time'];
		$result['week'] = $this->device->collect_data( $where, $dinfo['db'], '', 2, $database );
		$dinfo = $this->device->time_fomate( $nowtime, 3 );
		$where['time >='] = $dinfo['go_time'];
		$where['time <='] = $dinfo['end_time'];
		$result['month'] = $this->device->collect_data( $where, $dinfo['db'], '', 3, $database );
		return $result;
	}
	
	
}
