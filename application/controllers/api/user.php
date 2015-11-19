<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		$this->load->model( 'User_model', 'user' );
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
	}
	
	/**
	 * 登录验证
	 */
	public function login(){		
		//构建数据
		$param = array(
			'username'	=>	$this->input->post('username'),
			'password'	=>	$this->input->post('password'),
			'from_app'	=>	1,
		);
		$access = $this->user->access_check('login');
		//验证站点控制功能
		if( !$access ){
			echo return_back( array('res'=>0,'info'=>'用户登录功能已经关闭,请留意相关公告！') );
		}else{
			//登录验证
			$token = $this->user->user_login( $param, 2 );
			//调用返回结果格式
			echo return_back( $token );
		}
	}
	
	/**
	 * 当前登录状态判断
	 * @return string
	 */
	public function login_status(){
		is_login( $this, 'app' );
		$param = array(
			'id'			=> $_SESSION['uid'],
			'username'		=> $_SESSION['username'],
			'login_time'	=> $_SESSION['login_time'],
			'login_ip'		=> $_SESSION['login_ip'],
			'server_time'	=> time(),
		);
		echo return_back( array('res'=>1,'info'=>$param) );	
	}
	
	
	/**
	 * 注册
	 */
	public function register(){
		$param = array(
			'username'		=>	trim( $this->input->post('username',true) ),
			'password'		=>	$this->input->post('password',true),
			'ckpwd'			=>	$this->input->post('ckpwd',true),
			'from_app'		=>	1,
		);
		//我要注册
		$access = $this->user->access_check('reg');
		//验证账号不为中文
		$cn = $this->user->chinese_checked( $param['username'] );
		//不能包含空格
		$blank = $this->user->blank_checked( $param['username'] );
		if( $blank ){
			//报错
			echo return_back( array('res'=>0,'info'=>'账号中请不要输入空格') );
		}else{
			if( $cn ){
				echo return_back( array('res'=>0,'info'=>'账号不能使用中文！') );
			}else{
				//账号不能是zhongwen
				if( !$access ){
					echo return_back( array('res'=>0,'info'=>'用户注册功能已经关闭,请留意相关公告！') );
				}else{
					$token = $this->user->user_register( $param, 2 );
					echo return_back( $token );
				}
			}
		}
	}
	
	/**
	 * 用户信息列表
	 */
	public function user_info(){
		//登录判断
		is_login( $this, 'app' );
		$uid = $_SESSION['uid'];
		$uinfo = $this->user->user_info( array('id'=>$uid) );
		echo return_back( array('res'=>1,'info'=>$uinfo) );
	}
	
	/**
	 * 更改密码
	 */
	public function change_pass(){
		is_login( $this, 'app' );
		$oldpwd = $this->input->post('oldpwd');
		$newpwd = $this->input->post('newpwd');
		$uid = $_SESSION['uid'];
		if( empty( $oldpwd ) || empty( $newpwd ) ){
			echo return_back( array('res'=>0,'info'=>'主要参数丢失') );
		}elseif( strlen( $newpwd ) < 6 || strlen( $newpwd ) > 30 ){
			echo return_back( array('res'=>0,'info'=>'密码长度不小于6位，不大于30位') );
		}else{
			$res = $this->user->change_password( $newpwd, $oldpwd, $uid );
			if( $res == 1 ){
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				//错误提示
				echo return_back( array('res'=>0,'info'=>$res) );
			}
		}
	}
	
	/**
	 * 修改个人信息
	 */
	public function edit_info(){
		//登录判断
		is_login( $this, 'app' );
		$param = array(
			'mail'			=>	$this->input->post('mail'),
			'phone'			=>	$this->input->post('phone'),
			'age'			=>	$this->input->post('age'),
			'stature'		=>	$this->input->post('stature'),
			'weight'		=>	$this->input->post('weight'),
			'sex'			=>	$this->input->post('sex'),
		);
		//我要更改个人信息
		$token = $this->user->update_info( $param, 2 );
		echo return_back( $token );	
	}
	
	
	/**
	 * ajax异步判断 当前用户名是否存在
	 */
	public function new_user(){
		$username = $this->input->post('username');
		if( empty( $username ) ){
			echo return_back( array('res'=>0,'info'=>'重要参数丢失') );
		}else{
			$info = $this->user->user_record( array( 'username'=>$username ), 1 );
			if( empty( $info['id'] ) ){
				//可以注册
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				//已存在或者为空
				echo return_back( array('res'=>0,'info'=>'该用户已经存在') );
			}
		}
	}
	
	
	/**
	 * 注销
	 */
	public function layout(){
		session_destroy();
		echo return_back( array('res'=>1,'info'=>'') );
	}
	
	/**
	 * 传输我的产品给他人
	 */
	public function transfer_dev(){
		//验证登录
		is_login( $this, 'app' );
		//目标用户id
		$user_id = $this->input->post('user_id');
		//分享的设备id
		$devices = $this->input->post('devices');
		//当前登录用户id
		$uid = $_SESSION['uid'];
		
		$res = $this->user->transfer( $uid, $user_id, $devices );
		if( $res == 1 ){
			echo return_back( array('res'=>1,'info'=>'') );
		}else{
			//错误提示
			echo return_back( array('res'=>0,'info'=>$res) );
		}
	}
	
	/**
	 * 发送短信
	 */
	public function sent_sms(){
		$mobile = $this->input->post('mobile', true);
		if( preg_match( "/1[3458]{1}\d{9}$/", $mobile ) ){
			$this->load->library( 'sms' );
			$mobile_code = '您的验证码是：'.$this->sms->random(6,1).'。请不要把验证码泄露给其他人。';
			$post_data = $this->sms->make_data( $mobile, $mobile_code );
			$res = $this->sms->Post( $post_data, $this->config->item('sms_target') );
			if( $res == 1 ){
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				//错误提示
				echo return_back( array('res'=>0,'info'=>$res) );
			}
		}else{
			echo return_back( array('res'=>0,'info'=>'手机号码格式异常') );
		}
		
	}
	
	
}