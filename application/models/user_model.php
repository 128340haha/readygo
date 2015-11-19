<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model{
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		$this->load->database( 'default', TRUE );
		$this->default_face = base_url().'assets/user/images/default';
	}
	
	private function getBaseUrl()	{
		$basePath = $_SERVER['SCRIPT_FILENAME'];
		$basePath = substr( $basePath, 0, strrpos($basePath, '/' ) + 1 );
		$basePath = str_replace( '/', DIRECTORY_SEPARATOR, $basePath );
		return $basePath;
	}
	
	/**
	 * 头像
	 * @param string $uid
	 * @return string
	 */
	public function user_face( $uid = '' ){
		if( empty( $uid ) ){
			return array( 'src'=>$this->default_face, 'path'=>'' );
		}
		$this->db->select('path');
		$this->db->from('user_face');
		$this->db->where( array( 'id'=>$uid ) );
		$query = $this->db->get();
		$res = $query->row_array();
		if( empty( $res ) ){
			return array( 'src'=>$this->default_face, 'path'=>'' );
		}else{
			return array( 'src'=>base_url().$res['path'], 'path'=>$this->getBaseUrl().$res['path'] );;
		}
	}
	
	/**
	 * 更新头像
	 * @param string $uid
	 * @param string $path
	 * @return boolean|string
	 */
	public function save_face( $uid = '', $path = '' ){
		if( empty( $uid ) || empty( $path ) ){
			return false;
		}
		$this->db->select('path');
		$this->db->from('user_face');
		$this->db->where( array( 'id'=>$uid ) );
		$query = $this->db->get();
		$res = $query->row_array();
		if( empty( $res ) ){
			$param = array( 'id'=>$uid, 'path'=>$path );
			$str = $this->db->insert( 'user_face', $param );
			return str;
		}elseif( $res['path'] == $path ){
			return true;
		}else{
			$param = array( 'path'=>$path );
			$str = $this->db->update( 'user_face', $param, array( 'id'=>$uid ) );
			if( $str ){
				@unlink( $this->getBaseUrl().$res['path'].'_middle.jpg' );
				@unlink( $this->getBaseUrl().$res['path'].'_small.jpg' );
			}
			return $str;
		}
	}
	
	
	/**
	 * 查询个人账户记录
	 * @param unknown $where	数组
	 * @param number $level		参数等级 1.id与用户名,2.非密码信息,3.密码相关,4.全部
	 * @return boolean|unknown
	 */
	public function user_record( $where, $level = 2 ){
		if( empty( $where ) ){
			return false;
		}
		//构建查询条件
		$w = reshuffle( $where );
		//查询字段
		if( $level == 1 ){
			$field = 'id,username';
		}elseif ( $level == 2 ){
			$field = 'id,username,reg_time,reg_ip,login_time,login_ip,last_time,last_ip,status';
		}elseif( $level == 3 ){
			$field = 'id,username,password,salt';
		} elseif( $level == 4 ){
			$field = '*';
		}
		$query = $this->db->query('SELECT '.$field.' FROM user WHERE '.$w.' limit 1');
		$row = $query->row_array();
		return $row;
	}
	
	
	/**
	 * 用户列表
	 * @param unknown $where
	 * @param number $level
	 */
	public function user_list( $where, $per_page = 10, $level = 2, $page = 1 ){
		if( $level == 1 ){
			$field = 'id,username';
		}elseif ( $level == 2 ){
			$field = 'id,username,reg_time,reg_ip,login_time,login_ip,last_time,last_ip,status';
		}elseif( $level == 3 ){
			$field = 'id,username,password,salt';
		} elseif( $level == 4 ){
			$field = '*';
		}
		//分页查询
		$this->load->model('Page_model', 'page');
		//总数
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->from('user');
		$number = $this->db->count_all_results();
		//获取分页偏移
		$offset = $this->page->make_page( $number, $per_page, '', $page );
		//分页结果
		if( $where ){
			foreach( $where as $k=>$v ){
				$this->db->like( $k, $v, 'both' );
			}
		}
		$this->db->select( $field );
		$this->db->from('user');
		$this->db->order_by("id", "desc"); 
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
	
	/**
	 * 用户信息
	 */
	public function user_info( $where ){
		if( empty( $where ) ){
			return false;
		}
		//构建查询条件
		$w = reshuffle( $where );
		$query = $this->db->query('SELECT * FROM user_info WHERE '.$w.' limit 1');
		$row = $query->row_array();
		return $row;
	}
	
	/**
	 * 账户修改
	 * @param unknown $data
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function user_edit( $data, $where ){
		if( empty( $where ) || empty( $data ) ){
			return false;
		}
		$str = $this->db->update('user', $data, $where);
		return $str;
	}
	
	/**
	 * 删除
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function user_del( $where ){
		if( empty( $where ) ){
			return false;
		}
		//删除用户主表
		$str = $this->db->delete('user', $where);
		//删除用户信息表
		$this->db->delete('user_info', $where);
		return $str;
	}
	
	/**
	 * 账号注册
	 * @param unknown $data
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function user_reg( $data, $db = 'user' ){
		if( empty( $data ) ){
			return false;
		}
		$str = $this->db->insert($db, $data);
		return $str;
	}
	
/*====================================以下是功能函数==================================================*/	
	
	/**
	 * 用户登录验证
	 * @param unknown $param	登录参数	@username用户名  @password密码  @captcha验证码
	 * @param number $dataType	返回参数	1.web	2.app
	 */
	public function user_login( $param = array(), $dataType = 1 ){
		$this->load->library( 'form_validation' );
		$this->dataType = $dataType;
		//参数传递验证
		if( $this->form_validation->run( 'login' ) ){
			//验证码验证
			if( $param['from_app'] || strtolower( $this->session->userdata('captcha') ) == strtolower( $param['captcha'] ) ){
				//验证码通过并且销毁
				$this->session->unset_userdata('captcha');
				//查询用户信息
				$info = $this->user_record( array('username'=>$param['username'],'status'=>1 ), 4 );
				//账号是否存在
				if( !empty( $info['id'] ) ){
					//密码验证
					$this->load->library( 'Password' );
					//加密匹配
					$mypass = $this->password->makePass( $param['password'], $info['salt'] );
					if( $mypass == $info['password'] ){
						$login_time = time();
						$login_ip = $this->input->ip_address();
						//验证通过存入session		
						$new_data = array(
							'username'		=>	$param['username'],
							'uid'			=>	(int)$info['id'],
							'login_time'	=>	$login_time,
							'login_ip'		=>	$login_ip,
							'last_time'		=>	(int)$info['login_time'],
							'last_ip'		=>	$info['login_ip'],
							'status'		=>	1,
							'sessionid'		=>	session_id()
						);
						$_SESSION['uid'] = (int)$info['id'];
						$_SESSION['username'] = $param['username'];
						$_SESSION['overtimen'] = time() + $this->config->item('sess_expiration');
						$_SESSION['login_time'] = $login_time;
						$_SESSION['login_ip'] = $login_ip;
						
						//更新数据库登录信息
						$data = array(
							'login_time'	=> $login_time,
							'login_ip' 		=> $login_ip,
							'last_time'		=> $info['login_time'],
							'last_ip'		=> $info['login_ip'],
						);
						$where = array( "id" => $info['id'] );
						$this->user_edit( $data, $where );
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
	 * 判断是否有中文
	 * @param string $text
	 * @return boolean
	 */
	public function chinese_checked( $text = '' ){
		if( empty( $text ) ){
			return false;
		}else{
			$res = preg_match("/[\xe0-\xef][\x80-\xbf]{2}/",$text );
			if( $res ){
				return true;
			}else{
				return false;
			}
		}
	}
	
	/**
	 * 判断是否夹杂空格
	 * @param string $text
	 * @return boolean
	 */
	public function blank_checked( $text = '' ){
		if( empty( $text ) ){
			return false;
		}else{
			if( strpos( $text, " " ) ){
				return true;
			}else{
				return false;
			}
		}
	}
	
	
	public function user_register( $param = array(), $dataType = 1 ){
		$this->load->library( 'form_validation' );
		$this->dataType = $dataType;
		//参数传递验证
		if( $this->form_validation->run( 'reg' ) ){
			//密码验证
			if( $param['password'] == $param['ckpwd'] ){
				//验证码验证
				if( $param['from_app'] || strtolower( $this->session->userdata('captcha') ) == strtolower( $param['captcha'] ) ){
					//验证码通过并且销毁
					$this->session->unset_userdata('captcha');
					//数据库验证
					$row = $this->user_record( array( 'username' => $param['username'] ), 1 );
					//是否重名
					if( empty( $row['id'] ) ){
						//密码构建
						$this->load->library( 'password' );
						$salt = $this->makeWord( 6, 2 );
						//加密
						$password = $this->password->makePass( $param['password'], $salt );
						$data_main = array(
								'username' 	=> $param['username'],
								'password' 	=> $password,
								'salt' 		=> $salt,	//注册随机码
								'reg_time'	=> time(),
								'reg_ip'	=> $this->input->ip_address(),
						);
						$data_info = array(
							/*	
								'mail' 		=> '',	//$this->input->post('mail'),
								'phone' 	=> '',	//$this->input->post('phone'),
								'age' 		=> '',	//$this->input->post('age'),		//注册随机码
								'stature' 	=> '',	//$this->input->post('stature'),
								'weight' 	=> '',	//$this->input->post('weight'),
								'sex' 		=> '',	//$this->input->post('sex'),
							*/	
						);
						//插入账户表
						$doll = $this->user_reg($data_main);
						if( !$doll ){
							return $this->reback('注册失败');
						}
						$uid = $this->db->insert_id();
						//和谐密码相关
						unset( $data_main['password'] );
						unset( $data_main['salt'] );
						//id信息
						$data_info['id'] = $uid;
						$data_main = array_merge(array('id'=>$uid), $data_main);
						//插入用户信息表
						$this->user_reg($data_info, 'user_info');
						//注册成功
						if( $dataType == 1 ){
							return 1;
						}else{
							return array( 'res'=>1, 'info'=>$data_main );
						}
					}else{
						return $this->reback('该用户名已存在');
					}
				}else{
					return $this->reback('验证码错误');
				}
			}else{
				return $this->reback('两次密码不一样');
			}
		}else{
			return $this->reback('参数格式不对,请按照提示填写');
		}
	}
	
	
	/**
	 * 修改用户信息
	 * @param unknown $param
	 * @param number $dataType
	 * @return number|multitype:number string |Ambigous <unknown, multitype:number, multitype:number unknown >
	 */
	public function update_info( $param, $dataType = 1 ){
		$this->load->library( 'form_validation' );
		$this->dataType = $dataType;
		//参数传递验证
		if( $this->form_validation->run( 'uinfo' ) ){
			$uid = $_SESSION['uid'];  
			$str = $this->db->update('user_info', $param, array( 'id'=>$uid ));
			if( $str == true ){
				if( $dataType == 1 ){
					return 1;
				}else{
					return array( 'res'=>1, 'info'=>'' );
				}
			}else{
				return $this->reback('修改失败');
			}
			
		}else{
			return $this->reback('参数格式不对,请按照提示填写');
		}
	}
	
	/**
	 * 修改用户信息
	 * @param unknown $param
	 * @param number $dataType
	 * @return number|multitype:number string |Ambigous <unknown, multitype:number, multitype:number unknown >
	 */
	public function update_ainfo( $param, $uid ){
		$this->load->library( 'form_validation' );
		//参数传递验证
		if( $this->form_validation->run( 'uinfo' ) ){
			$str = $this->db->update('user_info', $param, array( 'id'=>$uid ));
			if( $str == true ){
				return 1;
			}else{
				return $this->reback('修改失败');
			}
		}else{
			return $this->reback('参数格式不对,请按照提示填写');
		}
	}
	
	/**
	 * 用户设备授权
	 * @param unknown $uid
	 * @param unknown $user_id
	 * @param unknown $devices
	 * @return string|number
	 */
	public function transfer( $uid, $user_id, $devices ){
		if( empty( $uid ) || empty( $user_id ) || empty( $devices ) ){
			return '重要参数丢失';
		}
		//验证设备必须是用户的
		$query = $this->db->query("SELECT name,code,pid,img FROM user_device WHERE user_id = '".$uid."' AND FIND_IN_SET( code, '".$devices."' ) " );
		$list = $query->result_array();
		//剔除已经存在B用户的设备，以防重复
		$dels = $this->db->query("SELECT code FROM user_device WHERE user_id = '".$user_id."' AND FIND_IN_SET( code, '".$devices."' ) " );
		$delete = $dels->result_array();
		//整理需要剔除的数据格式
		if( $delete ){
			$clear = array();
			foreach( $delete as $dl ){
				$clear[] = $dl['code'];
			}
		}else{
			$clear = array();
		}
		if( $list ){
			//验证接受用户
			$now_time = time();
			foreach ( $list as $li ){
				if( in_array( $li['code'], $clear ) ){
					//已经绑定的跳过
					continue;
				}else{
					$insert = array(
						'name'		=>	$li['name'],
						'user_id'	=>	$user_id,
						'code'		=>	$li['code'],
						'pid'		=>	$li['pid'],
						'img'		=>	$li['img'],
						'add_time'	=>	$now_time,
					);
					$this->db->insert('user_device',$insert);
				}
			}
			if( !empty( $insert ) ){
				return 1;
			}else{
				return '接收方已经拥有这些设备的权限';
			}
		}else{
			return '设备归属异常';
		}
	}
	
	
	/**
	 * 更改密码
	 * @param unknown $newpwd	新密码
	 * @param unknown $oldpwd	旧密码
	 * @param unknown $uid		用户id
	 * @return Ambigous <boolean, unknown, unknown>|string
	 */
	public function change_password( $newpwd, $oldpwd, $uid ){
		//用户信息
		$uinfo = $this->user_record( array( 'id'=>$uid ), 3 );
		$this->load->library( 'Password' );
		$ckpass = $this->password->makePass( $oldpwd, $uinfo['salt'] );
		//验证旧密码
		if( $ckpass == $uinfo['password'] ){
			//通过
			$newpass = $this->password->makePass( $newpwd, $uinfo['salt'] );
			$res = $this->user_edit( array('password'=>$newpass), array('id'=>$uid) );
			return $res;
		}else{
			return'旧密码错误';
		}
	}
	
	/**
	 * 检查
	 * @return unknown
	 */
	public function access_check( $module = '*' ){
		$this->db->select( $module );
		$this->db->where( array('id'=>1) );
		$query = $this->db->get('user_set');
		$row = $query->row_array();
		if( $module == '*' ){
			return $row;
		}else{
			return $row[$module];
		}
	}
	
	
	/**
	 * 错误返回提示
	 * @param unknown $info
	 * @param unknown $model
	 * @return unknown|multitype:number unknown
	 */
	private function reback( $info ){
		if( $this->dataType == 1 ){
			return $info;
		}elseif( $this->dataType == 2 ){
			return array( 'res'=>0, 'info'=>$info );
		}
	}
	
	/**
	 * 随机生成注册码
	 */
	public function makeWord( $num = 6, $model = 1 ){
		// 密码字符集，可任意添加你需要的字符
		if( $model == 1 ){
			$chars = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}elseif( $model == 2 ){
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}
		$password = '';
		for ( $i = 0; $i < $num; $i++ ){
			$password .= $chars[mt_rand(0, strlen($chars)-1)];
		}
		return $password;
	}
	


/************商家*************/
	public function user_access( $username ){
		$info = $this->user_record( array('username'=>$username), 1 );
		$this->db->select( 'id,status' );
		$this->db->where( array('user_id'=>$info['id']) );
		$query = $this->db->get('seller');
		$row = $query->row_array();
		if( $row && $row['status'] == 1 ){
			return true;
		}else{
			return false;
		}
	}	
} 