<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forum extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Forum_model', 'forum' );
		$this->load->helper('html');	
		is_login( $this, 'admin' );
		if( $this->input->is_ajax_request() || !empty( $_GET['noheadfoot'] ) ){
			//ajax访问不加载页头和页尾
		}else{
			$admin = $this->session->userdata('admin');
			$data = array( 'from_url'=>$this->input->server('HTTP_REFERER'), 'admin'=>$admin );
			$this->load->view( 'admin/header.html', $data );
			$this->foot = $this->load->view( 'admin/foot.html', '', true );
		}
	}
	
	/**
	 * 帖子列表
	 */
	public function index(){
		$model = $this->input->get('model');	//0所有， 1未审核  2已审核   3置顶  4加精 
		$type = $this->input->get('type');
		$start = $this->input->get('start',true);
		$end = $this->input->get('end',true);
		$user = $this->input->get('user',true);
		$keyword = $this->input->get('keyword',true);
		$page = $this->input->get('page') ? $this->input->get('page') : 1; 
		$per_page = $this->input->get('per_page') ? $this->input->get('per_page') : '15';
		$where = array( 'f.isdel'=>0 );
		$like = array();
		//分类查询
		$ppm = 'model='.$model;
		switch ( $model ){ 
			case 1:
				$where['f.review'] = 0;
				break;
			case 2:
				$where['f.review'] = 1;
				break;
			case 3:
				$where['f.sticky'] = 1;
				break;
			case 4:
				$where['f.elite'] = 1;
				break;
		}
		//开始时间
		if( !empty( $type ) ){
			$ppm .= '&type='.$type;
		}
		//开始时间
		if( !empty( $start ) ){
			$where['f.addtime >='] = strtotime( $start );
			$ppm .= '&start='.$start;
		}
		//结束时间
		if( !empty( $end ) ){
			$where['f.addtime <='] = strtotime( $end );
			$ppm .= '&end='.$end;
		}
		//查看某用户的
		if( !empty( $user ) ){
			//搜索用户
			$this->load->model( 'User_model', 'user' );
			$this->load->database( 'default', TRUE );
			$token = $this->user->user_record( array( 'username'=>$user ), 1 );
			if( empty( $token['id'] ) ){
				$where['f.user_id'] = 0;
			}else{
				$where['f.user_id'] = $token['id'];
			}
			$ppm .= '&user='.$user;
		}
		//查看某关键字为主
		if( !empty( $keyword ) ){
			$like['keyword'] = $keyword;
			$ppm .= '&keyword='.$keyword;
		}

		$list = $this->forum->forum_list( $where, $type, $page, $per_page, 'addtime desc', $like );
		//分页信息
		$page_info = $this->forum->make_bar( 3 );
		$page_bar = $this->forum->make_bar( 1, $ppm );
		$users = $this->forum->forum_users( $list );
		$type_list = $this->forum->getTypes( 2 );
		$data = array( 
			'list'		=>	$list, 
			'model'		=>	$model,
			'start'		=>	$start,
			'end'		=>	$end,
			'user'		=>	$user,
			'keyword'	=>	$keyword,
			'type'		=>	$type, 
			'type_list'	=>	$type_list,
			'pinfo'		=>	$page_info, 
			'bar'		=>	$page_bar, 
			'users'		=>	$users, 
			'foot'		=>	$this->foot 
		);
		$this->load->view( 'admin/forum_index.html', $data );
	}
	
	/**
	 * 回收站列表
	 */
	public function recycle(){
		$keyword = $this->input->get('keyword',true);
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		$per_page = $this->input->get('per_page') ? $this->input->get('per_page') : '15';
		$where = array( 'f.isdel'=>1 );
		$like = array();
		$ppm = '';
		//查看某关键字为主
		if( !empty( $keyword ) ){
			$like['keyword'] = $keyword;
			$ppm .= 'keyword='.$keyword;
		}
		
		$list = $this->forum->forum_list( $where, '', $page, $per_page, 'addtime desc', $like );
		//分页信息
		$page_info = $this->forum->make_bar( 3 );
		$page_bar = $this->forum->make_bar( 1, $ppm );
		//图片信息
		$num = $this->forum->invalid_pic();
		//用户信息
		$users = $this->forum->forum_users( $list );
		$data = array(
				'list'		=>	$list,
				'keyword'	=>	$keyword,
				'pinfo'		=>	$page_info,
				'bar'		=>	$page_bar,
				'users'		=>	$users,
				'number'	=>	$num ? $num : 0,
				'foot'		=>	$this->foot
		);
		$this->load->view( 'admin/forum_recycle.html', $data );
	}
	
	
	/**
	 * 审核
	 */
	public function pass_review(){
		$value = $this->input->post('val');
		$fid = $this->input->post('fid');
		if( empty( $fid ) ){
			echo json_encode( array( 'res'=>2, 'info'=>'重要参数丢失' ) );
		}else{
			$this->forum->update_forum( array('review'=>$value), array('id'=>$fid) );
			echo json_encode( array( 'res'=>1, 'info'=>'' ) );
		}
	}
	
	/**
	 * 特殊处理
	 */
	public function forum_set(){
		$value = $this->input->post('val');
		$fid = $this->input->post('fid');
		$key = $this->input->post('key');
		if( empty( $fid ) ){
			echo json_encode( array( 'res'=>2, 'info'=>'重要参数丢失' ) );
		}else{
			$this->forum->update_forum( array( $key=>$value), array('id'=>$fid) );
			echo json_encode( array( 'res'=>1, 'info'=>'' ) );
		}
	}
	
	
	/**
	 * 删除
	 */
	public function del_forum(){
		$fid = $this->input->get('id');
		if( empty( $fid ) ){
			gotourl( '重要参数丢失!' );
		}
		$res = $this->forum->delete_forum( $fid, 'admin' );
		if( $res == 1 ){
			gotourl( '操作成功', $this->input->server('HTTP_REFERER'), 0 );
		}else{
			gotourl( $res );
		}
	}
	
	
	/**
	 * 完全删除
	 */
	public function del_true(){
		$fid = $this->input->get('id');
		if( empty( $fid ) ){
			gotourl( '重要参数丢失!' );
		}
		$res = $this->forum->delete_true( $fid );
		if( $res == 1 ){
			gotourl( '操作成功', $this->input->server('HTTP_REFERER'), 0 );
		}else{
			gotourl( $res );
		}
	}
	
	/**
	 * 清空无效照片
	 */
	public function killPic(){
		$this->load->database( 'bbs', TRUE );
		$this->db->select('path');
		$this->db->from('forum_image');
		$this->db->where( array( 'send'=>0 ) );
		$query = $this->db->get();
		$pic = $query->result_array();
		if( $pic ){
			foreach ( $pic as $p ){
				@unlink( $p['path'] );
			}
			$this->db->delete( 'forum_image', array( 'send'=>0 ) );
		}
		gotourl( '操作成功', $this->input->server('HTTP_REFERER'), 0 );
	}
	
	
	public function batch_forum(){
		$ids = $this->input->get('id');
		if( empty( $ids ) ){
			gotourl( '重要参数丢失!' );
		}
		$res = $this->forum->delete_batch( $ids );
		if( $res == 1 ){
			gotourl( '操作成功', $this->input->server('HTTP_REFERER'), 0 );
		}else{
			gotourl( $res );
		}
		
	}
	
	
	public function edit(){
		$action = $this->input->get('action');
		$id = $this->input->get('id');
		if( $action == 'delete' && !empty( $id ) ){
			$res = $this->forum->type_action( $action, array( 'id'=>$id ) );
			if( $res ){
				gotourl( '操作成功', base_url('admin/forum/type'), 0 );
			}else{
				gotourl( '操作失败,请检查子分类是否清理完毕' );
			}
		}else{
			gotourl( '主要参数丢失' );
		}
	}
	
	/**
	 * 分类信息
	 */
	public function type(){
		$types = $this->forum->getTypes();
		$this->load->view('admin/forum_type.html', array( 'list' => $types, 'foot'=>$this->foot ) );
	}
	
	/**
	 * 高更改分类
	 */
	public function change_type(){
		$order = $this->input->post('order',true);
		$name = $this->input->post('name',true);
		$neworder = $this->input->post('neworder',true);
		$newforum = $this->input->post('newforum',true);
		$newcatorder = $this->input->post('newcatorder',true);
		$newcat = $this->input->post('newcat',true);
		$already = array();
		$new = array();	
		/* 已存在数据 */
		if( !empty( $name ) ){
			foreach( $name as $k => $nm ){
				$already[$k] = array( 'ascno'=>$order[$k], 'name'=>$nm );
			}
		}
		/* 新板块 */
		if( !empty( $newforum ) ){
			foreach( $newforum as $nk => $nf ){
				foreach ( $nf as $key => $v ) {
					$new[] = array( 'pid'=>$nk, 'ascno'=>$neworder[$nk][$key], 'name'=>$v  );
				}
			}
		}
		/* 新分区 */
		if( !empty( $newcat ) ){
			foreach( $newcat as $ck => $nc ){
				$new[] = array( 'pid'=>0, 'ascno'=>$newcatorder[$ck], 'name'=>$nc  );
			}
		}	
		$res = $this->forum->makeType( $already, $new );
		if( $res ){
			//成功
			gotourl( '操作成功', base_url('admin/forum/type'), 0 );
		}else{
			//失败
			gotourl( '操作失败' );
		}
	}

}