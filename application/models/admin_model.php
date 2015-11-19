<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_model extends CI_Model{
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	public function admin_info( $where, $level = 1 ){
		//查询字段
		if( $level == 1 ){
			$field = 'id,admin';
		}elseif ( $level == 2 ){
			$field = 'id,admin,login_time,login_ip,last_time,last_ip,priv,error';
		}elseif( $level == 3 ){
			$field = 'id,admin,password,salt';
		} elseif( $level == 4 ){
			$field = '*';
		}
		$this->db->select( $field );
		$this->db->where( $where );
		$query = $this->db->get('admin');
		$row = $query->row_array();
		return $row;
	}
	
	/**
	 * 管理信息更新
	 * @param unknown $data
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function admin_edit( $data, $where ){
		if( empty( $where ) || empty( $data ) ){
			return false;
		}
		$str = $this->db->update('admin', $data, $where);
		return $str;
	}
	
	/**
	 * 添加
	 * @param string $data
	 * @return boolean|unknown
	 */
	public function admin_add( $data = '' ){
		if( empty( $data ) ){
			return false;
		}
		$str = $this->db->insert('admin', $data);
		return $str;
	}
	
	/**
	 * 删除
	 * @param string $where
	 * @return boolean|unknown
	 */
	public function admin_delete( $where = '' ){
		if( empty( $where ) ){
			return false;
		}
		$str = $this->db->delete('admin', $where);
		return $str;
	}
	
	
	
/**
	 * 管理员列表
	 * @param unknown $where
	 * @param number $level
	 */
	public function admin_list( $where, $per_page = 10, $page = 1 ){
		//分页查询
		$this->load->model('Page_model', 'page');
		//总数
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->from('admin');
		$number = $this->db->count_all_results();
		//获取分页偏移
		$offset = $this->page->make_page( $number, $per_page, '', $page );
		//分页结果
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->select( 'id,admin,login_time,login_ip,last_time,last_ip,priv' );
		$this->db->from('admin');
	//	$this->db->order_by("id", "desc"); 
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		$res = $query->result_array();
		return $res;
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
	
	
/*====================================================================*/	

	public function admin_login( $param, $dataType = 1 ){
		$this->load->library( 'form_validation' );
		$this->dataType = $dataType;
		//参数传递验证
		if( $this->form_validation->run( 'admin' ) ){
			//验证码验证
			$error = $this->session->userdata('error');
			if( $error > 3 ){
				$vpass = false;
			}else{
				$vpass = true;
			}
			if( $vpass || $this->session->userdata('verify') == $param['verify'] ){
				//验证码通过并且销毁
				$this->session->unset_userdata('verify');
				//查询用户信息
				$info = $this->admin_info( array('admin'=>$param['admin'] ), 4 );
				//账号是否存在
				if( !empty( $info['id'] ) ){
					//密码验证
					$this->load->library( 'Password' );
					//加密匹配
					$mypass = $this->password->adminPass( $param['admin'], $param['password'], $info['salt'] );
					if( $mypass == $info['password'] ){
						$login_time = time();
						$login_ip = $this->input->ip_address();
						//验证通过存入session
						$new_data = array(
								'aid'			=>	(int)$info['id'],
								'admin'			=>	$param['admin'],			
								'priv'			=>	$info['priv'],
						);
						$this->session->set_userdata($new_data);
						//更新数据库登录信息
						$data = array(
								'login_time'	=> $login_time,
								'login_ip' 		=> $login_ip,
								'last_time'		=> $info['login_time'],
								'last_ip'		=> $info['login_ip'],
						);
						$where = array( "id" => $info['id'] );
						$this->admin_edit( $data, $where );
						//写入日志
						//提示成功
						if( $dataType == 1 ){
							return 1;
						}else{
							return array( 'res'=>1, 'info'=>$new_data );
						}
					}else{
						return $this->reback('密码错误');
					}
				}else{
					return $this->reback('账号不存在或者已禁用');
				}
			}else{
				return $this->reback('验证码错误');
			}
		}else{
			return $this->reback('提交参数异常,请勿非法操作');
		}
	}
	
	/**
	 * 管理员注册
	 * @param unknown $param
	 * @return Ambigous <unknown, multitype:number, multitype:number unknown >|number|multitype:number multitype:
	 */
	public function admin_register( $param = array(), $dataType = 1 ){
		$this->dataType = $dataType;
		$this->load->library( 'form_validation' );
		//参数传递验证
		if( $this->form_validation->run( 'admin' ) ){
			//密码验证
			if( $param['password'] == $param['ckpwd'] ){
				//密码构建
				$this->load->library( 'password' );
				//随机校验码
				$salt = random_string( 'alpha', 6 );
				//加密
				$mypass = $this->password->adminPass( $param['admin'], $param['password'], $salt );
				$data_main = array(
					'admin' 	=> $param['admin'],
					'password' 	=> $mypass,
					'salt' 		=> $salt,	//注册随机码
					'priv'		=> $param['priv']
				);
				//插入账户表
				$doll = $this->admin_add($data_main);
				if( $doll ){
					return 1;
				}else{
					return $this->reback('注册失败');
				}
			}else{
				return $this->reback('两次密码不一样');
			}
		}else{
			return $this->reback('参数格式不对,请按照提示填写');
		}
	}
	
	
	public function ck_admin( $admin = '' ){
		if( empty( $admin ) ){
			return 'false';
		}
		$this->db->where( array( 'admin'=>$admin ) );
		$this->db->from( 'admin' );
		$number = $this->db->count_all_results();
		if( $number > 0 ){
			//已存在
			return 'false';
		}else{
			//可以注册
			return 'true';
		}
	}
	
	
	/**
	 * 返回
	 * @param unknown $info
	 * @return unknown|multitype:number unknown
	 */
	private function reback( $info ){
		if( $this->dataType == 1 ){
			return $info;
		}elseif( $this->dataType == 2 ){
			return array( 'res'=>0, 'info'=>$info );
		}
	}
	
}