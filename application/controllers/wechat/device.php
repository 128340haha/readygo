<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Wx_model', 'wx' );
		$this->load->model( 'Device_model', 'device' );
		$this->load->model( 'Lamp_model', 'lamp' );
	}
	
	/**
	 * 设备中心
	 */
	public function index(){
		$uid = $this->session->userdata('uid');
		//第二入口
		$openid = $this->session->userdata( 'openid' );
		$code = $this->input->get('code');
		if( empty( $uid ) && empty( $openid ) && !empty( $code ) ){
			$openid = $this->wx->code_access_token( $code );
			$this->session->set_userdata( 'openid', $openid );
			$uinfo = $this->wx->bind_info( $openid );
			if( !empty( $uinfo['id'] ) ){
				//绑定，直接执行
				$uid = $uinfo['id'];
			}
		}
		if( !empty( $uid ) ){
			//我要显示的设备
			$devices = $this->wx->show_device( $uid );
			//设备耗能信息
			$list = array();
			$totle = array( 'day'=>0, 'week'=>0, 'month'=>0 );
			$num = count( $devices );
			$codes = '';
			$names = array();
			if ( $num > 0 ){
				$nowtime = time(); //;
				foreach ( $devices as $ds ){
					$where = array( 'device_code'=>$ds['code'] );
					$codes .= $ds['code'].',';
					//设备历史数据
					$database = $this->device->change_db( $ds['code'], 'code' );
					$dinfo = $this->device->time_fomate( $nowtime, 1 );	
					$where['time >='] = $dinfo['go_time'];
					$where['time <='] = $dinfo['end_time'];
					//天数据
					$result = $this->device->collect_data( $where, $dinfo['db'], '', 1, $database );
					$token = $this->data_format( $result, $ds['code'] );
					$list['day'][$ds['code']] = $token['list'];
					$totle['day'] += $token['totle'];
					$dinfo = $this->device->time_fomate( $nowtime, 2 );
					$where['time >='] = $dinfo['go_time'];
					$where['time <='] = $dinfo['end_time'];
					//周数据
					$result = $this->device->collect_data( $where, $dinfo['db'], '', 2, $database );
					$token = $this->data_format( $result, $ds['code'], 1 );
					$list['week'][$ds['code']] = $token['list'];
					$totle['week'] += $token['totle'];
					$dinfo = $this->device->time_fomate( $nowtime, 3 );
					$where['time >='] = $dinfo['go_time'];
					$where['time <='] = $dinfo['end_time'];
					//月数据
					$result = $this->device->collect_data( $where, $dinfo['db'], '', 3, $database );
					$token = $this->data_format( $result, $ds['code'] );
					$list['month'][$ds['code']] = $token['list'];
					$totle['month'] += $token['totle'];
					
					//设备名
					$names[$ds['code']] = $ds['name'];
				}
			}
			
			$days = '';
			for( $i = 1; $i <= date('t') ; $i++ ){
				if( empty( $days ) ){
					$days = '"'.$i.'日"';
				}else{
					$days .= ',"'.$i.'日"';
				}
			}
			$data = array( 
				'list'	=>	$list, 
				'num'	=>	$num, 
				'codes'	=>	$codes, 
				'names'	=>	$names,
				'days'	=>	$days,
				'totle'	=>	$totle
			);
			$this->load->view( 'wechat/device_center.html', $data );
		}else{
			//未登录或者登录超时显示演示模板
			$this->load->view( 'wechat/device_example.html' );
		}
	}
	
	/**
	 * 设备列表
	 */
	public function device_all(){
		is_login( $this, 'wechat' );
		$uid = $this->session->userdata('uid');
		//$uid = 21;
		$key = $this->input->get('key');
		$page = $this->input->get('page');
		$succ = $this->input->get('succ');
		$point = array();
		//分类查询判断
		if( !empty( $key ) ){
			$where = $this->device->limit_type( $key );
			$where = array_merge( $where, array( 'user_id'=>$uid ) );
			$per_page = 4;
			$pp = array();
		}else{
			//设备中心重点显示设备
			$point_list = $this->wx->show_device( $uid );
			$num = count($point_list);
			$where = "user_id = ".$uid;
			if( $num > 0 ){
				foreach ( $point_list as $pl ){
					$point[] = $pl['code'];
				}
				$keynote= $where." AND find_in_set( device.code, '".implode( ',', $point)."' ) ";
				$where .= " AND not find_in_set( device.code, '".implode( ',', $point)."' ) ";
				$pp = $this->device->device_list( $keynote, $num );
				$per_page = 3 - $num;
			}else{
				$pp = array();
				$per_page = 3;
			}
			
		}
		//我的设备列表
		$my_device = $this->device->device_list( $where, $per_page );
		$list = array_merge( $pp, $my_device );
		$infomation = $this->device->make_bar( 2 );
		
		$codes = '';
		foreach ( $list as &$lt ){
			//重点标志
			if( in_array( $lt['code'], $point ) ){
				$lt['checked'] = 1;
			}else{
				$lt['checked'] = 0;
			}
			//设备码
			if( empty( $codes ) ){
				$codes = $lt['code'];
			}else{
				$codes .= ','.$lt['code'];
			}
		}
		
		$data = array( 'list'=>$list, 'key'=>$key, 'allpage'=>$infomation['all_page'], 'codes'=>$codes, 'succ'=>$succ ? $succ : 0 );
		$this->load->view( 'wechat/device_all.html', $data );
	}
	
	/**
	 * ajax分页
	 */
	public function morePage(){
		$page = $this->input->post('page');
		$codes = $this->input->post('codes');
		$uid = $this->session->userdata('uid');
		$where = "user_id = ".$uid." AND not find_in_set( device.code, '".$codes."' ) ";
		$my_device = $this->device->device_list( $where, 3, '*', $page );
		$bar = $this->device->make_bar( 2 );
		if( $my_device ){
			echo json_encode( array( 'error'=>0, 'list'=>$my_device, 'bar'=>$bar ) );
		}else{
			echo json_encode( array( 'error'=>1, 'mess'=>'查询失败' ) );
		}
	}
	
	
	/**
	 * 设备绑定
	 */
	public function device_bind(){
		is_login( $this, 'wechat' );
		$data = array( 'error'=>0, 'info'=>'', 'code'=>'', 'salt'=>'', 'name'=>'' );
		$this->load->view( 'wechat/device_binding.html', $data );
	}
	
	/**
	 * 调用二维码扫描接口
	 */
	public function qrcode(){
		$openid = $this->session->userdata( 'openid' );
		//未获取过则去个人中心获取
		if( empty( $openid ) ){
			redirect( 'wechat/ucenter/index' );
		}
		/***********测试数据***************/
		$sign = $this->wx->get_signature();
		if( $sign['errcode'] === '0' ){
			$data = array(
				'appid'		=>	$sign['appid'],
				'timestamp'	=>	$sign['timestamp'],		//签名时间戳
				'noncestr'	=>	$sign['noncestr'],		//签名随机串
				'signature'	=>	$sign['signature']		//签名
			);
			$this->load->view( 'wechat/qrcode.html', $data );
		}else{
			echo '错误码:'.$sign['errcode'].',错误提示:'.$sign['errmsg'];
		}
	}
	
	
	public function act_bind(){
		$code = $this->input->get('code',true);
		$salt = $this->input->get('salt',true);
		$name = $this->input->get('name',true);
		$uid = $this->session->userdata('uid');
		if( empty( $code ) || empty( $salt ) || empty( $name ) ){
			$data = array( 'error'=>1, 'info'=>'设备码,校验码,自定义名不能为空', 'code'=>'', 'salt'=>'', 'name'=>'' );
		}else{
			//验证设备是否已经绑定
			$bind = $this->device->device_info( array( 'code' => $code, 'user_id'=>$uid ), 'user_device' );
			if( !empty( $bind['id'] ) ){
				$data = array( 'error'=>1, 'info'=>'该设备您已经绑定,请勿重复操作', 'code'=>$code, 'salt'=>$salt, 'name'=>$name );
			}elseif( empty( $uid ) ){
				$data = array( 'error'=>1, 'info'=>'用户信息丢失,请回个人中心页重试', 'code'=>$code, 'salt'=>$salt, 'name'=>$name );
			}else{
				//验证是否存在该设备
				$device = $this->device->device_info( array( 'code' => $code ) );
				if( empty( $device['id'] ) ){
					$data = array( 'error'=>1, 'info'=>'找不到该设备,请重新核对设备码', 'code'=>$code, 'salt'=>$salt, 'name'=>$name );
				}else{
					$param = array(
						'code'			=>	$code,
						'salt'			=>	$salt,
						'device_name'	=>	$name,
						'uid'			=>	$uid
					);
					//绑定函数
					$token = $this->wx->bind( $param );
					if( $token == 1 ){
						//绑定成功
						redirect('wechat/device/device_all?succ=1');
					}else{
						//报错
						$data = array( 'error'=>1, 'info'=>$token, 'code'=>$code, 'salt'=>$salt, 'name'=>$name );
					}
				}
			}
		}
		$this->load->view( 'wechat/device_binding.html', $data );
	}
	
	/**
	 * 设备详细页
	 */
	public function device_site(){
		is_login( $this, 'wechat' );
		$uid = $this->session->userdata('uid');
		$code = $this->input->get('code',true);
		if( empty( $code ) ){
			redirect( 'wechat/ucenter/error?error=主要参数丢失' );
		}else{
			//检测权限
			$bind = $this->device->device_info( array( 'code' => $code, 'user_id'=>$uid ), 'user_device' );
			if( !empty( $bind['id'] ) ){
				//处理设备信息
				$res = $this->wx->site_infomation( $code );
				if( $res ){
					$this->load->view( 'wechat/'.$res['model'].'_site.html', $res );
				}else{
					redirect( 'wechat/ucenter/error?error=设备数据信息异常' );
				}
			}else{
				redirect( 'wechat/ucenter/error?error=您没有该设备的查看权限');
			}
		}
	}
	
	
	public function socket(){
		$uid = $this->session->userdata('uid');
		//$uid = 21;
		$codes = $this->input->post('codes');
		$source = $this->input->post('source');
		if( empty( $uid ) || empty( $codes ) ){
			echo 0;
		}else{
			$code = explode( ',', $codes );
			$res = array();
			foreach ( $code as $c ){
				if ( !empty( $c ) ){
					$info = $this->device->device_info( array( 'code'=>$c, 'user_id'=>$uid ), 'user_device' );
					if( !empty( $info['name'] ) ){
						if( $info['pid'] > 0 ){
							$pinfo = $this->device->device_info( array( 'id'=>$info['pid'] ), 'user_device' );
							$sendcode = $this->lamp->smallLightGet( $pinfo['code'], $c );
							$ori_back = $this->lamp->childSendCode( $sendcode, 0x01 );
							$respone = $this->lamp->light_fomat( $ori_back, 0x01 );
						}else{
							$respone = $this->lamp->get_socket( $c, $source );
						}
						$res[$c] = $respone;
						$res[$c]['name'] = $info['name'];
					}
					
				}
			}
			echo json_encode( $res );
		}
	}
	
	
	public function control(){
		$uid = $this->session->userdata('uid');
		$code = $this->input->post('code');
		$value = $this->input->post('val');	
		if( empty( $uid ) || empty( $code ) ){
			echo '参数丢失';
		}else{
			$respone = $this->lamp->set_socket( $code, $value );
			if( $respone[0] == 1 ){
				echo 1;
			}else{
				echo $respone[1];
			}
		}
	}
	
	public function change(){
		$uid = $this->session->userdata('uid');
		$code = $this->input->post('code');
		$type = $this->input->post('type');
		$value = $this->input->post('value');
		if( empty( $uid ) || empty( $code ) || empty( $type ) || empty( $value ) ){
			echo '参数丢失';
		}else{
			$respone = $this->lamp->set_socket( $code, $type, $value );
			if( $respone[0] == 1 ){
				echo 1;
			}else{
				echo $respone[1];
			}
		}
	}
	
	/**
	 * ajax插入
	 */
	public function wshow(){
		$uid = $this->session->userdata('uid');
		$code = $this->input->post('code');
		$value = $this->input->post('val');
		$ascno = $this->input->post('ascno');
		if( empty( $uid ) ){
			echo '登录超时,请重新访问';
			return;
		}
		if( empty( $code ) ){
			echo '设备码丢失,请刷新后再试';
			return;
		}
		if( $value == 1 ){
			//插入
			$param = array( 'user_id'=>$uid, 'code'=>$code, 'ascno'=>$ascno );
			$str = $this->wx->nwshow( $param );
		}else{
			//删除
			$where = array( 'user_id'=>$uid, 'code'=>$code );
			$str = $this->wx->delshow( $where );
		}
		if( $str ){
			echo 1;
		}else{
			echo '操作失败';
		}
	}
	
	
	/**
	 * 数据格式化
	 * @param unknown $list
	 * @return string
	 */
	private function data_format( $list, $code = '', $test = 0 ){
		if( empty( $list ) ){
			return array( 'list'=>'', 'totle'=>0 );
		}
		$res = '';
		$totle = 0;
		$token = strlen( $code ) - 8;
		$type = (int)substr( $code , 0, $token );
		foreach ( $list as $lt ){
			if( $type > 0 && $type < 1000 ){
				$lt['electricity'] = round( $lt['electricity'] / 1000, 3, PHP_ROUND_HALF_UP);		
				$totle += $lt['electricity'];
				if( $res === '' ){
					$res = $lt['electricity'];
				}else{
					$res .= ','.$lt['electricity'];
				}
			}elseif( $type > 3000 && $type < 4000 ){
				//空测没电量
				$totle = 0;
				if( $res === '' ){
					$res = '0';
				}else{
					$res .= ',0';
				}
			}elseif( $type > 4000 && $type < 5000 ){
				$lt['electricity'] = round( $lt['electricity'] / 3200, 3, PHP_ROUND_HALF_UP);
				$totle += $lt['electricity'];
				if( $res === '' ){
					$res = $lt['electricity'];
				}else{
					$res .= ','.$lt['electricity'];
				}	
			}
		}
		return array( 'list'=>$res, 'totle'=>$totle );
	}
}