<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Setcolor extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'lamp_model', 'lamp' );
		$this->load->model( 'device_model', 'device' );
	}
	
	
	/**
	 * 更改颜色接口
	 * 1.不需要登录
	 * 2.使用cookie记录当前用户在随机状态下的颜色
	 */
	public function index(){
		$code = $this->input->get('code');
		//判断当前登录人   测试时期默认 guojunyan1
		$user_id = 21;
		if( $this->checkCode( $code ) ){
			//当前设备信息
			$user_device = $this->device->device_info( array( 'user_id'=>$user_id, 'code'=>$code ), 'user_device' );
			if( $user_device['pid'] > 0 ){
				//主机设备
				$parent = $this->device->device_info( array( 'id'=>$user_device['pid'] ), 'user_device' );
				//随机模式
				$day = empty( $_COOKIE['lucDay'] ) ? '' : $_COOKIE['lucDay'];
				//显示的颜色
				$color = empty( $_COOKIE['color'] ) ? '' : $_COOKIE['color'];
				//显示的图片
				$tcolor = empty( $_COOKIE['tcolor'] ) ? '' : $_COOKIE['tcolor'];
				//一共12种颜色
				$num = empty( $_COOKIE['num'] ) ? '' : $_COOKIE['num'];
				if( $day == date('Ymd') && !empty( $color ) ){
					$showColor = explode( ',', $color );
				}else{
					$num = mt_rand( 1, 9 );
					$resColor = $this->randomColor( $num );
					$tcolor = $resColor['tcolor'];
					$showColor = $resColor['color'];
					$color = implode( ',', $showColor );
					setcookie( "lucDay", date('Ymd'), time()+3600*24 );
					setcookie( "color", $color, time()+3600*24 );
					setcookie( "tcolor", $resColor['tcolor'], time()+3600*24 );
					setcookie( "num", $num, time()+3600*24 );
				}
				//编辑socket编码
				$sendcode = $this->lamp->smallLight( $parent['code'], $code, $showColor );
				//发起socket变色效果
				$res = $this->lamp->childSendCode( $sendcode );
				$sc = explode( ',', $color );
				unset( $sc[3] );
				$color = implode( ',', $sc );
				$this->load->view( 'lamp/index.html', array( 'num'=>$num, 'color'=>$color, 'tcolor'=>$tcolor ) );
			}else{
				echo '找不到该设备的控制主机';
			}
		}else{
			echo '设备类型异常,该设备没有自由控制权限';
		}
	}
	
	
	/**
	 * 随机颜色
	 */
	private function randomColor( $num = 1 ){
		switch( $num ){
			case 1:
				$color = array( 155,200,60,0 );		//青绿色
				$tcolor = '青绿色';
				break;
			case 2:
				$color = array( 180,10,200,0 );		//紫色
				$tcolor = '紫色';
				break;
			case 3:
				$color = array( 255,0,60,0 );		//紫红色
				$tcolor = '紫红色';
				break;
			case 4:
				$color = array( 0,0,255,0 );		//蓝色
				$tcolor = '蓝色';
				break;
			case 5:
				$color = array( 255,120,0,0 );		//橙黄色
				$tcolor = '橙黄色';
				break;
			case 6:
				$color = array( 255,0,0,0 );		//红色
				$tcolor = '红色';
				break;
			case 7:
				$color = array( 255,15,0,0 );		//橙红色
				$tcolor = '橙红色';
				break;
			case 8:
				$color = array( 0,255,0,0 );		//绿色
				$tcolor = '绿色';
				break;
			case 9:
				$color = array( 0,0,0,50 );			//白色
				$tcolor = '白色';
				break;

		}
		return array( 'color'=>$color, 'tcolor'=>$tcolor );
	}
	
	
	/**
	 * 验证设备码
	 */
	private function checkCode( $code ){
		if( strlen( $code ) == 12 ){
			$type = substr( $code, 0, 4 );
			if( $type == 4002 ){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * 验证颜色参数
	 */
	private function checkColor( $color ){
		if( strlen( $color ) == 6 && preg_match('/^[a-f0-9]{6}$/i', $color ) ){
			$param = str_split ( $color, 2 );
			$res = array();
			foreach( $param as $pm ){
				$res[] = hexdec( $pm );
			}
			return $res;
		}else{
			return false;
		}
	}
	
}