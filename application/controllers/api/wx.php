<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wx extends CI_Controller {
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'User_model', 'user' );
		$this->load->model( 'Wx_model', 'wx' );
		$this->wx->fileurl( dirname(__FILE__) );
	}
	
	public function index(){
		$this->wx->listen();
	//	$this->wxapi->responseMsg();
		
	// 	define("TOKEN", "IDreamFactory");	
	// 	$this->load->library( 'Wxapi' );
	//	$this->wxapi->writelog( 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 1, dirname(__FILE__) );
	/*
	 	$post = array();
		foreach ( $_POST as $k=>$p ){
			$post .= $k.'='.$p.',';
		}
	*/	
	//	$this->wxapi->writelog( $post, 2, dirname(__FILE__) );	
	//	$this->wx->valid();
	
	}
	
}