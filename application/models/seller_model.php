<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seller_model extends CI_Model{
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		$this->load->database( 'default', TRUE );
	}

	/**
	 * 用户列表
	 * @param unknown $where
	 * @param number $level
	 */
	public function seller_list( $where, $per_page = 10, $page = 1 ){
		//分页查询
		$this->load->model('Page_model', 'page');
		//总数
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->from('seller');
		$number = $this->db->count_all_results();
		//获取分页偏移
		$offset = $this->page->make_page( $number, $per_page, '', $page );
		//分页结果
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->select( '*' );
		$this->db->from('seller');
		$this->db->order_by("id", "desc"); 
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		$res = $query->result_array();
		return $res;
	}

	#商家信息
	public function seller_info( $id='' ){
		if( empty( $id ) ){
			return false;
		}
		$this->db->from('seller');
		$this->db->where( array('id'=>$id) );
		$query = $this->db->get();
		$row = $query->row_array();
		return $row;
	}
	
	//appid唯一性
	public function only_id( $appid = '', $id = '' ){
		if ( $appid ){
			$where = array('appid'=>$appid);
			if ( !empty( $id ) ){
				$where['id !='] = $id;
			}
			$this->db->select('id');
			$this->db->from('seller');
			$this->db->where( $where );
			$query = $this->db->get();
			$row = $query->row_array();
			if ( $row && $row['id'] ){
				return false;
			}else{
				return true;	
			}
		}else{
			return false;
		}
	}
	

	//添加
	public function seller_add( $param ){
		if( !empty( $param ) ){
			$res = $this->db->insert('seller',$param);
			if ($res) {
				return 1;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}


	/**
	 * 删除
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function seller_del( $where ){
		if( empty( $where ) ){
			return false;
		}
		//删除商家
		$str = $this->db->delete('seller', $where);
		return $str;
	}

	#ajax编辑
	public function seller_edit( $data, $where ){
		if( empty( $where ) || empty( $data ) ){
			return false;
		}
		$str = $this->db->update('seller', $data, $where);
		return $str;
	}


	/**
	 * 获取分页信息
	 * @param number $mode
	 * @return unknown
	 */
	public function make_bar( $model = 1 ){
		$this->load->model('Page_model', 'page');
		if( $model == 1 ){
			$res = $this->page->page_bar();
		}elseif ( $model == 2 ){
			$res = $this->page->page_info();
		}
		return $res;
	}


}