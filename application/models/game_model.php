<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Game_model extends CI_Model{
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 游戏数据总计
	 * @param unknown $where
	 * @param string $model
	 * @return unknown
	 */
	public function game_info( $where, $model = 'list' ){
		$this->db->where( $where );
		$query = $this->db->get('game_user');
		if( $model == 'list' ){
			$res = $query->result_array();
		}elseif( $model == 'info' ){
			$res = $query->row_array();
		}
		return $res;
	}
	
	
	public function game_list( $where = '', $page = 1, $per_page = 15 ){
		if( !empty( $where ) ){
			$this->db->where( $where );
		}
		$query = $this->db->get('game_type');
		return $query->result_array();
	}
	
	
	/**
	 * 数据记录
	 * @param unknown $where
	 * @return unknown
	 */
	public function record_list( $where ){
		$this->db->where( $where );
		$query = $this->db->get('game_record');
		$res = $query->result_array();
		return $res;
	}
	
	/**
	 * 写入记录
	 * @param unknown $param
	 */
	public function add_record( $param ){
		if( empty( $param ) ){
			return '参数丢失';
		}
		$str = $this->db->insert('game_record', $param);
		$insert_id = $this->db->insert_id();
		if( $str ){
			$res = $this->change_game( 'add', $param['user_id'], $param['coin'], $param['electricity'] );
			if( $res == 1 ){
				return 1;
			}else{
				//更改主表失败则删除 刚刚加入的数据
				$this->del_record( array( 'id'=>$insert_id ) );
				return $res;
			}
		}else{
			return '写入失败';
		}
	}
	
	/**
	 * 删除
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function del_record( $where ){
		if( empty( $where['id'] ) ){
			return false;
		}
		$str = $this->db->delete('game_record', $where);
		if( $str ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 更新游戏主表信息
	 * @param unknown $action
	 * @param unknown $coin
	 * @param unknown $electricity
	 */
	public function change_game( $action, $user_id, $coin, $electricity ){
		//where条件公用
		$this->db->where( array('user_id'=>$user_id) );
		if( $action == 'add' ){
			//增加
			$this->db->set('coin', 'coin+'.$coin, FALSE);
			$this->db->set('electricity', 'electricity+'.$electricity, FALSE);
			$str = $this->db->update('game_user');
		}elseif( $action == 'reduce' ){
			//减少
			$info = $this->game_info( array( 'user_id'=>$user_id ), 'info' );
			if( $info['coin'] < $coin ){
				return '您的金币不足';
			}else{
				$this->db->set('coin', 'coin-'.$coin, FALSE);
				$str = $this->db->update('game_user');
			}
		}else{
			return '操作参数丢失';
		}
		if( $str ){
			return 1;
		}else{
			return '更新失败';
		}
		
	}
	
	
/*******************************功能函数************************************/
	
	public function check_data( $data, $type ){
		$data = (int)$data;
		switch ( $type ){
			case 'coin':
				if( $data < 0 ){
					$data = 0;
				}elseif ( $data > 9999 ){
					$data = 9999;
				}
				break;
			case 'electricity':
				if( $data < 0 ){
					$data = 0;
				}elseif ( $data > 9999 ){
					$data = 9999;
				}
				break;
		}
		return $data;
	}
	
	
}
	
?>