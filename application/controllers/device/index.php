<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Device_model', 'device' );
		session_start();
		is_login( $this, 'user' );
	}
	
	//我的设备列表
	public function index(){
		$uid = $_SESSION['uid'];
		$key = $this->input->get('key');
		if( !empty( $key ) ){
			$where = $this->device->limit_type( $key );
			$where = array_merge( $where, array( 'user_id'=>$uid ) );
		}else{
			$where = array( 'user_id'=>$uid );
		}
		$list = $this->device->device_list( $where, 10 );
		$data = array( 'list'=>$list, 'key'=>$key );
		$main = $this->load->view( 'device/index.html', $data, true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	/**
	 * 设备录入
	 */
	public function device_input(){
		$this->load->helper( array('form') );
		$main = $this->load->view( 'device/bind.html', '', true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	/**
	 * 设备绑定
	 */
	public function device_bind(){
		$code = $this->input->post('code',true);
		$salt = $this->input->post('salt',true);
		$name = $this->input->post('device_name',true);
		if( empty( $code ) || empty( $salt ) || empty( $name ) ){
			gotourl( '重要参数丢失', 'back', 0, 'forum' );
		}
		//构建数据
		$param = array(
			'code'			=>	$code,
			'salt'			=>	$salt,
			'device_name'	=>	$name,
		);
		//绑定函数
		$token = $this->device->bind( $param );
		if( $token == 1 ){
			//绑定成功
			gotourl( '绑定成功', base_url('user/ucenter/index'), 0, 'forum' );
		}else{
			//报错
			gotourl( $token, 'back', 1, 'forum' );
		}
	}
	
	
	public function device_data(){
		$code = $this->input->get('code',true);
		$date = $this->input->get('date',true);
		$model = $this->input->get('model') ? $this->input->get('model',true) : 1;
		//查询单条
		$time = $date ? strtotime( $date ) : time();
	//	$time = 1420790400;
		$device_info = $this->device->device_info( array('u.code'=>$code,'u.user_id'=>$_SESSION['uid']), 'user_device as u left join device as d on u.code = d.code', 'u.*,d.device_name' );
		if( empty( $device_info['code'] ) ){
			gotourl( '没有该设备的绑定信息', 'back', 1, 'forum' );
		}

		//月份日期数
		$t = date('t',$time);
		$database = $this->device->change_db( $code, 'code' );
		$dinfo = $this->device->time_fomate( $time, $model );
		if( !$dinfo ){
			gotourl( '查询日期或者模式异常', 'back', 1, 'forum' );
		}else{
			//组织where
			$where = array(
				'device_code'	=>	$device_info['code'],
				'time >='		=>	$dinfo['go_time'],
				'time <='		=>	$dinfo['end_time']
			);
			$list = $this->device->collect_data( $where, $dinfo['db'], '', $model, $database );
			//参数构建
			$param = array(
				'list'	=>	$list,
				'code'	=>	$code,
				'date'	=>	$date ? $date : date('Y-m-d'),
				'model'	=>	$model,	
				't'		=>	$t
			);
			$data = array_merge( $param, $device_info );
			$main = $this->load->view( 'device/device_data.html', $data, true );
			$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
		}	
	}
	
	/**
	 * 需要重新调整
	 */
	public function device_data_list(){
		$type_id = $this->input->get('type_id',true);
		$model = $this->input->get('model',true);
		$time = $this->input->get('time',true);
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
					$devices = $this->device->device_list( array('user_id'=>$uid,'device.type_id'=>$type_id) );
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
	 * 取消绑定
	 */
	public function cancel(){
		$code = $this->input->post('code',true);
		if( !empty( $code ) ){
			$res = $this->device->cancel_ud( array( 'code'=>$code, 'user_id'=>$_SESSION['uid'] ) );
			if( $res ){
				echo 'true';
			}else{
				echo 'false';
			}
		}else{
			echo 'false';
		}
	}
	
	
	public function device_edit(){
		$code = $this->input->get('code',true);
		$uid = $_SESSION['uid'];
		if( empty( $code ) ){
			gotourl( '重要参数丢失', 'back', 1, 'forum' );
		}
		$info = $this->device->device_info( array( 'code'=>$code, 'user_id'=>$uid ), 'user_device' );
		$main = $this->load->view( 'device/edit.html', array( 'info'=>$info ), true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	
	public function edit_action(){
		$id = $this->input->post('id',true);
		$device_name = $this->input->post('device_name');
		$code = $this->input->post('code');
		if( empty( $id ) || empty( $device_name ) || empty( $code ) ){
			gotourl( '重要参数丢失', 'back', 1, 'forum' );
		}
		$str = $this->device->changname( array( 'name'=>$device_name ), $id );
		if( $str ){
			gotourl( '修改成功', base_url('device/index/device_edit').'?code='.$code, 0, 'forum' );
		}else{
			gotourl( '修改失败', 'back', 1, 'forum' );
		}
	}
	
	
	/**
	 * ajax判断是否绑定
	 */
	public function device_check(){
		$device = $this->device->device_info( array( 'code' => $this->input->post('code') ) );
		if( empty( $device['id'] ) ){
			echo 'false';
			exit;
		}
		$bind = $this->device->device_info( array( 'did' => $device['id'] ), 'user_device' );
		if( empty( $bind['id'] ) ){
			echo 'true';
		}else{
			echo 'false';
		}
		exit;
	}
	
}