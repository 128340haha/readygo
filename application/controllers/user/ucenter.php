<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ucenter extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model( 'User_model', 'user' );
		$this->load->helper('html');
		session_start();
		is_login( $this, 'user' );
	}
	
	
	public function index(){
		$key = $this->input->get('key');
		$head = $this->user->user_face( $_SESSION['uid'] );
		$this->load->model( 'Device_model', 'device' );
		if( !empty( $key ) ){
			$where = $this->device->limit_type( $key );
			$where = array_merge( $where, array( 'user_id'=>$_SESSION['uid'] ) );
		}else{
			$where = array( 'user_id'=>$_SESSION['uid'] );
		}
		$device = $this->device->device_list( $where, 10 );
		$token = $this->user->user_info( array( 'id'=>$_SESSION['uid'] ) );
		$this->load->model( 'Forum_model', 'forum' );
		$statistics = $this->forum->make_statis( $_SESSION['uid'] );
		$data = array( 'list'=>$device, 'showlive'=>$token['forumLive'], 'key'=>$key, 'statistics'=>$statistics, 'face'=>$head['src'] );
		$main = $this->load->view( 'user/main.html', $data, true );			
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	/**
	 * 回复列表
	 */
	public function forums(){
		$uid = $_SESSION['uid'];
		$this->load->model( 'Forum_model', 'forum' );
		$where = array( 'f.review' => 1, 'f.isdel' => 0, 'f.user_id'=>$uid );
		$list = $this->forum->forum_list( $where, '', 1, 15, 'addtime desc' );
		$data = array( 'list'=>$list );
		$main = $this->load->view( 'user/forums.html', $data, true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	
	/**
	 * 回复列表
	 */
	public function response(){
		$uid = $_SESSION['uid'];
		$this->load->model( 'Forum_model', 'forum' );
		$where = array( 'r.user_id'=>$uid, 'f.review'=>1, 'f.isdel'=>0 );
		$list = $this->forum->response_list( $where );
		$data = array( 'list'=>$list );
		$main = $this->load->view( 'user/responses.html', $data, true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	
	public function game_list(){
		$this->load->model( 'Game_model', 'game' );
		$list = $this->game->game_list();
		$data = array( 'list'=>$list );
		$main = $this->load->view( 'user/games.html', $data, true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	
	public function face(){
		//图片信息
		$data = $this->user->user_face( $_SESSION['uid'] );
		//返回地址参数
		$data['call_back'] = urlencode( base_url().'user/ucenter/upload' );
		$main = $this->load->view( 'user/face.html', $data, true );
		$this->load->view( 'user/ucenter.html', array( 'main'=>$main ) );
	}
	
	public function upload(){
		$this->load->library( 'Avatar' );
	//	$au = new AvatarUploader();
		if ( $this->avatar->processRequest() ) {
			exit();
		}
	}
	
}
	
	
