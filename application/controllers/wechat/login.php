<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
	}
	
	/**
	 * 登录绑定
	 */
	public function index(){	
		$data = array( 
			'username' 	=> '', 
			'password'	=> '', 
			'error'		=> '',
			'captcha' 	=> '',
			'img'		=>	$this->captcha()
		 );
		$this->load->view( 'wechat/login.html', $data );
	}
	
	/**
	 * 登录验证
	 */
	public function auth(){
		//构建数据
		$openid = $this->session->userdata( 'openid' );
		if( !empty( $openid ) ){
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
				//绑定操作
				$this->load->model( 'Wx_model', 'wx' );
				$str = $this->wx->user_bind( $param['username'], $openid );
				if( $str ){
				//验证通过
					redirect('wechat/ucenter/');
				}else{
					echo "绑定失败";
				}
			}else{
				//报错
				$data = array( 'username'=>$param['username'], 'password'=>$param['password'], 'captcha'=>$param['captcha'], 'img'=>$this->captcha(), 'error'=>$token );
				$this->load->view( 'wechat/login.html', $data );
			}
		}else{
			echo '数据获取失败，请返回重试';
		}
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
	 * 注册
	 */
	public function reg(){
		$this->load->helper( array('form') );
		$data = array( 'error'=>'', 'username'=>'', 'password'=>'', 'ckpwd'=>'', 'captcha'=>'', 'img'=>$this->captcha() );
		$this->load->view( 'wechat/register.html', $data );
	}
	
	
	/**
	 * 注册验证
	 */
	public function register(){
		$param = array(
			'username'		=>	trim( $this->input->post('username',true) ),
			'password'		=>	$this->input->post('password',true),
			'ckpwd'			=>	$this->input->post('ckpwd'),
			'captcha'		=>	$this->input->post('captcha',true),
			'from_app'		=>	0,
		);
		$this->load->model( 'User_model', 'user' );
		//不能包含中文
		$cn = $this->user->chinese_checked( $param['username'] );
		//不能包含空格
		$blank = $this->user->blank_checked( $param['username'] );
		if( $blank ){
			//报错
			$data = array( 'username'=>$param['username'], 'password'=>'', 'ckpwd'=>'', 'captcha'=>$param['captcha'], 'img'=>$this->captcha(), 'error'=>'账号中请不要输入空格' );
		}else{
			if( $cn ){
				$data = array( 'username' => $param['username'], 'password'=> '', 'ckpwd'=>'', 'captcha'=>$param['captcha'], 'img'=>$this->captcha(), 'error'=>'账号不能使用中文' );
			}else{
				//我要注册
				$token = $this->user->user_register( $param, 2 );
				if( $token['res'] == 1 ){
					//验证通过
					redirect('wechat/login/index');
				}else{
					$data = array( 'username' => $param['username'], 'password'=> '', 'ckpwd'=>'', 'captcha'=>$param['captcha'], 'img'=>$this->captcha(), 'error'=>$token );
				}
			}
		}
		$this->load->view( 'wechat/register.html', $data );
	}
	
	/**
	 * 验证码
	 */
	public function captcha() {
		$this->load->helper( 'captcha' );
		$code = random_string( 'alnum', 4 );
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