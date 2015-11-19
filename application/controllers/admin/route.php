<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Route extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		is_login( $this, 'admin' );
		/* 上传删除myci */
		$this->upload_path = $_SERVER['DOCUMENT_ROOT'].'/myhome/uploads/route';
		$this->load->model( 'Route_model', 'route' );
		$this->load->helper('html');
		if( $this->input->is_ajax_request() ){
			//ajax访问不加载页头和页尾
		}else{
			$admin = $this->session->userdata('admin');
			$HTTP_REFERER = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
			$data = array( 'from_url'=>$HTTP_REFERER, 'admin'=>$admin );
			$this->load->view( 'admin/header.html', $data );
			$this->foot = $this->load->view( 'admin/foot.html', '', true );
		}
	}


	/**
	 * 游戏列表
	 */
	public function index(){
		$name = $this->input->get('name');
		$where = array();
		if( !empty( $name ) ){
			$where['name'] = $name;
		}
		$page = $this->input->get('page');
		$game_list = $this->route->game_list( $where, 5, $page );
		$page_bar = $this->route->make_bar();
		$this->load->view( 'admin/game_list.html', array( 'list'=>$game_list, 'foot'=>$this->foot, 'bar'=>$page_bar ) );
	}

	//编辑
	public function edit(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$this->load->helper( array('form') );
		$info = $this->route->game_info( $id );
		$data = array_merge($info,array('action'=>'edit'));
		$this->load->view( 'admin/game_info.html', $data );
	}

	//添加
	public function add(){
		$this->load->helper( array('form') );
		$default = array('name'=>'','image'=>'','desc'=>'','path'=>'','ascno'=>5, 'status'=>0, 'action'=>'add','id'=>'');
		$this->load->view( 'admin/game_info.html', $default );
	}

	/**
	 * 更新商家信息
	 */
	public function game_action(){
		$from_url = $this->input->post('from_url');
		$param = array(
			'name'			=>	$this->input->post('name'),
			'desc'			=>	$this->input->post('desc'),
			'status'		=>	$this->input->post('status'),
			'ascno'			=>	$this->input->post('ascno'),
		);
		//获取操作标示
		$action = $this->input->post('action');
		//上传参数
		$config = array(
			'upload_path' 	=> $this->upload_path,
			'allowed_types' => 'jpg|jpeg|png|gz|tar|zip'
		);
		if( $action == 'add' ){
			//添加肯定要有图片
			$file_uploads = array('image');
		}elseif ( $action == 'edit' && $_FILES['image']['type'] ){
			//修改了图片
			$file_uploads = array('image');
		}else{
			//只修改文字信息
			$file_uploads = array();
		}
		if( $_FILES['resource']['type'] ){
			array_push( $file_uploads,'resource');
		}
		
		//初始化upload函数
		$this->load->library('upload', $config);
		$upload = $this->upload->do_uploades( $file_uploads, true );
		if ( $action == 'add' ){
			if( empty( $upload ) ){
				$error = $this->upload->display_errors();
				gotourl( $error );
			}else{
				//文件上传
				$file_data = $this->upload->data_list();
				$param['image'] = $file_data['image']['file_name'];
				if( $_FILES['resource']['type'] ){
					$param['resource'] = $file_data['resource']['file_name'];
				}
			}
			$res = $this->route->game_add( $param );
		}elseif ( $action == 'edit' ) {
			if( !empty( $upload ) ){
				$file_data = $this->upload->data_list();
				//上传图片则替换图片
				if( $_FILES['image']['type'] ){
					$param['image'] = $file_data['image']['file_name'];
				}
				//上传文件则替换文件
				if( $_FILES['resource']['type'] ){
					$param['resource'] = $file_data['resource']['file_name'];
				}
			}
			
			$id = $this->input->post('id');
			if( empty( $id ) ){
				gotourl( '重要参数丢失' );
			}
			$res = $this->route->game_edit( $param, array('id'=>$id), $this->upload_path );	
		}
		if( $res == 1 ){
			gotourl( '操作成功', base_url().'admin/route/index', 0 );
		}else{
			gotourl( '操作失败' );
		}
	}



	/**
	 * 删除商家
	 */
	public function del_game(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$this->route->game_del( array( 'id' => $id ), $this->upload_path );
		gotourl( '删除成功', base_url().'admin/route/index', 0 );
	}


	/**
	 * 更改用户状态
	 */
	public function change_no(){
		$value = $this->input->post('val');
		$id = $this->input->post('id');
		if( empty( $id ) ){
			echo json_encode( array( 'res'=>2, 'info'=>'重要参数丢失' ) );
		}else{
			$this->route->game_edit( array('ascno'=>$value), array('id'=>$id) );
			echo json_encode( array( 'res'=>1, 'info'=>'' ) );
		}
	}

}