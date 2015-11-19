<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Forum_model extends CI_Model{
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		//查询bbs库
		$this->load->database( 'bbs', TRUE );
		//531行 上传需要注销
	}
	
	/**
	 * 更新
	 * @param unknown $param
	 * @param unknown $where
	 * @return boolean
	 */
	public function update_forum( $param, $where ){
		if( empty( $param ) || empty( $where ) ){
			return false;
		}
		$this->load->database( 'bbs', TRUE );
		return $this->db->update( 'forum_forum', $param, $where );
	}
	
	
	public function update_live( $where = '', $param = '' ){
		if( empty( $param ) || empty( $where ) ){
			return false;
		}
		$this->load->database( 'default', TRUE );
		return $this->db->update( 'user_info', $param, $where );
	}
	
	
	/**
	 * 新帖
	 * @param unknown $param
	 * @return boolean
	 */
	public function new_forum( $param ){
		if( empty( $param ) ){
			return false;
		}
		$this->load->database( 'bbs', TRUE );
		return $this->db->insert( 'forum_forum', $param );
	}
	
	/**
	 * 编辑帖
	 * @param unknown $param
	 * @param string $where
	 * @return boolean
	 */
	public function edit_forum( $param, $where = '' ){
		if( empty( $param ) || empty( $where ) ){
			return false;
		}
		$this->load->database( 'bbs', TRUE );
		return $this->db->update( 'forum_forum', $param, $where );
}
	
	/**
	 * 更新
	 * @param unknown $param
	 * @return boolean
	 */
	public function cumulation_forum( $param, $id ){
		if( empty( $param ) || empty( $id ) ){
			return false;
		}
		$this->load->database( 'bbs', TRUE );
		return $this->db->query( 'update forum_forum set `'.$param.'`=`'.$param.'` + 1 where id = '.$id );
	}
	
	/**
	 * 帖子内容
	 * @param string $where
	 * @param string $read
	 * @return boolean|unknown
	 */
	public function forum_info( $where = '', $read = false ){
		$this->load->database( 'bbs', TRUE );
		if( empty( $where ) ){
			return false;
		}else{
			$this->db->where( $where );
		}
		$this->db->select('f.*,t.type_name');
		$this->db->from('forum_forum as f');
		$this->db->join('forum_type as t', 'f.type = t.id', 'left');
		$query = $this->db->get();
		$info = $query->row_array();
		//点击增加
		if( !empty( $info['id'] ) && $read ){
			$this->cumulation_forum( 'read', $where['f.id'] );
		}
		return $info;
	}
	
	/**
	 * 暂时删除帖子
	 * @param unknown $fid
	 * @return boolean
	 */
	public function delete_forum( $fid, $from = '' ){
		if( empty( $fid ) ){
			return false;
		}
		$info = $this->forum_info( array( 'f.id'=>$fid ) );
		//管理员权限判断
		if( $from == 'admin' || $_SESSION['role'] == 'manager' ){
			$pass = true;
		}else{
			$pass = false;
		}
		if( $info['user_id'] == $_SESSION['uid'] || $pass ){
			$str = $this->update_forum( array( 'isdel'=>1 ), array( 'id'=>$fid ) );
			if( $str ){
				return 1;
			}else{
				return '删除失败';
			}
		}else{
			return '请勿操作他人帖子';
		}
	}
	
	/**
	 * 删除楼层
	 * @param unknown $id
	 * @return boolean
	 */
	public function delete_floor( $id ){
		if( empty( $id ) ){
			return false;
		}
		$this->load->database( 'bbs', TRUE );
		$this->db->from('forum_response');
		$this->db->where( array('id'=>$id) );
		$query = $this->db->get();
		$floor = $query->row_array();
		if( $floor ){
			//删除该回复
			$this->db->delete( 'forum_response', array('id'=>$id) );
			//删除旗下图片
			$images = $this->find_image( $floor['context'] );
			$this->db->select('path');
			$this->db->from('forum_image');
			$this->db->where_in( 'src', $images );
			$query = $this->db->get();
			$img = $query->result_array();
			foreach( $img as $i ){
				@unlink( $i['path'] );
			}
			return true;
		}else{
			return false;
		}
	}
	
	
	public function delete_batch( $ids ){
		if( empty( $ids ) ){
			return false;
		}
		$str = $this->db->update( 'forum_forum', array( 'isdel'=>1 ), "find_in_set( id, '".$ids."' )" );
		if( $str ){
			return 1;
		}else{
			return '删除失败';
		}
	}
	
	
	public function delete_true( $ids = '' ){
		if( empty( $ids ) ){
			return false;
		}
		$this->db->select('path');
		$this->db->from('forum_image');
		$this->db->where( "find_in_set( fid, '".$ids."' )" );
		$query = $this->db->get();
		$img = $query->result_array();
		$this->db->trans_start();
		//删除主贴
		$this->db->delete( 'forum_forum', "find_in_set( id, '".$ids."' )" );
		//删除回复
		$this->db->delete( 'forum_response', "find_in_set( pid, '".$ids."' )" );
		//删除图片
		$this->db->delete( 'forum_image', "find_in_set( pid, '".$ids."' )" );
		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE){
			// 生成一条错误信息... 或者使用 log_message() 函数来记录你的错误信息
			return '删除失败';
		}else{
			//删除图片
			if( $img ){
				foreach( $img as $i ){
					@unlink( $i['path'] );
				}
			}
			return 1;
		}
	}
	
	
	public function new_response( $param ){
		$this->load->database( 'bbs', TRUE );
		if( empty( $param ) ){
			return false;
		}
		$this->db->select('floor');
		$this->db->from('forum_response');
		$this->db->where( array( 'pid' => $param['pid'] ) );
		$this->db->order_by( 'floor', 'desc' );
		$query = $this->db->get();
		$token = $query->row_array();
		if( empty( $token['floor'] ) ){ 	
			$floor = 1;
		}else{
			$floor = $token['floor'];
		}
		$param['floor'] = $floor + 1;
		return $this->db->insert( 'forum_response', $param );
	}
	
	
	/**
	 * 回复列表
	 * @param unknown $pid
	 * @param number $page
	 * @param number $per_page
	 * @return boolean
	 */
	public function back_list( $pid, $page = 1, $per_page = 15 ){
		$this->load->database( 'bbs', TRUE );
		if( empty( $pid ) ){
			return false;
		}
		//分页
		$this->load->model('Page_model', 'page');
		$this->db->from('forum_response');
		$this->db->where( array( 'pid' => $pid ) );
		$number = $this->db->count_all_results();
		//正式查询
		$this->db->select('*');
		$this->db->from('forum_response');
		$this->db->where( array( 'pid' => $pid ) );
		$this->db->order_by( 'floor,retime' );
		$offset = $this->page->make_page( $number, $per_page, '', $page );
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		return $query->result_array();
	}
	
	
	
	public function response_list( $where, $page = 1, $per_page = 15, $order = 'r.retime desc' ){
		$this->load->model('Page_model', 'page');
		$this->db->from('forum_response as r');
		$this->db->join('forum_forum as f', 'f.id = r.pid', 'left');
		if( !empty( $where ) ){
			$this->db->where( $where );
		}
		$number = $this->db->count_all_results();
		//正式查询
		$this->db->select('r.*,f.title,f.type,t.type_name');
		$this->db->from('forum_response as r');
		$this->db->join('forum_forum as f', 'f.id = r.pid', 'left');
		$this->db->join('forum_type as t', 't.id = f.type', 'left');
		if( !empty( $where ) ){
			$this->db->where( $where );
		}
		$this->db->order_by( $order );
		$offset = $this->page->make_page( $number, $per_page, '', $page );
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		return $query->result_array();
	}
	
	
	
	/**
	 * 帖子
	 * @param string $type_id
	 * @param number $page
	 * @param number $pre_page
	 */
	public function forum_list( $where = '', $type_id = '', $page = 1, $per_page = 15, $order = 'addtime desc', $like = '' ){
		//查询bbs库
		$this->load->database( 'bbs', TRUE );
		if( empty( $type_id ) ){
			//查询所有
		}else{
			//获取子分类
			$types = $this->typeChildren( $type_id );	
		}
		//分页
		$this->load->model('Page_model', 'page');
		$this->db->from('forum_forum as f');
		if( !empty( $where ) ){
			$this->db->where( $where );
		}
		if( !empty( $like ) ){
			$this->db->like( 'f.title', $like['keyword'] );
		//	$this->db->or_like( 'f.context', $like['keyword'] );
		}
		if( !empty( $types ) ){
			$this->db->where( "find_in_set( type, '".$types."' )" );
		}
		$number = $this->db->count_all_results();
		//正式查询
		$this->db->select('f.id,f.title,f.addtime,f.updtime,f.user_id,f.read,f.review,f.isdel,f.sticky,f.elite,f.type,t.type_name,count(r.id) as rpd');
		$this->db->join('forum_type as t', 't.id = f.type');
		$this->db->join('forum_response as r', 'f.id = r.pid', 'left');
		$this->db->from('forum_forum as f');
		if( !empty( $where ) ){
			$this->db->where( $where );
		}
		if( !empty( $like ) ){
			$this->db->like( 'f.title', $like['keyword'] );
		//	$this->db->or_like( 'f.context', $like['keyword'] );
		}
		if( !empty( $types ) ){
			$this->db->where( "find_in_set( type, '".$types."' )" );
		}
		$this->db->group_by( 'f.id' );
		$this->db->order_by( $order );
		$offset = $this->page->make_page( $number, $per_page, '', $page );
		$this->db->limit( $per_page, $offset );
		$query = $this->db->get();
		return $query->result_array();
		
	}
	
	private function typeChildren( $id ){
		$this->db->select('id');
		$this->db->from('forum_type');
		$this->db->or_where( array( 'id'=>$id, 'pid'=>$id ) );
		$query = $this->db->get();
		$types = $query->result_array();
		return $this->param_formate( $types, 'id' );
	}
	
	/**
	 * 跨库用户查询
	 * @param unknown $list
	 */
	public function forum_users( $list = array() ){
		$this->load->database( 'default', TRUE );
		//整理用户
		if( empty( $list ) ){
			return false;
		}
		$users = $this->param_formate( $list, 'user_id', true );
		$this->db->select('id,username');
		$this->db->from('user');
		$this->db->where( "find_in_set( id, '".$users."' )" );
		$query = $this->db->get();
		$user_list = $query->result_array();
		$res = array();
		foreach ( $user_list as $ul ){
			$res[$ul['id']] = $ul['username'];
		}
		return $res;
	}
	
	/**
	 * 统计
	 * @param string $uid
	 * @return multitype:number |multitype:number unknown
	 */
	public function make_statis( $uid = '' ){
		if( empty( $uid ) ){
			return array('back'=>0,'tie'=>0,'mess'=>0,'fav'=>0);
		}
		$this->db->from('forum_response');
		$this->db->where( array( 'user_id'=>$uid ) );
		$back = $this->db->count_all_results();
		$this->db->from('forum_forum');
		$this->db->where( array( 'user_id'=>$uid ) );
		$tie = $this->db->count_all_results();
		return array( 'back'=>$back,'tie'=>$tie,'mess'=>0,'fav'=>0 );
	}
	
	
	/**
	 * 分类操作
	 * @param unknown $action
	 * @param string $where
	 * @return unknown|boolean
	 */
	public function type_action( $action, $where = '' ){
		if( $action == 'delete' && !empty( $where ) ){
			$this->db->from('forum_type');
			$this->db->where( array( 'pid'=>$where['id'] ) );
			$number = $this->db->count_all_results();
			if( $number == 0 ){
				$str = $this->db->delete('forum_type', $where);
				return $str;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	
	/**
	 * 分类列表
	 * @model 1全部  2去除pid为0的选项
	 * @return Ambigous <boolean, multitype:unknown >
	 */
	public function getTypes( $model = 1 ){
		$this->load->database( 'bbs', TRUE );
		$this->db->select('*');
		$this->db->from('forum_type');
		$this->db->order_by('pid,ascno');
		$query = $this->db->get();
		$types = $query->result_array();
		$res = $this->type_formate( $types, $model );
		return $res;
	}
	
	/**
	 * 分类格式整理
	 * @param unknown $types
	 * @return multitype:unknown |boolean
	 */
	public function type_formate( $types = array(), $model = 1 ){
		$top = array();
		$mid = array();
		$order = array();
		$vk = array();
		if( $types ){
			foreach( $types as $k=>&$tp ){
				if( $tp['pid'] == 0 ){
					//第一层
					$order[$tp['id']] = array();
					//判断二层数据的标志
					$top[] = $tp['id'];
					$tp['level'] = 1;
				}else{
					//第二层
					if( in_array( $tp['pid'], $top ) ){
						$order[$tp['pid']][$tp['id']]  = array();
						//判断三层的标志
						$mid[$tp['id']] = array( $tp['pid'], $tp['id'] );
						$tp['level'] = 2;
					}else{
						//第三层
						$order[$mid[$tp['pid']][0]][$mid[$tp['pid']][1]][$tp['id']] = $tp['id'];
						$tp['level'] = 3;
					}
				}
				
			/*	
				if( $tp['pid'] == 0 ){
					//第一层
					$order[$tp['id'].'_'] = $tp['id'];
					$top[] = $tp['id'];
					$tp['level'] = 1;
				}else{
					//第二层
					if( in_array( $tp['pid'], $top ) ){
						$order[$tp['pid'].'_'.$ascno.$tp['id']]  = $tp['id'];
						$mid[$tp['id']] = $tp['pid'].'_'.$ascno.$tp['id'];
						$tp['level'] = 2;
					}else{
						//第三层
						$order[$mid[$tp['pid']].'_'.$ascno.$tp['id']]  = $tp['id'];
						$tp['level'] = 3;
					}
				}
			*/	
				$vk[$tp['id']] = $k;
			}
			$formate = $this->makeLine( $order );
			//重组数组
			$res = array();
			foreach ( $formate as $v ){
				if( $model == 2 && $types[$vk[$v]]['pid'] == 0 ){
					continue;
				}
				$res[] = $types[$vk[$v]];
			}
			return $res;
		}else{
			return false;
		}
	}
	
	
	public function invalid_pic(){
		$this->db->from('forum_image');
		$this->db->where( array( 'send'=>0 ) );
		return $this->db->count_all_results();
	}
	
	
	/**
	 * 论坛图片冗余处理
	 * @param unknown $content
	 * @param string $alread
	 */
	public function confirm_image( $context = '', $fid = 0 , $alread = '' ){
		$out = $this->find_image( $context );
		$this->load->database( 'bbs', TRUE );
		if( !empty( $alread ) ){
			//修改提交
			$saves = explode( ',', $alread );
			$dels = array();
			foreach ( $saves as $ss ){
				if( in_array( $ss, $out ) ){
					$key = array_search( $ss,$out );
					unset( $out[$key] );
				}else{
					$dels[] = $ss;
				}
			}
			if( $dels ){
				$this->db->select('path');
				$this->db->where_in( 'src', $dels );
				$query = $this->db->get('forum_image');
				$row = $query->result_array();
				foreach( $row as $r ){
					@unlink( $r['path'] );
				}
				$this->db->where_in( 'src', $dels );
				$this->db->delete( 'forum_image' );
			}
		}
		//要去除前缀站点信息
		if( $out ){
			$this->db->where_in( 'src', $out );
			$param = array('send'=>1);
			if( !empty( $fid ) ){
				$param['fid'] = $fid;
			}
			$this->db->update( 'forum_image', $param );
		}
	}
	
	/**
	 * 正则找到图片
	 * @param string $context
	 * @return unknown
	 */
	public function find_image( $context = '' ){
		preg_match_all ('/<img src=\"(.*?)\"/', $context, $out );
		$images = array();
		foreach( $out[1] as $v ){
			$v = str_replace( base_url(), '/', $v );
		//	$v = str_replace( '/myhome/', '/', $v );
			$images[] = $v;
		}
		return $images;
	}
	
	
	/**
	 * 分类核心格式
	 * @param unknown $list
	 */
	private function makeLine( $list ){
		if( is_array( $list ) ){
			$res = array();
			foreach( $list as $key=>$lt ){
				$res[] = $key;
				if( is_array( $lt ) ){
					$res = array_merge( $res, $this->makeLine( $lt ) );
				}
			}
		}
		return $res;
	}
	  
	/**
	 * 处理分类
	 * @param unknown $update
	 * @param unknown $insert
	 * @return unknown
	 */
	public function makeType( $update = array(), $insert = array() ){
		$sql = array();
		//组合修改的数据
		if( !empty( $update ) ){
			foreach ( $update as $k => $ud ){
				$sql[] = "update forum_type set type_name = '".$ud['name']."',ascno = ".$ud['ascno']." where id = ".$k." ";
			}
		}
		//组合新插入的数据
		if( !empty( $insert ) ){
			foreach ( $insert as $ik => $it ){
				if( $ik == 0 ){
					$query = "insert into forum_type( type_name, ascno, pid ) values( '".$it['name']."', '".$it['ascno']."', ".$it['pid']." )";
				}else{
					$query .= ", ( '".$it['name']."', '".$it['ascno']."', ".$it['pid']." ) ";
				}
			}
			$sql[] = $query;
		}
		//执行sql 事务提交
		$this->db->trans_start();
		foreach( $sql as $v ){
			$res = $this->db->query( $v );
		}
		$this->db->trans_complete();
		if ($this->db->trans_status() === FALSE){
			return false;
		}else{
			return true;
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
			$res = $this->page->page_bar( false, 3, '', $param );
		}elseif ( $model == 2 ){
			$res = $this->page->page_info();
		}elseif( $model == 3 ){
			$res = $this->page->page_bar();
		}
		return $res;
	}
	
	
	//数据连接格式
	private function param_formate( $param = array(), $key = 'id', $only = false ){
		$res = '';
		$already = array();
		foreach ( $param as $ts ){
			if( $only ){
				//数据唯一性
				if( in_array( $ts[$key], $already) ){
					continue;
				}
				$already[] = $ts[$key];
			}
			if( empty( $res ) ){
				$res = $ts[$key];
			}else{
				$res .= ','.$ts[$key];
			}
		}
		return $res;
	}
	
	
	
	/**
	 * 获取当前登录用户的live显示
	 */
	public function liveShow(){
		if( empty( $_SESSION['uid'] ) ){
			return '';
		}
		//默认family
		$host = $this->load->database( 'default', TRUE );
		//查询绑定信息
		$this->db->select('forumLive');
		$this->db->from('user_info');
		$this->db->where( array( 'id'=>$_SESSION['uid'] ) );
		$query = $this->db->get();
		$info = $query->row_array();
		if( empty( $info['forumLive'] ) ){
			//没有设定显示数据则查询绑定信息
			return '';
		/*	
			$this->db->select('code,name');
			$this->db->from('user_device');
			$this->db->where( array( 'user_id'=>$_SESSION['uid'] ) );
			$this->db->order_by("add_time",'desc');
			$query = $this->db->get();
			$row = $query->row_array();
			if( empty( $row['code'] ) ){
				//没有绑定信息
				return '';
			}else{
				$code = $row['code'];
			}
		*/	
		}else{
			//有绑定数据
			$code = $info['forumLive'];
			$this->db->select('name');
			$this->db->from('user_device');
			$this->db->where( array( 'code'=>$code, 'user_id'=>$_SESSION['uid'] ) );
			$query = $this->db->get();
			$row = $query->row_array();
			if( empty( $row['name'] ) ){
				return '';
			}else{
				return $this->chooseModel( $code, $row['name'] );
			}
		}
		
	}
	
	/**
	 * 显示模板
	 * @param unknown $code
	 */
	public function chooseModel( $code, $name ){
		$type = substr( $code, 0, 4 );
		if( $type == '4001' ){
			$type = 'default';
		}else{
			$type = sprintf ("%04d", $type);
		}
	/*	
	 	if( $type == '0001' ){
			$db = $this->load->database( 'default', TRUE );
			$db->select('*');
			$db->from('socket_data');
		}else{
	*/	
		$db = $this->load->database( $type, TRUE );
		$db->select('*');
		$db->from('device_data');
	//	}
		$db->where( array( 'device_code' => $code ) );
		$db->order_by("time",'desc');
		$query = $db->get();
		$row = $query->row_array();
		if( empty( $row ) ){
			return '';
		}
		switch ( $type ){
			case 'default':
			case '4001':
				$row['voltage'] = $row['voltage'] / 100;
				$row['electricity'] = $row['electricity'] / 3200;
				$row['current'] = $row['current'] / 1000;
				$row['power'] = $row['power'] / 100;
				$switch = $row['switch'] ? '开' : '关';
				$html = '<div id="showlive" title="code:'.$code.'" >
						  	 <div>设备：'.$name.'</div>
							 <div>电压：'.$row['voltage'].'V</div>
						     <div>电流：'.$row['current'].'A</div>
						     <div>功率：'.$row['power'].'W</div>
						     <div>电量：'.$row['electricity'].'kWh</div>
						     <div>开关状态：'.$switch.'</div>
						 </div>';
				break;
			case '3001':
				//状态标识 十进制转十六进制判断
				$status = dechex( $row['status'] );
				$power = substr( $status, 0, 1 ) ? '正常' : '异常';
				$space = substr( $status, 1, 1 ) ? '室外' : '室内';
				//源数据是放大10000倍的数据  但是计量单位又mg变成ug则 除以10
				$row['hcho'] = $row['hcho'] / 10;
				$source = $row['source'] ? '外部供电' : '电池供电';
				$tt = time() - $row['time'];
				if( $tt > 3600 ){
					//超过一小时则显示离线
					$expried = "<div style='color:red;'>当前设备已离线</div>";
				}else{
					$expried = '';
				}
				switch ( $row['iaq'] ){
					case 1:
						$quality = '优';
						break;
					case 2:
						$quality = '良';
						break;
					case 3:
						$quality = '轻度污染';
						break;
					case 4:
						$quality = '中度污染';
						break;
					case 5:
						$quality = '重度污染';
						break;
					default:
						$quality = '记录异常';
						break;
				}
				$html = '<div id="showlive" title="code:'.$code.'" >
						  	 <div>设备：'.$name.'</div>
							 <div>温度：'.$row['temp'].'℃</div>
						     <div>相对湿度：'.$row['rh'].'%</div>
						     <div>CO2浓度：'.$row['co2'].'ppm</div>
						     <div>PM2.5浓度：'.$row['pm'].'ug/m3</div>
						     <div>甲醛浓度：'.$row['hcho'].'ug/m3</div>
						     <div>电源状况：'.$source.$power.'</div>
						     <div>空间判断：'.$space.'</div>
						     <div>空气质量：'.$quality.'</div>
						     <div>检测时间：'.date('m月d日 H时i分', $row['time']).'</div>
						     '.$expried.'
						 </div>';
				break;
			case '3004':
				//状态标识 十进制转十六进制判断
				$status = dechex( $row['status'] );
				$power = substr( $status, 0, 1 ) ? '正常' : '异常';
				$space = substr( $status, 1, 1 ) ? '室外' : '室内';
				//源数据是放大10000倍的数据  但是计量单位又mg变成ug则 除以10
				$row['hcho'] = $row['hcho'] / 10;
				$source = $row['source'] ? '外部供电' : '电池供电';
				$tt = time() - $row['time'];
				if( $tt > 3600 ){
					//超过一小时则显示离线
					$expried = "<div style='color:red;'>当前设备已离线</div>";
				}else{
					$expried = '';
				}
				switch ( $row['iaq'] ){
					case 1:
						$quality = '优';
						break;
					case 2:
						$quality = '良';
						break;
					case 3:
						$quality = '轻度污染';
						break;
					case 4:
						$quality = '中度污染';
						break;
					case 5:
						$quality = '重度污染';
						break;
					default:
						$quality = '记录异常';
						break;
				}
				$html = '<div id="showlive" title="code:'.$code.'" >
					  	 <div>设备：'.$name.'</div>
						 <div>温度：'.$row['temp'].'℃</div>
					     <div>相对湿度：'.$row['rh'].'%</div>
					     <div>PM2.5浓度：'.$row['pm'].'ug/m3</div>
					     <div>甲醛浓度：'.$row['hcho'].'ug/m3</div>
					     <div>电源状况：'.$source.$power.'</div>
					     <div>空间判断：'.$space.'</div>
					     <div>空气质量：'.$quality.'</div>
					     <div>检测时间：'.date('m月d日 H时i分', $row['time']).'</div>
					     '.$expried.'
					 </div>';
				break;
			case '0001':
				$row['voltage'] = $row['voltage'] / 10;
				$row['current'] = $row['current'] / 100;
				$row['power'] = $row['power'] / 10;
				$row['electricity'] = $row['electricity'] / 1000;
				$html = '<div id="showlive" title="code:'.$code.'" >
				  	 <div>设备：'.$name.'</div>
					 <div>电压：'.$row['voltage'].'V</div>
				     <div>电流：'.$row['current'].'A</div>
				     <div>功率：'.$row['power'].'W</div>
				     <div>电量：'.$row['electricity'].'kWh</div>
				     <div>检测时间：'.date('m月d日 H时i分', $row['time']).'</div>
				 </div>';
				break;
			case '0002':
				$html = '';
				break;
			default:
				$html = '';
		}
		return $html;
	}
	
}