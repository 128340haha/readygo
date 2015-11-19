<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ucenter extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model( 'User_model', 'user' );
		$this->load->model( 'Wx_model', 'wx' );
		$this->load->helper('html');
	}
	
	public function index(){
		$code = $this->input->get('code');
		//是否已经获取过微信信息
		$openid = $this->session->userdata( 'openid' );
		//未获取过则去腾讯官网获取
		$openid = $openid ? $openid : $this->wx->code_access_token( $code );
		if( $openid ){
			//判断皮肤页面跳转
			if( $skin = $this->wx->skin( $openid ) ){
				redirect( base_url('wechat/liberty/index').'?id='.$skin );
			}
			//查询是否已经存在绑定信息
			$uinfo = $this->wx->bind_info( $openid );
			if( !empty( $uinfo['id'] ) ){
				//已经绑定设备，直接拿本地用户信息
				if( empty( $uinfo['path'] ) ){
					//没设置头像，使用微信的
					$winfo = $this->wx->getUserInfo( $openid );
					$uinfo['headimgurl'] = $winfo->headimgurl;
				}else{
					//有自己的头像
					$uinfo['headimgurl'] = $uinfo['headimgurl'].'_middle.jpg';		//	base_url().'assets/wechat/images/logo.png';
				}
				$uinfo['nickname'] = $uinfo['username'];
				$uinfo['bind'] = 1;
				$this->session->set_userdata( 'uid', $uinfo['id'] );
			}else{
				//无绑定信息,使用微信作为用户信息
				$uinfo = $this->wx->getUserInfo( $openid );
				$uinfo->bind = 0;
			}
			//openid存入session
			$this->session->set_userdata( 'openid', $openid );
			$this->load->view( 'wechat/uinfo.html', $uinfo );
		}else{
			//获取失败
			echo '获取openid失败,请重新访问!';
		}	
	}
	
/*	
	public function games(){
		$openid = $this->session->userdata( 'openid' );
		//未获取过则去个人中心获取
		if( empty( $openid ) ){
			redirect( 'wechat/ucenter/index' );
		}
		/***********测试数据**************
		//获取jsapi_ticket
		$sign = $this->wx->get_signature();
		if( $sign['errcode'] === '0' ){
			$data = array( 
				'appid'		=>	$sign['appid'],
				'timestamp'	=>	$sign['timestamp'],		//签名时间戳
				'noncestr'	=>	$sign['noncestr'],		//签名随机串
				'signature'	=>	$sign['signature']		//签名
			);
			$this->load->view( 'wechat/games.html', $data );
		}else{
			echo '错误码:'.$sign['errcode'].',错误提示:'.$sign['errmsg'];
		}
	}
*/
	
	/**
	 * 用户信息
	 */
	public function infomation(){
		$uid = $this->session->userdata('uid');
		if( empty( $uid ) ){
			$data = array( 'error'=>'请先绑定用户' );
			$this->load->view( 'wechat/error.html', $data );
		}else{
			//未获取过则去腾讯官网获取
			$uinfo = $this->user->user_info( array( 'id'=>$uid ) );	
			$face = $this->user->user_face( $uid );	
			$main = $this->user->user_record( array( 'id'=>$uid ), 1 );
			$uinfo['username'] = $main['username'];
			if( empty( $face['path'] ) ){
				$openid = $this->session->userdata( 'openid' );
				//未获取过则去腾讯官网获取
				$openid = $openid ? $openid : $this->wx->code_access_token( $code );
				//没设置头像，使用微信的
				$winfo = $this->wx->getUserInfo( $openid );
				$uinfo['headimgurl'] = $winfo->headimgurl;
			}else{
				//有自己的头像
				$uinfo['headimgurl'] = $face['src'].'_middle.jpg';		//	base_url().'assets/wechat/images/logo.png';
			}
			$this->load->view( 'wechat/infomation.html', $uinfo );
		}
		
	}
		
	/**
	 * 一键注册
	 */
	public function onekey_reg(){
		$openid = $this->session->userdata( 'openid' );
		$res = $this->wx->keyreg( $openid );
		if( $res ){
			redirect('wechat/ucenter/index');
		}else{
			$data = array( 'error'=>'操作失败，请稍后再试' );
			$this->load->view( 'wechat/error.html', $data );
		}
	}
	
	/**
	 * ajax获取头像
	 */
	public function aface(){
		$openid = $this->session->userdata( 'openid' );
		if( empty( $openid ) ){
			echo 0;
		}else{
			$uinfo = $this->wx->getUserInfo( $openid );
			echo $uinfo->headimgurl;
		}
	}
	
	
	public function share(){
		$this->load->view( 'wechat/share.html' );
	}
	
	
	/**
	 * openid丢失提示
	 */
	public function error(){
		$error = $this->input->get_post('error');
		if( empty( $error ) ){
			$data = array( 'error'=>'操作超时或者openid丢失，请从微信页面个人中心重新发起访问' );
		}else{
			$data = array( 'error'=>$error );
		}
		$this->load->view( 'wechat/error.html', $data );
	}
	

}