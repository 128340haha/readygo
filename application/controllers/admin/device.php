<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		is_login( $this, 'admin' );
		$this->load->model( 'Device_model', 'device' );
		$this->load->helper('html');
		if( $this->input->is_ajax_request() || !empty( $_GET['noheadfoot'] ) ){
			//ajax访问不加载页头和页尾
		}else{
			$admin = $this->session->userdata('admin');
			$data = array( 'from_url'=>$this->input->server('HTTP_REFERER'), 'admin'=>$admin );
			$this->load->view( 'admin/header.html', $data );	
			$this->foot = $this->load->view( 'admin/foot.html', '', true );
		}
	}
	
	/**
	 * 设备列表
	 */
	public function index(){
		$type_id = $this->input->get('type_id');
		$code = $this->input->get('code');
		$where = array();
		if( !empty( $type_id ) ){
			$where['device.type_id'] = $type_id;
		}
		if( !empty( $code ) ){
			$where['device.code'] = $code;
		}
		$page = $this->input->get('page');
		$device_list = $this->device->all_device( $where, 10, $page );
		$page_bar = $this->device->make_bar();
		
		$this->load->view( 'admin/all_device.html', array( 'list'=>$device_list, 'from_url'=>$_SERVER['REQUEST_URI'], 'foot'=>$this->foot, 'bar'=>$page_bar ) );
		
	}
	
	/**
	 * 设备参数信息
	 */
	public function device_param(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		//设备信息
		$device_info = $this->device->device_info( array( 'id'=>$id ) );
		//分类信息
		$types = $this->device->get_type();
		//参数组合
		$data = array_merge( $device_info, array( 'foot'=>$this->foot, 'types'=>$types, 'action'=>'edit' ) );
		$this->load->view( 'admin/device_info.html', $data );
	}
	
	/**
	 * 添加设备
	 */
	public function device_add(){
		//分类信息
		$types = $this->device->get_type();
		$device_info = array('device_id'=>'','device_name'=>'','code'=>'','id'=>'','type_id'=>'');
		$data = array_merge( $device_info, array( 'foot'=>$this->foot, 'types'=>$types, 'action'=>'add' ) );
		$this->load->view( 'admin/device_info.html', $data );
	}
	
	
	/**
	 * 设备增加修改操作
	 */
	public function device_action(){
		$action = $this->input->post('action');
		$id = $this->input->post('id');
		$from_url = $this->input->post('from_url');
		if( $action == 'edit' && !empty( $id ) ){
			//编辑
			$param = array(
				'device_name'	=>	trim( $this->input->post('device_name') )
			);
		}elseif( $action == 'add' ){
			//添加
			$param = array(
				'device_name'	=>	trim( $this->input->post('device_name') ),
				'type_id'		=>	$this->input->post('type_id')
			);
		}else{
			gotourl( '重要参数丢失' );
		}
		$res = $this->device->device_act( $action, $param, $id );
		if( $res ){
			gotourl( '操作成功', $from_url, 0 );
		}else{
			gotourl( '操作失败' );
		}
	}
	
	/**
	 * 分类列表
	 */
	public function device_types(){
		//分类信息
		$types = $this->device->get_type();
		$this->load->view( 'admin/device_types.html', array( 'list'=>$types, 'foot'=>$this->foot ) );
	}
	
	/**
	 * 添加分类
	 */
	public function type_add(){
		$data = array( 'id'=>'', 'type_name'=>'', 'action'=>'add', 'foot'=>$this->foot, 'priv'=>0 );
		$this->load->view( 'admin/type_info.html', $data );
	}
	
	
	/**
	 * 编辑分类
	 */
	public function type_edit(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl('重要参数丢失');
		}
		$info = $this->device->device_info( array( 'id'=>$id ), 'device_type' );
		$data = array_merge( $info, array( 'action'=>'edit', 'foot'=>$this->foot ) );
		$this->load->view( 'admin/type_info.html', $data );
	}
	
	
	
	/**
	 * 增加或修改分类信息
	 */
	public function update_type(){
		$action = $this->input->post('action');
		$id = $this->input->post('id');
		$type_name = $this->input->post('type_name');
		$priv = $this->input->post('priv');
		$from_url = $this->input->post('from_url');
		if( !empty( $id ) && !empty( $type_name ) ){
			if( $action == 'add' ){
				//添加
				$info = $this->device->device_info( array( 'id'=>$id ), 'device_type' );
				//验证id是否唯一
				if( !empty( $info['id'] ) ){
					gotourl( '该id已经存在' );
				}
				$param = array( 'id'=>$id, 'type_name'=>$type_name, 'priv'=>$priv );	
			}elseif( $action == 'edit' ){	
				$param = array( 
					'id'		=>	$id, 
					'type_name'	=>	$type_name,
					'ori_id'	=>	$this->input->post('ori_id'),
					'priv'		=>	$priv
				);
			}
			$res = $this->device->type_act( $action, $param );
			if( $res ){
				gotourl( '操作成功', $from_url, 0 );
			}else{
				gotourl( '操作失败' );
			}
		}else{
			gotourl( '主要参数丢失' );
		}
	}
	
	/**
	 * 设备已经绑定用户列表
	 */
	public function device_users(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$info = $this->device->device_info( array( 'id'=>$id ) );
		$user_list = $this->device->users( $info['code'] );
		$data = array( 'list'=>$user_list, 'foot'=>$this->foot, 'device_name'=>$info['device_name'], 'code'=>$info['code'] );
		$this->load->view( 'admin/device_users.html', $data );
	}
	
	/**
	 * 取消绑定
	 */
	public function cancel_user(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		$res = $this->device->cancel_ud( array( 'id'=>$id ) );
		if( $res ){
			gotourl( '操作成功' );
		}else{
			gotourl( '操作失败' );
		}
	}
	
	
	/**
	 * 删除
	 */
	public function delete_device(){
		$id = $this->input->get('id');
		$from_url = $this->input->get('from_url');
		if( empty( $id ) ){
			gotourl('重要参数丢失');
		}
		$res = $this->device->delete_device( array( 'id'=>$id ) );
		if( $res ){
			gotourl( '删除成功', $from_url, 0 );
		}else{
			gotourl( '删除失败' );
		}
	}
	
	
	/**
	 * 设备耗能数据
	 */
	public function device_data(){
		$id = $this->input->get('id');
		$date = $this->input->get('date');
		$model = $this->input->get('model');
		$model = $model ? $model : 1;
		
		if( empty( $id ) ){
			gotourl( '重要参数丢失' );
		}
		//设备信息
		$device_info = $this->device->device_info( array( 'id'=>$id ) );
		if( empty( $device_info['code'] ) ){
			gotourl( '没有该设备的绑定信息' );
		}
		if( empty( $date ) ){
			//今天
			$time = time();
		}else{
			//选定日期
			$time = strtotime( $date );
		}
		//构建时间条件
		//月份日期数
		$t = date('t',$time);
		$dinfo = $this->device->time_fomate( $time, $model );
		if( !$dinfo ){
			gotourl( '查询日期或者模式异常' );
		}else{
			//组织where
			$where = array( 
				'device_code'	=>	$device_info['code'],
				'time >='		=>	$dinfo['go_time'],
				'time <='		=>	$dinfo['end_time']
			);
			$list = $this->device->collect_data( $where, $dinfo['db'], '', $model );
			if( $list ){
				$value = array();
				foreach ( $list as $li ){
					$value[] = $li['electricity'];
				}
				$res = implode( ',', $value );
			}else{
				$res = '';
			}
			//参数构建
			$param = array(
					'id'	=>	$id,
					'res'	=>	urlencode($res), 
					'date'	=>	$date, 
					'model'	=>	$model, 
					't'		=>	$t
			);
			$data = array_merge( $param, $device_info );
			$this->load->view( 'admin/device_data.html', $data );
		}

	}
	
	/**
	 * 显示
	 */
	public function show_plot(){
		$value = $this->input->get('v');
		$key = $this->input->get('k');
		$model = $this->input->get('m');
		$month = $this->input->get('t');
		if( empty( $value ) || empty( $key ) || empty( $month ) ){
			echo '没有找到您查询的数据';
			exit;
		}else{
			$value = str_replace( ':', ',', $value );
			$key = str_replace( ':', ',', $key );
		}

		$this->load->view( 'admin/show_plot.html', array('data'=>$value,'key'=>$key,'model'=>$model,'month'=>$month) );
	}
	
	
	/**
	 * ajax验证id
	 */
	public function cktype(){
		$id = $this->input->post('id');
		$ori_id = $this->input->post('ori_id');
		if( empty( $id ) || !is_numeric( $id ) ){
			echo 'false';
		}else{
			if( empty( $ori_id ) ){
				$info = $this->device->device_info( array( 'id'=>$id ), 'device_type' );
			}else{
				$info = $this->device->device_info( array( 'id'=>$id, 'id !'=>$ori_id ), 'device_type' );
			}
			if( empty( $info['id'] ) ){
				echo 'true';
			}else{
				echo 'false';
			}
		}
	}

	/**
	 *  批量生成设备码
	 */
	public function batch_add(){
		//分类数量统计
		$res = $this->device->type_number();
		$data = array( 'types'=>$res, 'foot'=>$this->foot );
		$this->load->view( 'admin/batch_add.html', $data );
	}
	
	/**
	 * 批量生成操作
	 */
	public function batch_action(){
		$type_id = $this->input->post('type_id');
		$number = $this->input->post('number');
		$device_name = $this->input->post('device_name');
		if( empty( $type_id ) || empty( $number ) || empty( $device_name) ){
			gotourl( '分类id,数量,设备名不能为空' );
		}
		$res = $this->device->batch_add( $type_id, $number, $device_name );
		//添加的数据列表
		$list = $this->device->outData_list();
		//生成excel并且返回地址
		$url = $this->make_excel( $list );
		if( $res && $url ){
			gotourl( '操作成功', base_url('admin/device/index'), 0, 'admin', 0, $url );
		}else{
			gotourl( '操作失败' );
		}
		
	}

	/**
	 * 生成excel
	 */
	function make_excel( $data ){
		if( empty( $data ) || !is_array( $data ) ){
			return false;
		}
		error_reporting(E_ALL);
		define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
		$this -> load -> library('PHPExcel');

		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set document properties
		$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
									 ->setLastModifiedBy("Maarten Balliauw")
									 ->setTitle("PHPExcel Document")
									 ->setSubject("PHPExcel Document")
									 ->setDescription("Document for PHPExcel, generated using PHP classes.")
									 ->setKeywords("PHPExcel php")
									 ->setCategory("result file");

		// Add some data
		$objPHPExcel->setActiveSheetIndex()->setCellValue('a1', '设备码')
						          ->setCellValue('b1', '设备名称')
						          ->setCellValue('c1', '校验码');

		$COUNT = 1;
		foreach ( $data  as  $dt) {
			$COUNT++;
			$objPHPExcel->getActiveSheet()->setCellValue('a'.$COUNT, $dt['code'] )
							->setCellValue('b'.$COUNT, $dt['device_name'] )
							->setCellValue('c'.$COUNT, $dt['salt'] );
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
	
		/*Save Excel 2007 file
		echo date('H:i:s') , " Write to Excel2007 format" , EOL;
		$callStartTime = microtime(true);

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save(str_replace('.php', '.xlsx', __FILE__));
		$callEndTime = microtime(true);
		$callTime = $callEndTime - $callStartTime;

		echo date('H:i:s') , " File written to " , str_replace('.php', '.xlsx', pathinfo(__FILE__, PATHINFO_BASENAME)) , EOL;
		echo 'Call time to write Workbook was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
		// Echo memory usage
		echo date('H:i:s') , ' Current memory usage: ' , (memory_get_usage(true) / 1024 / 1024) , " MB" , EOL;
		*/

		// Save Excel 95 file
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		if( !is_dir( getcwd().'/files/'.date('Ym') ) ){
			mkdir( getcwd().'/files/'.date('Ym') );
		}
		$notime = time();
		$outFileName = 'files/'.date('Ym').'/'.$notime.'.xls';
		$objWriter->save( getcwd().'/'.$outFileName );
		return base_url().$outFileName;
	}
	
	/**
	 * 主从列表
	 */
	public function device_parent(){
		$type_id = $this->input->get('type_id');
		$code = $this->input->get('code');
		$user_id = $this->input->get('user_id');
		$where = array();
		if( !empty( $type_id ) ){
			$where['device.type_id'] = $type_id;
		}
		if( !empty( $user_id ) ){
			$where['user_device.user_id'] = $user_id;
		}
		if( !empty( $code ) ){
			$where['device.code'] = $code;
		}
		$page = $this->input->get('page');
		$device_list = $this->device->device_list( $where, 10, '*', $page );

		$page_bar = $this->device->make_bar();
		$search = array( 'type_id'=>$type_id, 'code'=>$code, 'user_id'=>$user_id );
		$this->load->view( 'admin/device_parent.html', array( 'list'=>$device_list, 'search'=>$search, 'from_url'=>$_SERVER['REQUEST_URI'], 'foot'=>$this->foot, 'bar'=>$page_bar ) );
	}
	
	//设定主机
	public function set_parent(){
		$id = $this->input->get('id');
		if( !empty( $id ) ){
			$info = $this->device->device_info( array( 'id'=>$id ), 'user_device' );
			$this->load->view( 'admin/set_parent.html', $info );
		}else{
			gotourl( '参数丢失' );
		}
	}
	
	//操作主机
	public function update_parent(){
		$code = $this->input->post('code');
		$user_id = $this->input->post('user_id');
		$pid = $this->input->post('pid');
		$from_url = $this->input->post('from_url');
		if( !empty( $code ) && !empty( $user_id ) ){
			$pid = $pid ? $pid : 0;
			//验证id是否存在
			$info = $this->device->device_info( array( 'code'=>$code, 'user_id'=>$user_id ), 'user_device' );
			if( $info ){
				//存在则修改
				if( $pid === 0 ){
					//解除绑定
					$param = array( 'pid'=>0 );
				}else{
					$pinfo = $this->device->device_info( array( 'user_id'=>$user_id, 'id'=>$pid ), 'user_device' );
					if( $pinfo ){
						//验证两边权限
						$cpass = $this->device->ck_authority( $code, 'child' );
						$ppass = $this->device->ck_authority( $pinfo['code'], 'parent' );
						if( $cpass && $ppass ){
							$param = array( 'pid'=>$pid );
						}elseif( $cpass ){
							gotourl( '您设定的主机没有控制权利' );
							return;
						}elseif ( $ppass ){
							gotourl( '您当前设备为主机设备,无法设定为子设备' );
							return;
						}else{
							gotourl( '主机无控制权，子设备拥有主机权限' );
							return;
						}
					}else{
						gotourl( '请确认主机与子设备为同一用户' );
						return;
					}
					$str = $this->device->user_device_edit( $param, array( 'user_id'=>$user_id, 'code'=>$code ) );
					if( $str ){
						gotourl( '设定成功', $from_url, 0 );
					}else{
						gotourl( '操作失败' );
					}
				}
				
			}
			
		}
		
		
	}
	

}