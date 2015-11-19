<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device_model extends CI_Model{
	private $outData = array();
	private $noTotal = array( 3 );	//不需要统计的类型
	private $nullTotal = false;		//flag
	private $head_type = '';
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();	
	}
	
	/**
	 * 设备分类列表
	 * @return unknown
	 */
	public function get_type( $desc = 'desc' ){
		$query = $this->db->query('SELECT * FROM device_type order by id '.$desc);
		$row = $query->result_array();
		return $row;
	}
	
	/**
	 * 分类相关操作
	 * @param unknown $action
	 * @param unknown $param
	 * @return boolean|unknown
	 */
	public function type_act( $action, $param ){
		if( empty( $param ) ){
			return false;
		}
		if( $action == 'add' ){
			//添加	
			$str = $this->db->insert('device_type', $param);
		}elseif( $action == 'edit' ){
			//修改
			if( empty( $param['ori_id'] ) ){
				return false;
			}else{
				$ori_id = $param['ori_id'];
				unset( $param['ori_id'] );
			}
			$str = $this->db->update('device_type', $param, array( 'id'=>$ori_id ));
		}else{
			return false;
		}
		return $str;
	}
	
	
	/**
	 * 所有设备
	 * @param unknown $where
	 * @param number $per_page
	 */
	public function all_device( $where, $per_page = 0, $page = '' ){
		$this->load->model('Page_model', 'page');
		$this->db->where( $where );
		$this->db->from('device');
		$number = $this->db->count_all_results();
		//获取分页偏移
		$offset = $this->page->make_page( $number, $per_page, '', $page );	
		//分页结果
		$this->db->where( $where );
		$this->db->select('device.*,device_type.type_name,GROUP_CONCAT( user.username ) AS users');
		$this->db->from('device');
		$this->db->join('device_type', 'device_type.id=device.type_id', 'left');
		$this->db->join('user_device', 'user_device.code=device.code', 'left');
		$this->db->join('user', 'user.id=user_device.user_id', 'left');
		$this->db->order_by("device.id", "desc");
		$this->db->group_by("device.id");
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		$res = $query->result_array();
		return $res;
		
	}
	
	/**
	 * 设备信息
	 */
	public function device_info( $where, $db = 'device', $field = '*' ){
		if( empty( $where ) ){
			return false;
		}
		//构建查询条件
		$w = reshuffle( $where );
		$query = $this->db->query('SELECT '.$field.' FROM '.$db.' WHERE '.$w.' limit 1');
		$row = $query->row_array();
		return $row;
	}
	
	
	/**
	 * 我的设备查询
	 * @param unknown $where	查询条件
	 * @param number $per_page	每页记录数
	 * @return unknown
	 */
	public function device_list( $where, $per_page = 0, $field = '*', $page = 1 ){
		if( !empty( $per_page ) ){
			//分页查询
			$this->load->model('Page_model', 'page');
			//总数
			$this->db->where( $where );
			$this->db->from('user_device');
			$this->db->join('device', 'device.code = user_device.code');
			$this->db->join('device_type', 'device_type.id = device.type_id');
			$number = $this->db->count_all_results();
			//获取分页偏移
			$offset = $this->page->make_page( $number, $per_page, '', $page );
			//分页结果
			$this->db->select( 'user_device.*,device.device_id,device.device_name,device.type_id,device_type.type_name,device_type.priv' );
			$this->db->where( $where );
			$this->db->from('user_device');
			$this->db->join('device', 'device.code = user_device.code');
			$this->db->join('device_type', 'device_type.id = device.type_id');
			$this->db->limit( $per_page, $offset );
			$query = $this->db->get();
		}else{
			//查询条件
			$this->db->where( $where );
			//查询所有
			if( !empty( $field ) ){
				$this->db->select( $field );
			}
			$this->db->join('device', 'device.code = user_device.code');
			$query = $this->db->get('user_device');	
		}
		$res = $query->result_array(); 
		return $res;
	}
	
	/**
	 * 数据信息列表
	 * @param unknown $where
	 * @param number $per_page
	 * @return unknown
	 */
	public function data_list( $where, $per_page = 0, $page = '' ){
		//查询条件
		$this->db->where( $where );
		if( !empty( $per_page ) ){
			//分页查询
			$this->load->model('Page_model', 'page');
			//总数
			$this->db->from('device_data');
			$number = $this->db->count_all_results();
			//获取分页偏移
			$offset = $this->page->make_page( $number, $per_page, '', $page );
			//分页结果
			$this->db->from('device_data');
			$this->db->limit( $per_page, $offset );
			$query = $this->db->get();
		}else{
			//查询所有
			$query = $this->db->get('device_data');
				
		}
		$res = $query->result_array();
		return $res;
	}
	
	
	/**
	 * 查询处理结果
	 * @param unknown $where
	 * @param unknown $db
	 * @return unknown
	 */
	public function collect_data( $where, $db, $spec = '', $model, $database = '' ){
		if( empty( $database ) ){
			//有更改查库
			$database = $this->db;
		}
		if( !empty( $spec ) ){
			$database->where( "find_in_set( device_code, '".$spec."' )" );
		}
		
		$database->where( $where );
		$database->order_by( 'device_code,time' );
		$query = $database->get( $db );
		$res = $query->result_array();
		$data = array();
		foreach ( $res as $rs ){
			if( empty( $data[$rs['device_code']][$rs['time']] ) ){
				$data[$rs['device_code']][$rs['time']] = $rs;
			}else{
				$data[$rs['device_code']][$rs['time']] = $this->data_formate( $data[$rs['device_code']][$rs['time']], $rs );
			}
		}
		//显示节点
		$param = array();
		//累计
		$total = array();
		//按设备数量循环结果
		foreach ( $data as $k=>$da ){
			switch( $model ){
				case 1:
					//日数据  24小时 	涨幅以‘小时’为单位
					$addtime = 3600;
					break;
				case 2:
				case 3:
					//周数据 月数据  涨幅已‘天’为单位
					$addtime = 86400;
					break;		
			}
			for ( $i = $where['time >=']; $i <= $where['time <=']; $i += $addtime ){
				if( $i > time() - $addtime ){
					//超过当前时间直接跳出;
					break;
				}
				$token = $this->type_data( $data, $i, $k, $da );
				$param[] = $token['param'];
				$total = array_merge( $total, $token['total'] );
			}
		}
		$this->total = $total;
		return $param;
	}
	
	
	public function type_data( $data, $i, $k, $da ){
		$total = array();
		if( empty( $data[$k][$i] ) ){
			$param = array_merge( array( 'id'=>'', 'time'	=> $i, 'device_code' => $k ), $this->blank_formate() );
		}else{
			if( $this->nullTotal ){
				$total = array();
			}else{
				if( empty( $total[$k] ) ){
					$total[$k] = $da[$i]['electricity'];
				}else{
					$total[$k] += $da[$i]['electricity'];
				}
			}
			$param = $da[$i];
		}
		return array( 'param'=>$param, 'total'=>$total );
	}
	
	
	
	/**
	 * 累加统计
	 * @return multitype:NULL
	 */
	public function collect_tatal( $devices ){
		if( $devices && !$this->nullTotal ){
			$res = array();
			$total = $this->total;
			foreach ( $devices as $v ){
				if( empty( $total[$v['code']] ) ){
					$res[] = array(
						'id'			=>	$v['id'],
						'name'			=>	$v['name'],
						'code'			=>	$v['code'],
						'device_name'	=>	$v['device_name'],
						'number'		=>	0
					);
				}else{
					$res[] = array(
						'id'			=>	$v['id'],
						'name'			=>	$v['name'],
						'code'			=>	$v['code'],
						'device_name'	=>	$v['device_name'],
						'number'		=>	$total[$v['code']]
					);
				}
			}
			return $res;
		}else{
			return array();
		}
	}
	
	
	
	/**
	 * 获取分页信息
	 * @param number $mode
	 * @return unknown
	 */
	public function make_bar( $model = 1, $param = '' ){
		$this->load->model('Page_model', 'page');
		if( $model == 1 ){
			$res = $this->page->page_bar( false, 3, 'admin', $param );
		}elseif ( $model == 2 ){
			$res = $this->page->page_info();
		}elseif( $model == 3 ){
			$res = $this->page->page_bar( false, 2, 'business', $param );
		}
		return $res;
	}
	
	/**
	 * 绑定
	 * @param unknown $data
	 * @param string $db
	 * @return boolean|unknown
	 */
	public function user_device_bind( $data, $db = 'device' ){
		if( empty( $data ) ){
			return false;
		}
		$str = $this->db->insert($db, $data);
		return $str;
	}
	
	
	public function ck_authority( $code = '', $model = 'child' ){
		if( empty( $code ) ){
			return false;
		}
		$bench = strlen( $code ) - 8;
		$type = (int)substr( $code, 0, $bench );
		$this->db->select('priv');
		$this->db->from('device_type');
		$this->db->where( array( 'id'=>$type ) );
		$query = $this->db->get();
		$res = $query->row_array();
		if( $model == 'child' ){
			if( $res['priv'] & 1 ){
				return false;
			}else{
				return true;
			}
		}elseif( $model == 'parent' ){
			if( $res['priv'] & 1 ){
				return true;
			}else{
				return false;
			}
		}
	}
	
	/**
	 * 删除绑定信息
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function cancel_ud( $where ){
		if( empty( $where ) ){
			return false;
		}
		$device_info = $this->device_info( $where, 'user_device' );
		if( empty( $device_info['id'] ) ){
			return false;
		}
		//删除绑定信息
		$str = $this->db->delete('user_device', $where);
		return $str;
	}
	
	
	public function user_device_edit( $param = '', $where = '' ){
		if( empty( $where ) || empty( $param ) ){
			return false;
		}
		$str = $this->db->update('user_device', $param, $where);
		return $str;
	}
	
	
	/**
	 * 设备操作
	 * @param unknown $param
	 * @param unknown $action
	 */
	public function device_act( $action, $param, $id = '' ){
		if( $action == 'add' ){
			//添加
			if( empty( $param ) ){
				return false;
			}
			//设备id
			$this->db->select_max('device_id');
			$this->db->where( 'type_id', $param['type_id'] );
			$this->db->from('device');
			$head = sprintf ("%04d", $param['type_id']);
			//数量
			$query = $this->db->get();
			$max = $query->row_array();
			$device_id = $max['device_id'] + 1;
			$foot = sprintf ("%08d", $device_id);
			$param['device_id'] = $device_id;
			$param['code'] = $head.$foot;
			$param['salt'] = $this->makeSalt();
			$str = $this->db->insert('device', $param);
		}elseif( $action == 'edit' ){
			//修改
			if( empty( $id ) || empty( $param ) ){
				return false;
			}
			$str = $this->db->update('device', $param, array('id'=>$id));
		}else{
			return false;
		}
		return $str;
	}
	
	/**
	 * 删除设备信息
	 * @param unknown $where
	 * @return boolean|unknown
	 */
	public function delete_device( $where ){
		if( empty( $where['id'] ) && empty( $where['code'] ) ){
			return false;
		}
		//删除绑定信息
		$str = $this->db->delete('device', $where);
		return $str;
	}
	
	
	/**
	 * 绑定的用户列表
	 * @param unknown $code
	 * @return boolean
	 */
	public function users( $code ){
		if( empty( $code ) ){
			return false;
		}
		$this->db->select( 'user_device.id,user_device.name,user_device.pid,user.username' );
		$this->db->where( array( 'code'=>$code ) );
		$this->db->join('user', 'user.id = user_device.user_id', 'left');
		$this->db->from('user_device');
		$query = $this->db->get();
		return $query->result_array();
	}
	
	/**
	 * 更改用户自定义名称
	 * @param unknown $param
	 * @param unknown $id
	 * @return boolean
	 */
	public function changname( $param, $id ){
		if( empty( $param ) || empty( $id ) ){
			return $this->reback( '重要参数丢失' );
		}
		$str = $this->db->update('user_device', $param, array( 'id'=>$id ));
		if( $str ){
			return 1;
		}else{
			return $this->reback( '修改失败' );
		}
	}
	
	
/***************************以下是功能函数**********************************/	
	
	public function bind( $param, $dataType = 1 ){
		$this->load->library( 'form_validation' );
		$this->dataType = $dataType;
		$uid = $_SESSION['uid'];
		//参数传递验证
		if( $this->form_validation->run( 'bind' ) ){
			//验证机器是否存在
			$info = $this->device_info( array( 'code'=>$param['code'], 'salt'=>strtolower( $param['salt'] ) ) );
			if( !empty( $info['id'] ) ){
				//验证该机器是否已经绑定
				$ud = $this->device_info( array( 'code'=>$param['code'], 'user_id'=>$uid ), 'user_device' );
				if( empty( $ud['id'] ) ){
					//判断设备的分类与权限
				//	$mypriv = $this->ck_priv( $param['pid'], $info['type_id'], $uid );
				//	if( $mypriv == 1 ){
						$data = array(
							'name'		=>	$param['device_name'],
							'user_id'	=>	$uid,
							'code'		=>	$param['code'],
						//	'img'		=>	
							'add_time'	=>	time(),
							'pid'		=>	$param['pid'] ? $param['pid'] : 0
						);
						$res = $this->user_device_bind( $data, 'user_device' );
						if( $res ){
							if( $dataType == 1 ){
								return 1;
							}else{
								return array( 'res'=>1, 'info'=>'' );
							}
						}else{
							return $this->reback( '设备添加失败,请稍后再试' );
						}
				/*	}else{
						return $this->reback( $mypriv );
					}
				*/	
				}else{
					return $this->reback( '您已经添加该设备，请勿重复操作' );
				}
			}else{
				return $this->reback( '设备信息不匹配，请验证后重新输入' );
			}
		}else{
			return $this->reback( '参数不和规格，请按提示操作' );
		}
	}
	
	
	/**
	 * 修改，重新绑定
	 * @param unknown $id
	 * @param unknown $pid
	 */
	public function re_bind( $id, $pid, $uid ){
		$this->dataType = 1;
	//	$info = $this->device_info( array( 'u.id'=>$id ), 'user_device as u left join device as d on d.code = u.code' );
		//验证权限
	//	$mypriv = $this->ck_priv( $pid, $info['type_id'], $uid );
	//	if( $mypriv == 1 ){
			//update
			$res = $this->changname( array( 'pid'=>$pid ), $id );
			if( $res ){
				return 1;
			}else{
				return '修改失败';
			}
	/*		
		}else{
			return $this->reback( $mypriv );
		}
	*/	
	}
	
	
	/**
	 * 检查绑定权限
	 * @param unknown $pid
	 * @param unknown $type_id
	 * @param unknown $uid
	 * @return string|number
	 */
	public function ck_priv( $pid, $type_id, $uid ){
		if( empty( $type_id ) ){
			return '设备分类丢失';
		}
		//当前设备类型
		$type = $this->device_info( array( 'id'=>$type_id ), 'device_type' );
		if( $pid == 0 ){
			//不传父主机，那么必须自己是主机或者能上网
			if( ($type['priv'] & 1) == 1 || ($type['priv'] & 2) == 2 ){
				//自己是主机或者能上网 通过
				return 1;	
			}else{
				//自己不是主机并且不能上网，卒
				return '当前设备为子设备，请选择主机';
			}	
		}else{
			//只能是子类
			if( ($type['priv'] & 2) == 0 ){
				//非主机功能，属于子类
				$host = $this->device_info( array( 'd.id'=>$pid ), 'device as d left join device_type as t on d.type_id = t.id' );
				if( empty( $host ) ){
					return '找不到主机信息';
				}else{
					//验证是否有主机能力
					if( ( ($host['priv'] & 1) == 1 ) ){
						//最后验证主机必须是自己的	
						$myhost = $this->device_info( array( 'code'=>$host['code'], 'user_id'=>$uid ), 'user_device' );
						if( !empty( $myhost['id'] ) ){
							return 1;
						}else{
							return '目标主机设备不是你的，请勿非法操作';
						}
					}else{
						return '目标主机设备类型异常，请核实相关详情';
					}
				}
			}else{
				return '非子设备，勿传输父主机pid';
			}	
		}
	}
	
	
	
	/**
	 * 处理时间数据
	 */
	public function time_fomate( $date, $model ){
		//构建时间条件
		$day = date( 'Y-m-d', $date );
		$month = date( 'Y-m', $date );
		if( $model == 1 ){
			//日记录
			$db = 'device_hour_data';
			//当日
			$go_time = strtotime( $day );
			$end_time = strtotime( $day.' 23:59:59 ' );
		}elseif ( $model == 2 ){
			//周记录
			$db = 'device_day_data';
			//当周
			$use_day = date( "w", $date );
			$go_time = strtotime( $day. '-'.$use_day.'day' );
			$short_day = 7 - date( "w", $date );
			$end_time = strtotime( $day. '+'.$short_day.'day -1 second' );
		}elseif( $model == 3 ){
			//月记录
			$db = 'device_day_data';
			$go_time = strtotime( $month.'-01 00:00:00' );
			$end_time = strtotime( $month.'-01 23:59:59'.' +1 month -1 day ' );
		}else{
			return false;
		}
		return array( 'db'=>$db, 'go_time'=>$go_time, 'end_time'=>$end_time );
	}
	
	/**
	 * 按设备分类统计数量
	 */
	public function type_number(){
		$query = $this->db->query(
			'SELECT t.id,t.type_name,IF( d.num IS NULL, 0, d.num ) AS num,IF( d.maxid IS NULL, 0, d.maxid ) AS maxid '.
			'FROM device_type AS t '.
			'LEFT JOIN ( SELECT COUNT(id) AS num,type_id,MAX(device_id) AS maxid '.
			'FROM device GROUP BY type_id ) AS d ON d.type_id = t.id'
		);
		$res = $query->result_array();
		return $res;
	}

	/**
	 * 批量
	 */
	public function batch_add( $typeid, $num, $device_name, $supplier = 0, $status = 1 ){
		$data = array();
		$param = array( 'type_id'=>$typeid );
		//头构建
		$head = sprintf ("%04d", $typeid );
		//取库最大值
		$this->db->select_max('device_id');
		$this->db->where( 'type_id', $typeid );
		$this->db->from('device');
		$query = $this->db->get();
		$max = $query->row_array();
		//最大值加1
		$device_id = $max['device_id'] + 1;
		for ( $i=0; $i < $num; $i++ ) { 
			$foot = sprintf ( "%08d", $device_id );
			$param ['device_name'] = $device_name.$device_id;
			$param['device_id'] = $device_id;
			$param['code'] = $head.$foot;
			$param['salt'] = $this->makeSalt();
			$param['supplier'] = $supplier;
			$param['status'] = $status;
			$data[] = $param;
			$device_id++;
		}
		$this->outData = $data;
		$str = $this->db->insert_batch( 'device' , $data ); 
		if( $str ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 重新选择数据库
	 * @param unknown $type_id
	 */
	public function change_db( $param, $model = 'code' ){
		if( empty( $param ) ){
			return false;
		}
		if( $model == 'code' ){
			$this->load->database( 'default', TRUE );
			$info = $this->device_info( array('code'=>$param), 'device', 'type_id' );
			if( empty( $info['type_id'] ) || $info['type_id'] == '4001' ){	
				$trueType = $this->check_type( '4001' );
				return false;
			}else{
				$trueType = $this->check_type( $info['type_id'] );
				$type_id = $info['type_id'];
			}
		}elseif ( $model == 'type' ){		
			if( empty( $param ) || $param == '4001' ){
				$trueType = $this->check_type( '4001' );
				return false;
			}else{
				$trueType = $this->check_type( $param );
				$type_id = $param;
			}
		}
		return $this->load->database( $trueType, TRUE );
	}
	
	//不需累计类型
	private function check_type( $type_id = '4001' ){
		$type_id = $type_id ? $type_id : '4001';
		$trueType = sprintf("%04d", $type_id);
		$this->head_type = substr( $trueType, 0, 1 );
		if( in_array( $this->head_type, $this->noTotal ) ){
			$this->nullTotal = true;
		}
		return $trueType;
	}
	
	
	public function limit_type( $key = '' ){
		if( empty( $key ) ){
			return '';
		}
		switch ( $key ){
			case 'light':	//云灯
				$w = array( 'device.type_id >' => 4000, 'device.type_id <'=> 5000 );
				break;
			case 'socket':	//插板
				$w = array( 'device.type_id >' => 0, 'device.type_id <'=> 1000 );
				break;
			case 'airck':	//空测
				$w = array( 'device.type_id >' => 3000, 'device.type_id <'=> 4000 );
				break;
			case 'bike':	//自行车
				$w = array( 'device.type_id >' => 6000, 'device.type_id <'=> 7000 );
				break;		
		}
		return $w;
	}
	
	
	/**
	 * 重复数据处理
	 * @param unknown $data
	 * @param unknown $rs
	 * @return number
	 */
	private function data_formate( $data, $rs ){
		switch ( $this->head_type ){
			case 0:			//插板
			case 4:			//云灯
				$data['electricity'] += $rs['electricity'];
				break;
			case 3:			//空气探测
				$data['temp'] = floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['rh'] = floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['co2'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['pm'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['hcho'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['co'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['lux'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['wind'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['rain'] =  floor( ( $data['electricity'] + $rs['electricity'] ) / 2 );
				$data['status'] =  floor( ( $data['status'] + $rs['status'] ) / 2 );
				break;
		}
		return $data;
	}
	
	
	private function blank_formate(){
		switch ( $this->head_type ){
			case 0:			//插板
			case 4:			//云灯
				$data = array(
				'electricity' => 0
				);
				break;
			case 3:			//空气探测
				$data = array(
				'temp'		=> 0,
				'rh'		=> 0,
				'co2'		=> 0,
				'pm'		=> 0,
				'hcho'		=> 0,
				'co'		=> 0,
				'lux'		=> 0,
				'wind'		=> 0,
				'rain'		=> 0,
				'status'	=> 0
				);
				break;
			default:
				$data = array();
		}
		return $data;
	}
	
	
	/**
	 * 之前操作的数据列表
	 */
	public function outData_list(){
		return $this->outData;
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


	private function makeSalt(){
		// 密码字符集，可任意添加你需要的字符
		$chars = 'abcdefghijklmnopqrstuvwxyz';
		$password = '';
		for ( $i = 0; $i < 6; $i++ ){
			$password .= $chars[mt_rand(0, strlen($chars)-1)];
		}
		return $password;
	}


	
}