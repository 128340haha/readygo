<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends CI_Controller {
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'User_model', 'user' );
		$this->load->model( 'Route_model', 'route' );
		$this->load->helper('html');
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
	}
	
	/**
	 * 主页
	 */
	public function index(){	
		$this->load->helper('html');
		$this->load->view( 'business/index.html', array( 'base_url'=>base_url() ) );
	}

	/**
	 * 合作商
	 */
	public function work(){
		$this->load->helper('html');
		$page = $this->input->get('page');
		$list = $this->route->game_list( '', 9, $page );
		$this->load->view( 'business/partner.html', array( 'list'=>$list ) );
	}
	
	/**
	 * 关于我们
	 */
	public function about(){
		$this->load->helper('html');
		$this->load->view( 'business/about.html' );
	}
	
	/**
	 * 加入我们
	 */
	public function join(){
		$this->load->helper('html');
		$this->load->view( 'business/contact.html' );
	}
	
	/**
	 * 登录界面
	 */
	public function login(){
		$this->load->helper( array('form') );
		$data['captcha'] = $this->captcha();
		$data['error'] = '';
		$this->load->view( 'business/login.html', $data );
	}
	
	/**
	 * 登录判断
	 */
	public function biz_login(){
		//构建数据
		$param = array(
			'username'	=>	$this->input->post('username', true),
			'password'	=>	$this->input->post('password', true),
			'captcha'	=>	$this->input->post('captcha', true),
			'from_app'	=>	0,
		);
		//登录验证
		$access = $this->user->access_check('biz_login');
		if ( $access ){
			//账号密码登录
			$token = $this->user->user_login( $param );
			//商家权限判断
			$prv = $this->user->user_access( $param['username'] );
			if( $prv == 1 ){ 
				if( $token == 1 ){
					//验证通过
					redirect('business/center/index');
				}else{
					//报错
					$data['captcha'] = $this->captcha();
					$data['error'] = $token;
					$this->load->view( 'business/login.html', $data );
				}
			}else{
				$data['captcha'] = $this->captcha();
				$data['error'] = '您没有商家权限！';
				$this->load->view( 'business/login.html', $data );
			}
		}else{
			echo return_back( array('res'=>0,'info'=>'管理登录功能已经关闭,请留意相关公告！') );
		}
	}
	
	/**
	 * 验证码
	 */
	public function captcha() {
		$this->load->helper( 'captcha' );
		$code = $this->user->makeWord( 6 );
		$vals = array(
			'word' => $code,
			'img_path' => './captcha/',
			'img_url' => base_url().'captcha/',
			'font_path' => './path/to/fonts/texb.ttf',
			'img_width' => 150,
			'img_height' => 30,
			'expiration' => 7200
		);
		//ajax刷新用标示符
		$model = $this->input->get_post('model') ? $this->input->get_post('model') : 1;
		return captcha_code( $this, $vals, $model );
	}
	
}