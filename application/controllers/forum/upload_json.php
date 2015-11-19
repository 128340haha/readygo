<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upload_json extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
		$this->upload_path = $_SERVER['DOCUMENT_ROOT'].'/myhome/uploads/forum/'.date('Ym');
		$this->upload_src = 'uploads/forum/'.date('Ym');
		if( !is_dir( $this->upload_path ) ){
			mkdir( $this->upload_path );
		}
		header('Content-type: application/json; charset=UTF-8');
		is_login( $this, 'image' );
	}
	
	//文件上传
	public function index(){
		//上传参数
		$config = array(
			'upload_path' 	=> $this->upload_path,
			'file_name'		=> time().random_string( 'alnum', 6 ),
			'max_size'		=> 500,
			'allowed_types' => 'jpg|jpeg|png'
		);
		//初始化upload函数
		$this->load->library('upload', $config);
		//验证唯一性
		$upload = $this->upload->do_upload('imgFile');
		if( empty( $upload ) ){
			$error = $this->upload->display_errors();
			echo $error;
		}else{
			//文件上传
			$file_data = $this->upload->data();
			//组合参数
			$param = array(
				'user_id'	=>	$_SESSION['uid'],
				'path'		=>	$this->upload_path.'/'.$file_data['file_name'],		//文件路径
				'src'		=>	'/'.$this->upload_src.'/'.$file_data['file_name'],	//网络路径
				'addtime'	=>	time()
			);
			$this->load->database( 'bbs', true );
			$this->db->insert( 'forum_image', $param );
			$this->load->library('Services_JSON', '', 'json');
			echo $this->json->encode(array('error' => 0, 'url' => base_url().$this->upload_src.'/'.$file_data['file_name']));
		}
	}
}