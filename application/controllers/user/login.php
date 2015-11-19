<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		session_start();
	}
	
	/**
	 * 登录
	 */
	public function index(){	
	//	redirect( 'business/index/index' );
		$this->load->helper( array('form') );
		//获取验证码
		$data['captcha'] = $this->captcha();
		$data['error'] = '';
		$this->load->view( 'user/login.html', $data );
	}
	
	/**
	 * 登录验证
	 */
	public function auth(){
		//构建数据
		$param = array(
			'username'	=>	$this->input->post('username',true),
			'password'	=>	$this->input->post('password',true),
			'captcha'	=>	$this->input->post('captcha',true),
			'from_app'	=>	0,
		);
		$this->load->model( 'User_model', 'user' );
		//登录验证
		$token = $this->user->user_login( $param );
		if( $token == 1 ){
			//验证通过
			echo json_encode( array('res'=>1) );
		}else{
			//报错
			echo json_encode( array('res'=>0, 'info'=>$token) );
		}
		exit;
	}
	
	/**
	 * 注销
	 */
	public function layout(){
		$new_data = array(
				'username'		=>	'',
				'uid'			=>	'',
				'login_time'	=>	'',
				'login_ip'		=>	'',
				'last_time'		=>	'',
				'last_ip'		=>	'',
				'status'		=>	''
		);
		$this->session->unset_userdata( $new_data );
		session_destroy();
		redirect('/user/login/index');
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