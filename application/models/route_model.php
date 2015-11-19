<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Route_model extends CI_Model{
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
	public function game_list( $where, $per_page = 10, $page = 1 ){
		//分页查询
		$this->load->model('Page_model', 'page');
		//总数
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->from('router_game');
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
		$this->db->from('router_game');
		$this->db->order_by("ascno"); 
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		$res = $query->result_array();
		return $res;
	}

	//商家信息
	public function game_info( $id='' ){
		if( empty( $id ) ){
			return false;
		}
		$this->db->from('router_game');
		$this->db->where( array('id'=>$id) );
		$query = $this->db->get();
		$row = $query->row_array();
		return $row;
	}

	//添加
	public function game_add( $param ){
		if( !empty( $param ) ){
			$res = $this->db->insert('router_game',$param);
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
	public function game_del( $where, $path ){
		if( empty( $where ) ){
			return false;
		}
		$info = $this->game_info( $where['id'] );
		//删除游戏
		$str = $this->db->delete('router_game', $where);
		if( $str ){
			//删除图片以及上传文件
			@unlink( $path.'/'.$info['image'] );
			if( !empty( $info['resource'] ) ){
				@unlink( $path.'/'.$info['resource'] );
			}
		}
		return $str;
	}

	//编辑游戏
	public function game_edit( $data, $where, $path ){
		if( empty( $where ) || empty( $data ) ){
			return false;
		}
		$info = $this->game_info( $where['id'] );
		$str = $this->db->update('router_game', $data, $where);
		if( $str ){
			if( !empty( $data['image'] ) ){
				@unlink( $path.'/'.$info['image'] );
			}
			if( !empty( $data['resource'] ) ){
				@unlink( $path.'/'.$info['resource'] );
			}
		}
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