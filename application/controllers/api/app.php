<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 手机app相关设定
 * @author Shaka
 *
 */
class App extends CI_Controller {
	public function __construct() { 
		parent::__construct(); 
		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		//上传的时候注意去掉myci
		$this->upload_path = $_SERVER['DOCUMENT_ROOT'].'/myhome/uploads/pack';
	}

	/**
	 * app版本列表
	 */
	public function app_list(){
		$prepage = $this->input->get('pre_page') ? $this->input->get('pre_page') : 10;
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		$this->load->model('Page_model', 'page');
		//总数
		$this->db->from('app_level');
		$number = $this->db->count_all_results();
		//获取分页偏移
		$offset = $this->page->make_page( $number, $prepage, '', $page );
		//分页结果
		$this->db->select('*');
		$this->db->from('app_level');
		$this->db->order_by( 'level desc' );
		$this->db->limit( $prepage, $offset );
		$query = $this->db->get();
		$res = $query->result_array();
		$page_bar = $this->page->page_info();
		echo json_encode( array( 'res'=>1, 'list'=>$res, 'bar'=>$page_bar ) );
	}
	
	/**
	 * 添加新版本
	 */
	public function app_add(){
		$level = $this->input->post('level');
		$log = $this->input->post('log');
		//上传参数
		$config = array(
			'upload_path' 	=> $this->upload_path,
			'allowed_types' => 'ipa|deb|apk'
		);	
		//初始化upload函数
		$this->load->library('upload', $config);	 
		if( !is_numeric( $level ) ){
			echo return_back( array('res'=>0,'info'=>'版本信息格式不对') );
		}else{
			//验证唯一性
			$this->db->where(array('level'=>$level));
			$this->db->from('app_level');
			$number = $this->db->count_all_results();
			if( $number > 0 ){
				echo return_back( array('res'=>0,'info'=>'该版本已经存在') );
			}else{
				//构建
				$upload = $this->upload->do_upload();
				if( empty( $upload ) ){
					$error = $this->upload->display_errors();
					echo return_back( array('res'=>0,'info'=>$error) );
				}else{
					//文件上传
					$file_data = $this->upload->data();
					//组合参数
					$param = array(
						'level'		=>	$level,
						'log'		=>	$log,
						'path'		=>	$config['upload_path'].'/'.$file_data['file_name'],		//文件路径
						'src'		=>	base_url('uploads/pack').'/'.$file_data['file_name'],	//网络路径
						'addtime'	=>	time()
					);
					$str = $this->db->insert( 'app_level', $param );
					if( $str ){
						echo return_back( array('res'=>1,'info'=>'') );
					}else{
						@unlink( $file_data['full_path'] );
						echo return_back( array('res'=>0,'info'=>'添加失败') );
					}
				}
			}
		}
	}
	
	/**
	 * 修改
	 */
	public function app_edit(){
		$id = $this->input->post('id');
		$level = $this->input->post('level');
		$log = $this->input->post('log');
		if( empty( $id ) ){
			echo return_back( array('res'=>0,'info'=>'id不能为空') );
		}else{
			//上传参数
			$config = array(
				'upload_path' 	=> $this->upload_path,
				'allowed_types' => 'ipa|deb|apk'
			);
			//初始化upload函数
			$this->load->library('upload', $config);
			if( !is_numeric( $level ) ){
				echo return_back( array('res'=>0,'info'=>'版本信息格式不对') );
			}else{
				//验证唯一性
				$this->db->where(array('level'=>$level,'id !='=>$id));
				$this->db->from('app_level');
				$number = $this->db->count_all_results();
				if( $number > 0 ){
					echo return_back( array('res'=>0,'info'=>'该版本已经存在') );
				}else{
					//原始数据
					$this->db->where( array( 'id' => $id ) );
					$this->db->select('path');
					$query = $this->db->get('app_level');
					$ori = $query->row_array();
					//构建
					$upload = $this->upload->do_upload();
					if( empty( $upload ) ){
						$error = $this->upload->display_errors();
						echo return_back( array('res'=>0,'info'=>$error) );
					}else{
						//文件上传
						$file_data = $this->upload->data();
						//组合参数
						$param = array(
							'level'		=>	$level,
							'log'		=>	$log,
							'path'		=>	$config['upload_path'].'/'.$file_data['file_name'],		//文件路径
							'src'		=>	base_url('uploads/pack').'/'.$file_data['file_name'],	//网络路径
							'addtime'	=>	time()
						);
						$str = $this->db->update( 'app_level', $param, array('id'=>$id) );
						if( $str ){
							//删除原始数据
							@unlink( $ori['path'] );
							echo return_back( array('res'=>1,'info'=>'') );
						}else{
							@unlink( $file_data['full_path'] );
							echo return_back( array('res'=>0,'info'=>'修改失败') );
						}
					}
				}
			}
		}
	}
	
	/**
	 * 删除版本信息
	 */
	public function app_delete(){
		$id = $this->input->post('id');
		if( empty( $id ) ){
			echo return_back( array('res'=>0,'info'=>'id丢失') );
		}else{
			$this->db->where( array( 'id' => $id ) );
			$this->db->select('path');
			$query = $this->db->get('app_level');
			$info = $query->row_array();		
			$str = $this->db->delete( 'app_level', array('id'=>$id) );
			if( $str ){
				//删除数据
				@unlink( $info['path'] );
				echo return_back( array('res'=>1,'info'=>'') );
			}else{
				echo return_back( array('res'=>0,'info'=>'删除失败') );
			}
		}
	}
	
}