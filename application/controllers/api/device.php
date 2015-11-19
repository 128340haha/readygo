<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Device_model', 'device' );
		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
		is_login( $this, 'app' );
	}
	
	//我的设备列表
	public function index(){
		$uid = $_SESSION['uid'];
		$pre_page = $this->input->get('pre_page') ? $this->input->get('pre_page',true) : 10;	
		$page = $this->input->get('page') ? $this->input->get('page',true) : 1;
		$priv = $this->input->get('priv',true);
		if( $priv === '0' || !empty( $priv ) ){
			$where = array( 'device_type.priv'=>$priv, 'user_id'=>$uid );
		}else{
			$where = array( 'user_id'=>$uid );
		}
		$list = $this->device->device_list( $where, $pre_page, '',$page );
		$list = data_fomat( $list, 'list', array('id','user_id','pid','add_time','device_id','type_id') );
		$page_bar = $this->device->make_bar( 2 );
		echo json_encode( array( 'res'=>1, 'list'=>$list, 'bar'=>$page_bar ) );
	}
	
	/**
	 * 添加我的设备
	 */
	public function device_bind(){
		//构建数据
		$param = array(
			'code'			=>	$this->input->post('code',true),
			'salt'			=>	$this->input->post('salt',true),
			'device_name'	=>	$this->input->post('device_name',true),
			'pid'			=>	$this->input->post('pid') ? $this->input->post('pid',true) : 0
		);
		//绑定函数
		$token = $this->device->bind( $param, 2 );
		echo return_back( $token );
	}
	
	/**
	 * 修改重新绑定
	 */
	public function re_bind(){
		$uid = $_SESSION['uid'];
		$id = $this->input->post('id',true);
		if( empty( $id ) ){
			echo return_back( array('res'=>0,'info'=>'id不能为空') );
		}else{
			$pid = $this->input->post('pid') ? $this->input->post('pid',true) : 0;
			//绑定函数
			$token = $this->device->re_bind( $id, $pid, $uid );
			if( $token == 1 ){
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				echo return_back( array('res'=>0,'info'=>$token) );
			}
		}
	}
	
	/**
	 * 设定当前设备的主机
	 */
	public function set_host(){
		//操作的设备id
		$code = $this->input->post('code',true);
		//父id
		$pid = $this->input->post('pid',true);
		if( empty( $code ) ){
			echo return_back( array('res'=>0,'info'=>'设备码不能为空') );
		}else{
			//验证我的产品
			$info = $this->device->device_info( array( 'user_id'=>$_SESSION['uid'], 'code'=>$code ), 'user_device' );
			if( $info ){
				//是我的设备
				if( $pid === '0' ){
					//解除绑定
					$param = array( 'pid'=>0 );
				}else{
					//绑定主机
					$pinfo = $this->device->device_info( array( 'user_id'=>$_SESSION['uid'], 'id'=>$pid ), 'user_device' );
					if( $pinfo ){
						//验证两边权限
						$cpass = $this->device->ck_authority( $code, 'child' );
						$ppass = $this->device->ck_authority( $pinfo['code'], 'parent' );
						if( $cpass && $ppass ){
							$param = array( 'pid'=>$pid );
						}elseif( $cpass ){
							echo return_back( array('res'=>0,'info'=>'您选定的主机没有控制权利') );
							return;
						}elseif ( $ppass ){
							echo return_back( array('res'=>0,'info'=>'当前设备为主机设备,无法设定为子设备') );
							return;
						}else{
							echo return_back( array('res'=>0,'info'=>'主机无控制权，子设备拥有主机权限') );
							return;
						}
					}else{
						echo return_back( array('res'=>0,'info'=>'请确认主机设备您已经绑定') );
						return;
					}
				}
				$str = $this->device->user_device_edit( $param, array( 'user_id'=>$_SESSION['uid'], 'code'=>$code ) );
				if( $str ){
					echo return_back( array('res'=>1,'info'=>'') );
				}else{
					echo return_back( array('res'=>0,'info'=>'操作失败') );
				}
			}else{
				echo return_back( array('res'=>0,'info'=>'请确认该设备您已经绑定') );
			}
		}
	}
	
	
	
	/**
	 * 分类列表
	 */
	public function device_type(){
		$types = $this->device->get_type('');
		$list = data_fomat( $types, 'list', array('id','priv') );
		echo json_encode( array( 'res'=>1, 'list'=>$list ) );
	}
	
	
	/**
	 * 更改设备自定义名称
	 */
	public function device_change(){
		$uid = $_SESSION['uid'];
		$id = $this->input->post('id',true);
		//构建数据
		$param = array(
			'name'	=>	$this->input->post('name',true),
		);
		//验证是我的产品
		$info = $this->device->device_info( array( 'id'=>$id ), 'user_device' );
		if( $info['user_id'] == $uid ){
			//修改
			$token = $this->device->changname( $param, $id );
			if( $token == 1 ){
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				echo return_back( array('res'=>0,'info'=>$token) );
			}
		}else{
			echo return_back( array('res'=>0,'info'=>'非法操作,请勿修改别人的产品信息') );
		}
	}
	
	
	/**
	 * 设备能源信息
	 */
	public function device_data(){
		$code = $this->input->get('code', true);
		$model = $this->input->get('model', true);
		$time = $this->input->get('time', true);
		$uid = $_SESSION['uid'];
		if( empty( $time ) || empty( $model ) || empty( $code ) ){
			//没id跳回列表页
			echo return_back( array('res'=>0,'info'=>'设备码,时间,模式一个都不能少') );
		}else{
			//单个产品
			$device_info = $this->device->device_info( array('u.code'=>$code,'u.user_id'=>$uid), 'user_device as u left join device as d on u.code = d.code', 'u.*,d.device_name' );
			if( empty( $device_info['id'] ) ){
				//没有该机器的绑定信息
				header('Content-Type: application/json; charset=utf-8');
				echo return_back( array('res'=>0,'info'=>'没有该设备的绑定信息') );
				exit;
			}
			$where = array( 'device_code'=>$code );
			//判断改code所属库
			$database = $this->device->change_db( $code, 'code' );
			//整理查询时间
			$dinfo = $this->device->time_fomate( $time, $model );
			if( !$dinfo ){
				echo return_back( array('res'=>0,'info'=>'查询日期或者模式异常') );
			}else{
				//组织where
				$where['time >='] = $dinfo['go_time'];
				$where['time <='] = $dinfo['end_time'];
				$list = $this->device->collect_data( $where, $dinfo['db'], '', $model, $database );
				$total = $this->device->collect_tatal( array( $device_info ) );
				$list = data_fomat( $list, 'list', array('id','electricity','time') );
				echo json_encode( array( 'res'=>1, 'list'=>$list, 'total'=>$total ) );
			}
		}
	}
	
	
	/**
	 * 设备能源信息
	 
	public function device_data(){
		$code = $this->input->get('code', true);
		$model = $this->input->get('model', true);
		$time = $this->input->get('time', true);
		$uid = $_SESSION['uid'];
		if( empty( $time ) || empty( $model ) ){
			//没id跳回列表页
			echo return_back( array('res'=>0,'info'=>'查询日期与模式不能为空') );
		}else{
			if( !empty( $code ) ){
				//单个产品
				$device_info = $this->device->device_info( array('code'=>$code,'user_id'=>$uid), 'user_device' );
				if( empty( $device_info['id'] ) ){
					//没有该机器的绑定信息
					header('Content-Type: application/json; charset=utf-8');
					echo return_back( array('res'=>0,'info'=>'没有该设备的绑定信息') );
					exit;
				}
				$where = array( 'device_code'=>$code );
			}
			//整理查询时间
			$dinfo = $this->device->time_fomate( $time, $model );
			if( !$dinfo ){
				echo return_back( array('res'=>0,'info'=>'查询日期或者模式异常') );
			}else{
				//我的产品列表
				$devices = $this->device->device_list( array('user_id'=>$uid) );
				//切换数据库
				$database = $this->device->change_db( $code, 'code' );
				if( !$devices ){
					echo return_back( array('res'=>0,'info'=>'没有找到该用户下的设备') );
				}else{
					$dvs = array();
					foreach ( $devices as $ds ){
						$dvs[] = $ds['code'];
					}
					$devceses = implode( ',', $dvs );
					//组织where
					$where['time >='] = $dinfo['go_time'];
					$where['time <='] = $dinfo['end_time'];
					$list = $this->device->collect_data( $where, $dinfo['db'], $devceses, $model, $database );
					$total = $this->device->collect_tatal( $devices );
					$list = data_fomat( $list, 'list', array('id','electricity','time') );
					echo json_encode( array( 'res'=>1, 'list'=>$list, 'total'=>$total ) );
				}
			}
		}
	
	}
	*/
	
	/**
	 * 我的设备耗能列表
	*/ 
	public function device_data_list(){
		$type_id = $this->input->get('type_id',true);
		$model = $this->input->get('model',true);
		$time = $this->input->get('time',true);
		$pre_page = $this->input->get('pre_page') ? $this->input->get('pre_page',true) : 10;
		$page = $this->input->get('page') ? $this->input->get('page',true) : 1;
		$uid = $_SESSION['uid'];
		if( empty( $time ) || empty( $model ) || empty( $type_id ) ){
			//没id跳回列表页
			echo return_back( array('res'=>0,'info'=>'设备分类,时间,模式一个都不能少') );
		}else{
			//整理查询时间
			$dinfo = $this->device->time_fomate( $time, $model );
			if( !$dinfo ){
				echo return_back( array('res'=>0,'info'=>'查询日期或者模式异常') );
			}else{
				//检查分类的正确性
				$tid = $this->device->device_info( array('id'=>$type_id), 'device_type', 'id' );
				if( !empty( $tid['id'] ) ){
					//我的产品列表
					$devices = $this->device->device_list( array('user_id'=>$uid,'device.type_id'=>$type_id) , $pre_page, '*', $page );
					//切换数据库
					$database = $this->device->change_db( $type_id, 'type' );
					$page_bar = $this->device->make_bar( 2 );
					if( !$devices ){
						echo return_back( array('res'=>0,'info'=>'没有找到该用户下的设备') );
					}else{
						$dvs = array();
						foreach ( $devices as $ds ){
							$dvs[] = $ds['code'];
						}
						$devceses = implode( ',', $dvs );
						//组织where
						$where['time >='] = $dinfo['go_time'];
						$where['time <='] = $dinfo['end_time'];
						$list = $this->device->collect_data( $where, $dinfo['db'], $devceses, $model, $database );
						$total = $this->device->collect_tatal( $devices );
						$list = data_fomat( $list, 'list', array('id','electricity','time') );
						echo json_encode( array( 'res'=>1, 'list'=>$list, 'total'=>$total, 'bar'=>$page_bar ) );
					}
				}else{
					echo return_back( array('res'=>0,'info'=>'设备分类type_id无效，请勿非法操作') );
				}
			}
		}
	}
	
	
	/**
	 * ajax判断是否绑定
	 */
	public function device_check(){
		$device = $this->device->device_info( array( 'code' => $this->input->post('code') ) );
		$uid = $_SESSION['uid'];
		if( empty( $device['id'] ) ){
			echo return_back( array('res'=>0,'info'=>'找不到该设备信息') );
		}else{
			$bind = $this->device->device_info( array( 'code' => $device['code'], 'user_id' => $uid ), 'user_device' );
			if( empty( $bind['id'] ) ){
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				echo return_back( array('res'=>0,'info'=>'您已经添加该设备') );
			}
		}
	}
	
	/**
	 *  我要取消绑定
	 */
	public function cancel_bind(){
		$uid = $_SESSION['uid'];
		$id = $this->input->get('id',true);
		$code = $this->input->get('code',true);
		if( !empty( $id ) ){
			$res = $this->device->cancel_ud( array( 'id'=>$id, 'user_id'=>$uid ) );
		}elseif( !empty( $code ) ){
			$res = $this->device->cancel_ud( array( 'code'=>$code, 'user_id'=>$uid ) );
		}else{
			echo return_back( array('res'=>0,'info'=>'id不能为空') );	
			return;
		}
		if( $res ){
			echo return_back( array('res'=>1,'info'=>'') );
		}else{
			echo return_back( array('res'=>0,'info'=>'没有找到该设备绑定信息或者取消绑定失败') );
		}
	}
	
}