<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Index extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model( 'forum_model', 'forum' );
		$this->load->helper('html');
		session_start();
		$this->nolive = $this->input->get_post('nolive');
		if( $this->nolive == 1 ){
			//ajax等无需加载导航使用
		}else{
			$this->liveModel();
		}
		if( empty( $_SESSION['uid'] ) ){
			$_SESSION['uid'] = '';
		}
	}
	
	
	public function index(){
		$child = $this->input->get('url');
		if( empty( $child ) ){
			$main = base_url()."forum/index/main";
		}else{
			$main = $child;
		}
		$this->load->view('forum/index.html', array( 'main'=>$main ));
	}
	
	
	public function left(){
		$types = $this->forum->getTypes();
		$this->load->model( 'user_model', 'user' );
		if( empty( $_SESSION['uid'] ) ){
			$uid = '';
		}else{
			$uid = $_SESSION['uid'];
		}
		$head = $this->user->user_face( $uid );
		$this->load->view('forum/left.html',array('types'=>$types,'face'=>$head['src']));
	}
	
	/**
	 * 显示主题
	 */
	public function main(){
		$id = $this->input->get('id');
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		$per_page = $this->input->get('per_page') ? $this->input->get('per_page') : 15;
		//数据列表
		$list = $this->forum->forum_list( array( 'f.review' => 1, 'f.isdel' => 0 ), $id, $page, $per_page, 'sticky desc,addtime desc' );
		//分页信息
		$page_info = $this->forum->make_bar( 2 );
		$page_bar = $this->forum->make_bar( 1, '&id='.$id );
		//用户信息
		$users = $this->forum->forum_users( $list );
		$data = array( 'list'=>$list, 'pinfo'=>$page_info, 'bar'=>$page_bar, 'users'=>$users, 'tid'=>$id );
		$this->load->view('forum/main.html',$data);
	}
	
	/**
	 * 新帖
	 */
	public function new_forum(){
		is_login( $this, 'bbs' );
		$tid = $this->input->get('tid');
		$types = $this->forum->getTypes( 2 );
		$data = array(
			'type'		=>	$types, 
			'captcha'	=>	'', 
			'from_url'	=>	$this->input->server('HTTP_REFERER'),
			'tid'		=>	$tid,
		);
		$this->load->view( 'forum/new.html', $data );
	}
	
	/**
	 * 删除
	 */
	public function del_forum(){
		is_login( $this, 'bbs' );
		$id = $this->input->get('id');
		$res = $this->forum->delete_forum( $id );
		if( $res ){
			$url = base_url('forum/index/main');
			gotourl( '删除成功', $url, 0, 'forum' );
		}else{
			gotourl( '删除失败', 'back', 1, 'forum' );
		}
	}
	
	
	public function del_floor(){
		is_login( $this, 'bbs' );
		$id = $this->input->get('id');
		$res = $this->forum->delete_floor( $id );
		if( $res ){
			$url = $this->input->server('HTTP_REFERER');
			gotourl( '删除成功', $url, 0, 'forum' );
		}else{
			gotourl( '删除失败', 'back', 1, 'forum' );
		}
	}
	
	
	/**
	 * 帖子详情
	 */
	public function details(){
		$id = $this->input->get('id');
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		$per_page = $this->input->get('per_page') ? $this->input->get('per_page') : 15;
		if( empty( $id ) ){
			gotourl( '主要参数丢失', 'back', 1, 'forum' );
		}
		//帖子信息
		$info = $this->forum->forum_info( array( 'f.id'=>$id, 'f.review'=>1 ), true );
		//回复列表
		$list = $this->forum->back_list( $id, $page, $per_page );
		//用户信息
		$users = $this->forum->forum_users( $list );
		//楼主信息
		$this->load->model( 'user_model', 'user' );
		//分页信息
		$page_info = $this->forum->make_bar( 2 );
		$page_bar = $this->forum->make_bar( 1, '&id='.$id );
		if( $page == 1 ){
			$uinfo = $this->user->user_record( array('id'=>$info['user_id']), 1 );
			$info['username'] = $uinfo['username'];
		}
		$data = array( 'info'=>$info, 'list'=>$list, 'users'=>$users, 'pinfo'=>$page_info, 'bar'=>$page_bar );
		$this->load->view( 'forum/details.html', $data );
	}
		
	
	/**
	 * 编辑
	 */
	public function edit_forum(){
		is_login( $this, 'bbs' );
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '主要参数丢失', 'back', 1, 'forum' );
		}
		$types = $this->forum->getTypes( 2 );
		//帖子信息
		$info = $this->forum->forum_info( array( 'f.id'=>$id, 'f.review'=>1 ) );
		$images = $this->forum->find_image( $info['context'] );
		if( $images ){
			$img = implode( ',', $images );
		}else{
			$img = '';
		}
		if( $info['user_id'] == $_SESSION['uid'] ){
			$data = array(
				'type'		=>	$types,
				'img'		=>	$img,
				'info'		=>	$info,
				'captcha'	=>	'',
				'from_url'	=>	$this->input->server('HTTP_REFERER'),
			);
			$this->load->view( 'forum/edit.html', $data );
		}else{
			gotourl( '您没有编辑的权限', 'back', 1, 'forum' );
		}
	}
	
	
	/**
	 * 实时数据
	 */
	public function liveModel(){
		if( empty( $_SESSION['uid'] ) ){
			$this->showLive = '';
		}else{
			$this->showLive = $this->forum->liveShow();
		}
	}
	
	/**
	 * 回帖
	 */
	public function response(){
		$pid = $this->input->post('pid');
		//标题
		$title = $this->input->post('title',true);
		//内容
		$content = $this->input->post('content',true);
		//验证码
		$vc = $this->input->post('vc',true);
		$captcha = $this->session->userdata('forum');
		if( $vc == $captcha ){
			//验证码通过
			if( empty( $title ) || empty( $content ) || empty( $pid ) ){
				gotourl( '主要参数不能为空', 'back', 1, 'forum' );
			}else{
				$param = array( 
					'pid'		=>	$pid, 
					'heading'	=>	$title, 
					'context'	=>	$content, 
					'user_id'	=>	$_SESSION['uid'],
					'retime'	=>	time(),
				);
				$res = $this->forum->new_response( $param );
				$this->forum->confirm_image( $content, $pid );
				if( $res ){
					$url = base_url('forum/index/details').'/?id='.$pid;
					gotourl( '回复成功', $url, 0, 'forum' );
				}else{
					gotourl( '发帖失败', 'back', 1, 'forum' );
				}
			}
		}else{
			gotourl( '验证码不一致', 'back', 1, 'forum' );
		}
	}
	
	/**
	 * 发贴
	 */
	public function sent_forum(){
		is_login( $this, 'bbs' );
		$from_url = $this->input->post('from_url');
		//分类
		$category = $this->input->post('category');
		//标题
		$title = $this->input->post('title',true);
		//内容
		$content = $this->input->post('content',true);
		//验证码
		$vc = $this->input->post('vc',true);
		$captcha = $this->session->userdata('forum');
		if( $vc == $captcha ){
			//验证码通过
			if( empty( $category ) || empty( $title ) || empty( $content ) ){
				gotourl( '主要参数不能为空', 'back', 1, 'forum' );
			}else{
				$param = array( 
					'type'		=>	$category, 
					'title'		=>	$title, 
					'context'	=>	$content, 
					'user_id'	=>	$_SESSION['uid'],
					'addtime'	=>	time(),
					'review'	=>	0,			//通过验证
				);
				//处理图片问题
				$res = $this->forum->new_forum( $param );	
				$new_id = $this->db->insert_id();
				$this->forum->confirm_image( $content, $new_id );
				if( $res ){
					gotourl( '发帖成功,请耐心等待审核', $from_url, 0, 'forum' );
				}else{
					gotourl( '发帖失败', 'back', 1, 'forum' );
				}
			}
		}else{
			gotourl( '验证码不一致', 'back', 1, 'forum' );
		}
	}
	
	/**
	 * 编辑提交
	 */
	public function edit_submit(){
		is_login( $this, 'bbs' );
		//id
		$id = $this->input->post('id');
		$from_url = $this->input->post('from_url');
		//分类
		$category = $this->input->post('category');
		//标题
		$title = $this->input->post('title',true);
		//内容
		$content = $this->input->post('content',true);
		//验证码
		$vc = $this->input->post('vc',true);
		//已存在的图片
		$img = $this->input->post('img');
		$captcha = $this->session->userdata('forum');
		if( $vc == $captcha ){
			//验证码通过
			if( empty( $category ) || empty( $title ) || empty( $content ) || empty( $id ) ){
				gotourl( '主要参数不能为空', 'back', 1, 'forum' );
			}else{
				$param = array(
						'type'		=>	$category,
						'title'		=>	$title,
						'context'	=>	$content,
						'user_id'	=>	$_SESSION['uid'],
						'addtime'	=>	time(),
						'review'	=>	0,			//通过验证
				);
				//处理图片问题	
				$res = $this->forum->edit_forum( $param, array( 'id'=>$id ) );
				$this->forum->confirm_image( $content, $id, $img );
				if( $res ){
					gotourl( '编辑成功后，帖子需要重新审核，请耐心等待', base_url().'forum/index/main/', 0, 'forum' );
				}else{
					gotourl( '编辑失败', 'back', 1, 'forum' );
				}
			}
		}else{
			gotourl( '验证码不一致', 'back', 1, 'forum' );
		}
	}
	
	
	/**
	 * 提示页
	 */
	public function tip(){
		$data = array(
			'error'		=>	$this->input->get_post('mess'),
			'gotourl'	=>	$this->input->get_post('from_rul'),
			'sec'		=>	$this->input->get_post('sec'),
			'color'		=>	$this->input->get_post('color'),
			'down'		=>	$this->input->get_post('down'),
		);
		if( empty( $data['down'] ) ){
			$data['filename'] = '';
		}else{
			$n = strrpos( $data['down'], '/' ) + 1;
			$data['filename'] = substr( $data['down'], $n );
		}
		$this->load->view( 'forum/tip.html', $data );
	}
	
	/**
	 * 更改显示设备
	 */
	public function change_live(){
		$code = $this->input->post('code');
		if( empty( $_SESSION['uid'] ) ){
			echo json_encode( array( 'res'=>0, 'info'=>'您已登录超时,请重新登录' ) );
		}else{
			$res = $this->forum->update_live( array( 'id'=>$_SESSION['uid'] ), array( 'forumLive'=>$code ) );
			if( $res ){
				echo json_encode( array( 'res'=>1, 'info'=>'' ) );
			}else{
				echo json_encode( array( 'res'=>0, 'info'=>'修改失败' ) );
			}
		}
	}
	
	
	/**
	 * 验证码
	 */
	public function captcha() {
		$this->load->helper( 'captcha' );
		$code = random_string( 'alpha', 4 );
		$vals = array(
				'word' => strtolower( $code ),
				'img_path' => './captcha/',
				'img_url' => base_url().'captcha/',
				'font_path' => './path/to/fonts/texb.ttf',
				'img_width' => 70,
				'img_height' => 28,
				'expiration' => 7200
		);
		$model = $this->input->get_post('model') ? $this->input->get_post('model') : 1;
		if( $this->nolive == 1 ){
			echo captcha_code( $this, $vals, $model, 'forum' );
		}else{
			return captcha_code( $this, $vals, $model, 'forum' );
		}
	}
	
}