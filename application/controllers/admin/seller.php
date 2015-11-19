<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seller extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		is_login( $this, 'admin' );
		$this->load->model( 'Seller_model', 'seller' );
		$this->load->helper('html');
		if( $this->input->is_ajax_request() ){
			//ajax访问不加载页头和页尾
		}else{
			$admin = $this->session->userdata('admin');
			$data = array( 'from_url'=>$_SERVER['HTTP_REFERER'], 'admin'=>$admin );
			$this->load->view( 'admin/header.html', $data );
			$this->foot = $this->load->view( 'admin/foot.html', '', true );
		}
	}


	/**
	 * 商家列表
	 */
	public function index(){
		$sellername = $this->input->get('sellername');
		$where = array();
		if( !empty( $sellername ) ){
			$where['sellername'] = $sellername;
		}
		$page = $this->input->get('page');
		$seller_list = $this->seller->seller_list( $where, 15, $page );
		$page_bar = $this->seller->make_bar();
		$this->load->view( 'admin/seller_list.html', array( 'list'=>$seller_list, 'foot'=>$this->foot, 'bar'=>$page_bar ) );
	}

	//编辑
	public function edit(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$this->load->helper( array('form') );
		$info = $this->seller->seller_info( $id );
		$data = array_merge($info,array('action'=>'edit'));
		$this->load->view( 'admin/seller_info.html', $data );
	}

	//添加
	public function add(){
		$this->load->helper( array('form') );
		$default = array('sellername'=>'','phone'=>'','appid'=>'','appkey'=>'', 'status'=>1, 'action'=>'add','id'=>'');
		$this->load->view( 'admin/seller_info.html', $default );
	}

	/**
	 * 更新商家信息
	 */
	public function seller_action(){
		$from_url = $this->input->post('from_url');
		$param = array(
			'sellername'	=>	$this->input->post('sellername'),
			'phone'			=>	$this->input->post('phone'),
			'appid'			=>	$this->input->post('appid'),
			'appkey'		=>	$this->input->post('appkey'),
			'status'		=>	$this->input->post('status'),
		);
		$action = $this->input->post('action');
		if ( $action == 'add' ){
			$only = $this->seller->only_id( $param['appid'] );
			if( $only ){
				$res = $this->seller->seller_add( $param );
			}else{
				gotourl( '此appid已经存在' );
			}
		}elseif ( $action == 'edit' ) {
			$id = $this->input->post('id');
			if( empty( $id ) ){
				gotourl( '重要参数丢失' );
			}
			$only = $this->seller->only_id( $param['appid'], $id );
			if( $only ){
				$res = $this->seller->seller_edit( $param, array('id'=>$id) );
			}else{
				gotourl( '此appid已经存在' );
			}
			
		}
		if( $res == 1 ){
			gotourl( '操作成功', $from_url, 0 );
		}else{
			gotourl( '操作失败' );
		}
	}



	/**
	 * 删除商家
	 */
	public function del_seller(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$this->seller->seller_del( array( 'id' => $id ) );
		redirect( 'admin/seller/index' );
	}


	/**
	 * 更改用户状态
	 */
	public function change_status(){
		$value = $this->input->post('val');
		$id = $this->input->post('id');
		if( empty( $id ) ){
			echo json_encode( array( 'res'=>2, 'info'=>'重要参数丢失' ) );
		}else{
			$this->seller->seller_edit( array('status'=>$value), array('id'=>$id) );
			echo json_encode( array( 'res'=>1, 'info'=>'' ) );
		}
	}

}