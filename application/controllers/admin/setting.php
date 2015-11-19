<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Setting extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		is_login( $this, 'admin' );
		$this->load->helper('html');
		$admin = $this->session->userdata('admin');
		$this->load->view( 'admin/header.html', array( 'admin'=>$admin ) );
		$this->foot = $this->load->view( 'admin/foot.html', '', true );
	}
	
	/**
	 * 后台模块欢迎页
	 */
	public function index(){
		is_login( $this, 'admin' );
		$this->load->helper('html');		
		$this->load->view( 'admin/index.html', array( 'foot'=>$this->foot ) );
	}
	
	
	public function tip(){
		$error = $this->input->get_post('mess');
		$gotourl = $this->input->get_post('from_rul');
		$color = $this->input->get_post('color');
		$sec = $this->input->get_post('sec');
		$down = $this->input->get_post('down');
		if( empty( $down ) ){
			$filename = '';
		}else{
			$n = strrpos( $down, '/' ) + 1;
			$filename = substr( $down, $n );
		}
		$data = array( 'error'=>$error, 'gotourl'=>$gotourl, 'sec'=>$sec, 'color'=>$color, 'down'=>$down, 'filename'=>$filename, 'foot'=>$this->foot );
		$this->load->view( 'admin/tip.html', $data );
	}
	
	
	
}