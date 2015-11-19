<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
	}
	
	/**
	 * 登录验证
	 */
	public function index(){
		$data = array(
			'username'	=>	'',
			'password'	=>	'',
			'ckpwd'		=>	'',
			'action'	=>	site_url( 'api/device/device_bind' )
		);
		$this->load->view( 'api/test.html', $data );
	}
	

	public function download(){
		$m = $this->input->get('m');
		if ( $m ) {
			switch ( $m ) {
				case 1:
					$file = base_url().'files/test/md5sum';
					break;
				case 2:
					$file = base_url().'files/test/root_uImage_noweb';
					break;
				case 3:
					$file = base_url().'files/test/version';
					break;
			}
			header("Content-type: application/octet-stream");
		    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
		    header("Content-Length: ". filesize($file));
		    readfile($file);
		}
		$this->load->view( 'api/download.html' );
	}

}