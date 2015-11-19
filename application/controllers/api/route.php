<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Route extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Route_model', 'route' );
		session_start();
	}
	
	/**
	 * 游戏列表
	 * @page 当前页
	 * @per_page 
	 */
	public function game_list(){
		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		$page = $this->input->get('page',true);
		$pre_page = $this->input->get('pre_page') ? $this->input->get('pre_page',true) : 10;
		$game_list = $this->route->game_list( '', $pre_page, $page );
		$page_bar = $this->route->make_bar( 2 );
		echo json_encode( array( 'res'=>1, 'data'=>$game_list, 'bar'=>$page_bar ) );
	}
	
	
	public function download(){
		$file_name = $this->input->get('file',true);
		$file_dir = base_url().'uploads/route/';
		$file = @fopen($file_dir . $file_name,"r");
		if (!$file) {
			$this->output->set_header('Content-Type: application/json; charset=utf-8');
			echo json_encode( array( 'res'=>0, 'data'=>'找不到文件' ) );
		} else {
			$this->output->set_header('Content-Type: application/octet-stream; charset=utf-8');
			$this->output->set_header("Content-Disposition: attachment; filename=" . $file_name);
			while ( !feof( $file ) ){
				echo fread($file,50000);
			}
			fclose($file);
		}
	}
}
	