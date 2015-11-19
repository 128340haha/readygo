<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {
	public function __construct() { 
		parent::__construct(); 
		session_start();
	}
	
	public function index(){
		$data = array(
			'savePath' 		=> getcwd().'/uploads/head', 	 //图片存储路径
			'savePicName' 	=> time().rand(1000, 9999),  	//图片存储名称	
		);
		$this->load->view( 'forum/test.html', $data );
	}
	
	public function upload(){	
	/*
		$savePath = getcwd().'/uploads/head/';  //图片存储路径
		$showPath = base_url().'uploads/head/';  //图片显示路径
		$savePicName = time().random_string( 'alnum', 4 );  //图片存储名称
		$file_src = $savePath.$savePicName."_src.jpg";
		$filename162 = $savePath.$savePicName."_162.jpg"; 
		$filename48 = $savePath.$savePicName."_48.jpg"; 
		$filename20 = $savePath.$savePicName."_20.jpg";    
		
		$src=base64_decode($_POST['pic']);
		$pic1=base64_decode($_POST['pic1']);   
		$pic2=base64_decode($_POST['pic2']);  
		$pic3=base64_decode($_POST['pic3']);  
		
		if($src) {
			file_put_contents($file_src,$src);
		}
		
		file_put_contents($filename162,$pic1);
		file_put_contents($filename48,$pic2);
		file_put_contents($filename20,$pic3);
		
		$rs['status'] = 1;
		$rs['picUrl'] = $showPath.$savePicName;
		
		print json_encode($rs);
	*/
		$rs = array();
		
		switch($_GET['action']){
		
			//上传临时图片
			case 'uploadtmp':
				$file = 'uploadtmp.jpg';
				@move_uploaded_file($_FILES['Filedata']['tmp_name'], $file);
				$rs['status'] = 1;
				$rs['url'] = './php/' . $file;
				break;
		
				//上传切头像
			case 'uploadavatar':
				$input = file_get_contents('php://input');
				$data = explode('--------------------', $input);
				@file_put_contents('./avatar_1.jpg', $data[0]);
				@file_put_contents('./avatar_2.jpg', $data[1]);
				$rs['status'] = 1;
				break;
		
			default:
				$rs['status'] = -1;
		}
		
		print json_encode($rs);
	}
	
}