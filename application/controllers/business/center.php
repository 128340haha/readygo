<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Center extends CI_Controller {
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Device_model', 'device' );
		$this->load->helper('html');
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
		is_login( $this, 'business' );
	}
	
	/**
	 * 主页
	 */
	public function index(){	
		$this->load->view( 'business/main.html' );
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
		$this->load->view( 'business/tip.html', $data );
	}
	
	
}