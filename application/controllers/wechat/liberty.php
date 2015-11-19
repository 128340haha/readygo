<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Liberty extends CI_Controller {
	private $base_view = 'skin/';
	public function __construct() {
		parent::__construct();
		$this->load->model( 'Wx_model', 'wx' );
		$id = $this->input->get_post('id');
		if( empty( $id ) ){
			$openid = $this->session->userdata( 'openid' );
			$openid = '';
			if( empty( $openid ) ){
				//操作超时
				redirect( base_url('wechat/ucenter/error') );
			}
			$skin = $this->wx->skin( $openid );
			if( empty( $skin ) ){
				//跳转回index
				redirect( base_url('wechat/ucenter/index') );
			}else{
				$this->skin_path = $skin;
			}
		}else{
			$this->skin_path = $id;
		}
	}
	
	/**
	 * 自由模板设定
	 * @param unknown $method
	 */
	function _remap($method){
		$url = base_url('wechat/liberty');
		if ($method == 'index'){
			$this->index( $url );
		}else{
			//判断文件是否存在
			$this->load->helper('directory');
			$map = directory_map('./application/views/skin/'.$this->skin_path);
			if( in_array( $method.'.html', $map ) ){
				//这里是客户自由设定模板
				$data = array( 'url'=>$url );
				$this->load->view( $this->base_view.$this->skin_path.'/'.$method.'.html', $data );
			}else{
				$data = array( 'error'=>'找不到您设定的模板' );
				$this->load->view( 'wechat/error.html', $data );
			}
		}
	}
	
	/**
	 * 默认入口
	 * @param unknown $url
	 */
	public function index( $url ){
		$data = array( 'url'=>$url );
		$this->load->view( $this->base_view.$this->skin_path.'/index.html', $data );
	}
	
	
	
	
}