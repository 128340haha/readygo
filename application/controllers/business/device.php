<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Device extends CI_Controller {
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Device_model', 'device' );
		$this->load->helper('html');
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
		is_login( $this, 'business' );
	}

	/**
	 * 商家的设备列表
	 */
	public function device_list(){
		//分类列表
		$types = $this->device->get_type();
		//查询信息
		$code = $this->input->get('code');
		$type_id = $this->input->get('type_id');
		$page = $this->input->get('page');
		$excel = $this->input->get('excel');
		$where = array();
		$pm = null;
		//搜索分类
		if( !empty( $type_id ) ){
			$where['device.type_id']  = $type_id;
			$pm .= '&type_id='.$type_id;
		}
		//搜索设备码
		if( !empty( $code ) ){
			$where['device.code']  = $code;
		}
		//锁定用户
		$where['supplier'] = $_SESSION['uid'];
		//查询
		$list = $this->device->all_device( $where, 15, $page );
		if( $excel == 1 ){
			$url = $this->make_excel( $list, 2 );
		}
		$page_bar = $this->device->make_bar( 3, $pm );
		$page_info = $this->device->make_bar( 2 );
		$data = array( 'types'=>$types, 'list'=>$list, 'bar'=>$page_bar, 'code'=> $code, 'type_id'=> $type_id, 'page'=>$page_info );
		$this->load->view( 'business/device_list.html', $data );
		
	}
	
	/**
	 * 添加设备
	 */
	public function device_add(){
		$types = $this->device->type_number();
		$info = array( 'device_name'=>'', 'type_id'=>'', 'status'=>'', 'code'=>'' );
		$data = array('types'=>$types, 'action'=>'add', 'info'=>$info, 'did'=>'' );
		$this->load->view( 'business/device_action.html', $data );
	}
	
	
	/**
	 * 修点设备信息
	 */
	public function device_edit(){
		$id = $this->input->get('id');
		if( empty( $id ) ){
			gotourl( '重要参数丢失', 'back', 1, 'business' );
		}
		//自己的设备
		$supplier = $_SESSION['uid'];
		$info = $this->device->device_info( array( 'id'=>$id, 'supplier'=>$supplier ) );
		if( !$info ){
			gotourl( '您没有该设备的操作权', 'back', 1, 'business' );
		}
		//分类信息
		$type = $this->device->device_info( array( 'id'=>$info['type_id'] ), 'device_type' );
		$info['type_name'] = $type['type_name'];
		$data = array( 'action'=>'edit', 'info'=>$info, 'did'=>$id );
		$this->load->view( 'business/device_action.html', $data );
	}
	
	
	/**
	 * 操作
	 */
	public function device_action(){
		$action = $this->input->post('action');
		$device_name = $this->input->post('device_name', true);
		$status = $this->input->post('status');
		$from_url = $this->input->post('from_url');
		$uid = $_SESSION['uid'];
		if( $action == 'add' ){
			$type_id = $this->input->post('type_id');
			$param = array(
				'type_id'		=>	$type_id,
				'device_name'	=>	trim( $device_name ),
				'status'		=>	$status,
				'supplier'		=>	$uid,
			);
			$id = null;
		}elseif( $action == 'edit' ){
			$id = $this->input->post('did');
			$param = array(
				'device_name'	=>	trim( $device_name ),
				'status'		=>	$status,
			);	
		}
		$res = $this->device->device_act( $action, $param, $id );
		if( !$res ){
			gotourl( '操作成功', $from_url, 1, 'business' );
		}else{
			gotourl( '操作失败', 'back', 0, 'business' );
		}
	}
	
	
	/**
	 *  批量生成设备码
	 */
	public function batch_add(){
		//分类数量统计
		$res = $this->device->type_number();
		$data = array( 'types'=>$res );
		$this->load->view( 'business/batch_add.html', $data );
	}
	
	
	/**
	 * 批量生成操作
	 */
	public function batch_action(){
		$type_id = $this->input->post('type_id');
		$number = $this->input->post('number');
		$device_name = $this->input->post('device_name');
		$status = $this->input->post('status');
		if( empty( $type_id ) || empty( $number ) || empty( $device_name) ){
			gotourl( '分类id,数量,设备名不能为空', 'back', 0, 'business' );
		}
		$supplier = $_SESSION['uid'];
		$res = $this->device->batch_add( $type_id, $number, $device_name, $supplier, $status );
		//添加的数据列表
		$list = $this->device->outData_list();
		//生成excel并且返回地址
		$url = $this->make_excel( $list );
		if( $res && $url ){
			gotourl( '操作成功', base_url('business/device/device_list'), 1, 'business', 0, $url );
		}else{
			gotourl( '操作失败', 'back', 0, 'business' );
		}
	
	}
	
	
	/**
	 * 更改设备状态
	 */
	public function change_status(){
		$value = $this->input->post('val');
		$did = $this->input->post('did');
		if( empty( $did ) ){
			echo json_encode( array( 'res'=>2, 'info'=>'重要参数丢失' ) );
		}else{
			$this->device->device_act( 'edit', array('status'=>$value), $did );
			echo json_encode( array( 'res'=>1, 'info'=>'' ) );
		}
	}
	
	/**
	 * 生成excel
	 */
	function make_excel( $data, $model = 1 ){
		if( empty( $data ) || !is_array( $data ) ){
			return false;
		}
		error_reporting(E_ALL);
		define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
		$this->load->library('PHPExcel');
	
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
		->setCellValue('c1', '校验码')
		->setCellValue('d1', '开启状态');;
	
		$COUNT = 1;
		foreach ( $data  as  $dt) {
			$COUNT++;
			$objPHPExcel->getActiveSheet()->setCellValue('a'.$COUNT, $dt['code'] )
			->setCellValue('b'.$COUNT, $dt['device_name'] )
			->setCellValue('c'.$COUNT, $dt['salt'] )
			->setCellValue('d'.$COUNT, $dt['status'] ? '开启' : '关闭' );
		}
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
		$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
		$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
	
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
		if( $model == 1 ){
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			if( !is_dir( getcwd().'/files/'.date('Ym') ) ){
				mkdir( getcwd().'/files/'.date('Ym') );
			}
			$notime = time();
			$outFileName = 'files/'.date('Ym').'/'.$notime.'.xls';
			$objWriter->save( getcwd().'/'.$outFileName );
			return base_url().$outFileName;
		}elseif ( $model == 2 ){
			// Redirect output to a client’s web browser (Excel5)
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="我的设备列表.xls"');
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');
			
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0
			
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('php://output');	
			return true;
		}
	}
	
}