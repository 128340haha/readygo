<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		is_login( $this, 'admin' );
		$this->load->model( 'User_model', 'user' );
		$this->load->helper('html');
		if( $this->input->is_ajax_request() ){
			//ajax访问不加载页头和页尾
		}else{
			$admin = $this->session->userdata('admin');
			$data = array( 'from_url'=>$_SERVER['HTTP_REFERER'], 'admin'=>$admin );
			$this->load->view( 'admin/header.html', $data );
			$this->foot = $this->load->view( 'admin/foot.html', '', true );
		}
	}
	
	/**
	 * 用户列表
	 */
	public function index(){
		$username = $this->input->get('username');
		$where = array();
		if( !empty( $username ) ){
			$where['username'] = $username;
		}
		$page = $this->input->get('page');
		$user_list = $this->user->user_list( $where, 15, 2, $page );
		$page_bar = $this->user->make_bar();
		$this->load->view( 'admin/user_list.html', array( 'list'=>$user_list, 'foot'=>$this->foot, 'bar'=>$page_bar ) );
	}
	
	/**
	 * 更改用户状态
	 */
	public function change_status(){
		$value = $this->input->post('val');
		$uid = $this->input->post('uid');
		if( empty( $uid ) ){
			echo json_encode( array( 'res'=>2, 'info'=>'重要参数丢失' ) );
		}else{
			$this->user->user_edit( array('status'=>$value), array('id'=>$uid) );
			echo json_encode( array( 'res'=>1, 'info'=>'' ) );
		}
	}
	
	/**
	 * 重置密码
	 */
	public function reset_pass(){
		$this->load->helper( array('form') );
		$uid = $this->input->get('uid');
		if( empty( $uid ) ){
			gotourl( '重要参数丢失' );
		}
		$uinfo = $this->user->user_record(array('id'=>$uid),1);
		$this->load->view( 'admin/reset_pass.html', array('id'=>$uid,'username'=>$uinfo['username']) );
	}
	
	/**
	 * 更改密码操作
	 */
	public function change_pass(){
		$this->load->library( 'form_validation' );
		if( $this->form_validation->run( 'reset_pass' ) ){
			$password = $this->input->post('password');
			$ckpwd = $this->input->post('ckpwd');
			$uid = $this->input->post('uid');
			$from_url = $this->input->post('from_url');
			if( empty( $uid ) ){
				gotourl( '重要参数丢失' );
			}
			if( $password == $ckpwd ){
				//两次密码一致
				$uinfo = $this->user->user_record(array('id'=>$uid),3);
				$this->load->library( 'Password' );
				//加密匹配
				$newpass = $this->password->makePass( $password, $uinfo['salt'] );
				$res = $this->user->user_edit( array('password'=>$newpass), array('id'=>$uid) );
				if( $res ){
					gotourl( '修改成功', $from_url, 0 );
				}else{
					gotourl( '修改失败' );
				}
			}else{
				gotourl( '两次密码不一致' );
			}
		}else{
			gotourl( '参数格式不正确' );
		}
	}
	
	/**
	 * 删除用户
	 */
	public function del_user(){
		$uid = $this->input->get('id');
		if( empty( $uid ) ){
			gotourl( '重要参数丢失' );
		}
		$this->user->user_del( array( 'id' => $uid ) );
		redirect( 'admin/user/index' );
	}
	
	/**
	 * 用户信息
	 */
	public function user_info(){
		$uid = $this->input->get('uid');
		if( empty( $uid ) ){
			gotourl( '重要参数丢失' );
		}
		$this->load->helper( array('form') );
		$user = $this->user->user_record(array('id'=>$uid),3);
		$uinfo = $this->user->user_info(array('id'=>$uid));
		$data = array_merge( $user, $uinfo );
		$this->load->view( 'admin/user_info.html', $data );
	}
	
	/**
	 * 添加用户
	 */
	public function add_user(){
		$this->load->helper( array('form') );
		$this->load->view( 'admin/user_add.html' );
	}
	
	/**
	 * 添加
	 */
	public function new_user(){
		$param = array(
			'username'		=>	trim( $this->input->post('username') ),
			'password'		=>	$this->input->post('password'),
			'ckpwd'			=>	$this->input->post('ckpwd'),
			'from_app'		=>	1,
		);
		//我要注册
		$token = $this->user->user_register( $param );
		if( $token == 1 ){
			gotourl( '添加成功', base_url('admin/user/index'), 0 );
		}else{
			gotourl( '添加失败' );
		}
	}
	
	
	/**
	 * 更新用户信息
	 */
	public function update_info(){
		$uid = $this->input->post('uid');
		$from_url = $this->input->post('from_url');
		if( empty( $uid ) ){
			gotourl( '重要参数丢失' );
		}
		$param = array(
			'mail'			=>	$this->input->post('mail'),
			'phone'			=>	$this->input->post('phone'),
			'age'			=>	$this->input->post('age'),
			'stature'		=>	$this->input->post('stature'),
			'weight'		=>	$this->input->post('weight'),
			'sex'			=>	$this->input->post('sex'),
		);
		$token = $this->user->update_ainfo( $param, $uid );
		if( $token == 1 ){
			gotourl( '修改成功', $from_url, 0 );
		}else{
			gotourl( '修改失败' );
		}
	}

	
	/**
	 * 用户绑定设备列表
	 */
	public function user_device(){
		$uid = $this->input->get('uid');
		if( empty( $uid ) ){
			gotourl( '重要参数丢失' );
		}
		$pre_page = $this->input->get('pre_page') ? $this->input->get('pre_page') : 10;
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		$this->load->model( 'Device_model', 'device' );
		$list = $this->device->device_list( array('user_id'=>$uid), $pre_page, $page );
		$page_bar = $this->device->make_bar( 1 );
		//用户信息
		$user = $this->user->user_record(array('id'=>$uid),1);
		$data = array_merge( $user, array( 'list' => $list, 'bar' => $page_bar, 'foot' => $this->foot, 'uid' => $uid ) );
		$this->load->view( 'admin/user_device.html', $data );

	}
	
	/**
	 * 取消绑定
	 */
	public function cancel_ud(){
		$uid = $this->input->get('uid');
		$id = $this->input->get('id');
		if( empty( $uid ) || empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$this->load->model( 'Device_model', 'device' );
		$res = $this->device->cancel_ud( array( 'id'=>$id, 'user_id'=>$uid ) );
		if( $res ){
			gotourl( '修改成功', base_url('admin/user/user_device').'?uid='.$uid, 0 );
		}else{
			gotourl( '修改失败' );
		}
	}
	
	/**
	 * 用户设定
	 */
	public function setting(){
		$query = $this->db->get('user_set');
		$data = $query->row_array();
		$this->load->view( 'admin/user_setting.html', $data );
	}
	
	/**
	 * 设定提交
	 */
	public function user_set(){
		$login = $this->input->post('login');
		$reg = $this->input->post('reg');
		$check = $this->input->post('check');
		$data = array(
			'login'	=>	$login,
			'reg'	=>	$reg,
			'check'	=>	$check,
		);
		$str = $this->db->update('user_set', $data);
		if( $str ){
			gotourl( '修改成功', base_url('admin/user/setting'), 0 );
		}else{
			gotourl( '修改失败' );
		}
	}
	
	/**
	 * 用户设备列表
	 */
	public function device_data(){
		$model = $this->input->get('model');
		$model = $model ? $model : '1';
		$date = $this->input->get('date');
		$ids = $this->input->get('ids');
		//日期处理
		if( empty( $date ) ){
			//今天
			$time = time();
		}else{
			//选定日期
			$time = strtotime( $date );
		}
		$uid = $this->input->get('uid');
		if( empty( $uid ) ){
			gotourl( '请先选择要查看的用户' );
		}
		//用户信息
		$user = $this->user->user_record( array( 'id'=>$uid ), 1 );
		if( empty( $user['id'] ) ){
			gotourl( '用户信息异常' );
		}
		//载入device类
		$this->load->model( 'Device_model', 'device' );

		$devices = $this->device->device_list( array( 'user_id'=>$uid ), '', 'user_device.*,device.device_name' );
		$param = array(
			'list'	=>	$devices,
			'uid'	=>	$uid,
			'date'	=>	$date,
			'model'	=>	$model,
			'ids'	=>	$ids,
		);
		$data = array_merge( $user, $param );
		$this->load->view( 'admin/user_ddata.html', $data );	
	}
	
	/**
	 * ajax获取数据
	 */
	public function get_data(){
		$ids = $this->input->post('ids');
		$model = $this->input->post('model');
		$date = $this->input->post('date');
		if( empty( $ids ) ){
			echo json_encode( array( 'res'=>0, 'info'=>'设备id信息为空') );
			exit;
		}
		//载入device函数
		$this->load->model( 'Device_model', 'device' );
		if( empty( $date ) ){
			//今天
			$time = time();
		}else{
			//选定日期
			$time = strtotime( $date );
		}
		//x坐标显示数据
		$t = date('t',$time);
		$dinfo = $this->device->time_fomate( $time, $model );
		if( !$dinfo ){
			echo json_encode( array( 'res'=>0, 'info'=>'查询日期或者模式异常') );
			exit;
		}else{
			//组织where
			$where = array(
				'time >='		=>	$dinfo['go_time'],
				'time <='		=>	$dinfo['end_time']
			);
			$list = $this->device->collect_data( $where, $dinfo['db'], $ids, $model );
			if( $list ){
				$res = array();
				foreach( $list as $li ){
					if( empty( $res[$li['device_code']] ) ){
						$res[$li['device_code']] = $li['electricity'];
					}else{
						$res[$li['device_code']] .= ','.$li['electricity'];
					}
				}
				echo json_encode( array( 'res'=>1, 'list'=>$res, 't'=>$t ) );
			}else{
				echo json_encode( array( 'res'=>1, 'list'=>'', 't'=>0 ) );
			}
		}
	}
	
}