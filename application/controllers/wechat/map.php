<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Map extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model( 'Wx_model', 'wx' );
	}
	
	public function index(){
		$openid = $this->session->userdata( 'openid' );
		$code = $this->input->get('code');
		if( empty( $openid ) && !empty( $code ) ){
			$openid = $this->wx->code_access_token( $code );
			$this->session->set_userdata( 'openid', $openid );
		}	
		//我的地址
		$res = $this->my_gps($openid);
		if ($res){
			$data = array(
				'info'=>array(
					'Latitude'	=>	$res['latitude'],
					'Longitude'	=>	$res['longitude'],
					'Precision'	=>	$res['precision'],
				)
			);
			$list = $this->area_device( $res );
			$data['list'] = $list;
		}else{
			$data = array('list'=>'','info'=>'');
		}
		$this->load->view( 'wechat/map.html', $data );
			
	}
	
	
	private function find_gps( $openid ){
		if( empty($openid) ){
			return false;
		}
		$this->db->select('latitude,longitude,precision');
		$this->db->from('wechat_user');
		$this->db->where( array( 'openid'=>$openid ) );
		$query = $this->db->get();
		$res = $query->row_array();
		return $res;
	}
	
	private function area_device( $mygps ){
		$n = $mygps['latitude'] + 0.1;
		$s = $mygps['latitude'] - 0.1;
		$w = $mygps['longitude'] - 0.1;
		$e = $mygps['longitude'] + 0.1;
		$where = array( 
			'latitude <='	=>	$n,
			'latitude >='	=>	$s,
			'longitude >='	=>	$w,
			'longitude <='	=>	$e,
		);
		$this->db->select('latitude,longitude,precision');
		$this->db->from('wechat_user');
		$this->db->where( array( 'openid'=>$openid ) );
		$query = $this->db->get();
		$res = $query->result_array();
	}
	
}