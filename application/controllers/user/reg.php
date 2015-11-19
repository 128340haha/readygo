<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reg extends CI_Controller{
	
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 注册页
	 */
	public function index(){
		$this->load->helper( array('form') );
		$data['reg_captcha'] = $this->captcha();
		$data['error'] = '';
		$this->load->view( 'user/reg.html', $data );
	}
	
	/**
	 * 注册验证
	 */
	public function register(){
		$param = array(
			'username'		=>	$this->input->post('username',true),
			'password'		=>	$this->input->post('password',true),
			'ckpwd'			=>	$this->input->post('ckpwd'),
			'captcha'		=>	$this->input->post('captcha',true),
			'from_app'		=>	0,
		);
		$this->load->model( 'User_model', 'user' );
		//我要注册
		$token = $this->user->user_register( $param );
		if( $token == 1 ){
			//验证通过
			echo json_encode( array('res'=>1) );
		}else{
			//报错
			echo json_encode( array('res'=>0, 'info'=>$token) );
		}
	}
	
	/**
	 * ajax异步判断 当前用户名是否存在
	 */
	public function new_user(){
		$query = $this->db->query('select id from user where username = \''.$this->input->post('username').'\'');
		$info = $query->row_array();
		$username = $this->input->post('username');
		if( empty( $info['id'] ) && !empty( $username ) ){
			//可以注册
			echo 'true';
		}else{
			//已存在或者为空
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
		return captcha_code( $this, $vals, $model, 'captcha' );
	}
	
}