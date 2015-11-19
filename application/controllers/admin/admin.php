<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Admin_model', 'admin' );
		$this->load->helper('html');
		if( !empty( $_SERVER['HTTP_REFERER'] ) ){
			$from_url = $_SERVER['HTTP_REFERER'];
		}else{
			$from_url = '';
		}
		$this->data = array( 'from_url'=> $from_url , 'admin'=>$this->session->userdata('aid') );
	}
	
	/**
	 * 登录
	 */
	public function login(){
		$this->load->helper( array('form') );
		//获取验证码
		$error = $this->session->userdata('error');
		if( $error > 3 ){
			$data['verify'] = $this->captcha();
		}else{
			$data['verify'] = '';
		}
		$data['error'] = '';
		$this->load->view( 'admin/login.html', $data );
	}
	
	
	/**
	 * 登录验证
	 */
	public function login_validation(){
		//构建数据
		$param = array(
			'admin'		=>	$this->input->post('admin'),
			'password'	=>	$this->input->post('password'),
			'verify'	=>	$this->input->post('verify')
		);
		//登录验证
		$token = $this->admin->admin_login( $param );
		//调用返回结果格式
		if( $token == 1 ){
			//验证通过
			redirect('admin/setting/index');
		}else{
			//报错
			$data['error'] = $token;
			$this->load->view( 'admin/login.html', $data );
		}
	}
	
	/**
	 * 登出
	 */
	public function admin_layout(){
		$param = array( 'aid'=>'','admin'=>'','priv'=>'' );
		$this->session->unset_userdata( $param );
		redirect('admin/admin/login');
	}
	
	
	/**
	 * 管理员列表
	 */
	public function index(){
		is_login( $this, 'admin' );
		$admin = $this->input->get('admin');
		$page = $this->input->get('page');
		$where = array();
		if( !empty( $admin ) ){
			$where['admin'] = $admin;
		}
		$admin_list = $this->admin->admin_list( $where, 15, $page );
		$page_bar = $this->admin->make_bar();
		$this->load->view( 'admin/header.html', $this->data );
		$this->foot = $this->load->view( 'admin/foot.html', '', true );
		$this->load->view( 'admin/admin_list.html', array( 'list'=>$admin_list, 'foot'=>$this->foot, 'bar'=>$page_bar ) );
	}
	
	/**
	 * 添加管理员
	 */
	public function add_admin(){	
		$this->load->helper( array('form') );
		$data = array(
			'admin'		=>	'',
			'password'	=>	'',
			'ckpwd'		=>	'',
			'priv'		=>	1,
		);
		$this->load->view( 'admin/header.html', $this->data );
		$this->foot = $this->load->view( 'admin/foot.html', '', true );
		$this->load->view( 'admin/admin_add.html', $data );
	}

	/**
	 * 添加管理员操作
	 */
	public function new_manager(){
		$param = array(
			'admin'		=>	$this->input->post('admin'),
			'password'	=>	$this->input->post('password'),
			'ckpwd'		=>	$this->input->post('ckpwd'),
			'priv'		=>	$this->input->post('priv')
		);
		$token = $this->admin->ck_admin( $param['admin'] );
		if( $token == 'true' ){
			$res = $this->admin->admin_register( $param );
			if( $res == 1 ){
				gotourl( '添加成功', base_url().'admin/admin/index', 0 );
			}else{
				gotourl( $res );
			}
		}else{
			gotourl( '账号为空或者已经存在' );
		}
	}
	
	/**
	 * 删除管理员
	 */
	public function del_manager(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '主要参数丢失' );
		}else{
			$str = $this->admin->admin_delete( array( 'id'=>$id ) );
			if( $str ){
				gotourl( '删除成功', base_url().'admin/admin/index', 0 );
			}else{
				gotourl( '删除失败' );
			}
		}
	}
	
	/**
	 * 重设管理员密码
	 */
	public function reset_manager(){
		$this->load->helper( array('form') );
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '主要参数丢失' );
		}else{
			$info = $this->admin->admin_info( array( 'id'=>$id ), 1 );
			if( $info ){
				$this->load->view( 'admin/header.html', $this->data );
				$this->foot = $this->load->view( 'admin/foot.html', '', true );
				$this->load->view( 'admin/admin_reset.html', $info );
			}else{
				gotourl( '找不到管理员信息' );
			}
		}
	}
	
	/**
	 * 重设操作
	 */
	public function reset_pass(){
		$id = $this->input->post('id');
		$password = $this->input->post('password');
		$ckpwd = $this->input->post('ckpwd');
		if( empty( $id ) || empty( $password ) || empty( $ckpwd ) ){
			gotourl( '主要参数丢失' );
		}elseif( $password != $ckpwd ){
			gotourl( '两次输入密码不一致' );
		}else{
			$where = array( 'id'=>$id );
			$info = $this->admin->admin_info( $where, 3 );
			$this->load->library( 'Password' );
			//加密匹配
			$mypass = $this->password->adminPass( $info['admin'], $password, $info['salt'] );
			$param = array( 'password'=>$mypass );
			$res = $this->admin->admin_edit( $param, $where );
			if( $res ){
				gotourl( '更新成功', base_url().'admin/admin/index', 0 );
			}else{
				gotourl( $res );
			}
		}
	}
	
	
	/**
	 * ajax
	 */
	public function managers(){
		$admin = $this->input->post('admin');
		if( !empty( $admin ) ) {
			echo $this->admin->ck_admin( $admin );
		}else{
			echo 'false';
		}
		exit;
	}
	
	
	/**
	 * 验证码
	 */
	public function captcha() {
		$this->load->helper( 'captcha' );
		$code = rand( 1, 999999 );
		$vals = array(
				'word' => $code,
				'img_path' => './captcha/',
				'img_url' => base_url().'captcha/',
				'font_path' => './path/to/fonts/texb.ttf',
				'img_width' => 150,
				'img_height' => 50,
				'expiration' => 7200
		);
		$model = $this->input->get_post('model') ? $this->input->get_post('model') : 1;
		return captcha_code( $this, $vals, $model );
	}
	
}