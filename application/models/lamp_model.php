<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lamp_model extends CI_Model{
	private $device_ip = '';		//这是声物的，暂定121.40.80.9
	private $port = 0;
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
	}
	
	
	/*
		根据文档描述，68开始，之后是长度， 1字节8位  1byte = 8bit
		长度是：一共有多少‘位’合计,显示2 个字节，表示长度到效验码之间的字节个数，低字节在前，高字节在后。控制码与数据码就算是0也要计算位置，而数据域没参数则不需要
		校验码是前面所有的数据之和，包括68
        $arr = pack('Cv2VC7',0x68,0x8,4001|0xC000,0x1,0x2,0x0,0xFF,0x00,0xFF,0x0,0x16);
 	    $str = unpack("C*",call_user_func_array('pack', $arr));
  	    print_r($str);
        array_unshift($str, 'C*');
        print_r(unpack('C*',call_user_func_array('pack', $str)));
     	字节序-》当一个数据时单字节时，不同倒序，比如RGBY，如果一个数据占两个字节以上，则需要倒序
     	大C代表一个字节，小v一般是2个字节，而4个字节的用大V  V代表倒序，如果顺序使用n 数字则跟随前面的字符  v2N 相当于 vvN

	*/
	
	/*
	 *	小云灯，变色子类控制
	 */
	public function smallLight( $host, $code, $showcolor ){
		$bench = strlen( $code ) - 8;
		$type = (int)substr( $code, 0, $bench );
		$id = substr( $code, $bench );
		//变色需求
		$control = 0x02;
		$data = 0x00;
		//编码组合
		$arr = pack( 'vVC6', $type, $id, $control, $data, $showcolor[3], $showcolor[2], $showcolor[1], $showcolor[0] );
		$length = strlen( $arr );
		$arrLen = pack( 'Cv', 0x68, $length );
		//算cs
		$cs = 0;
		$arr = $arrLen.$arr;
		//计算cs从头0x68开始 长度2位
		$length += 3;
		for ( $i = 0; $i<$length; $i++ ){
			$cs += ord($arr[$i]);
		}
		$cs %= 256;
		//云灯控制码
		$arr .= chr($cs).chr(0x16);
		
		return $this->doubPackage( $host, $arr );
	}
	
	/*
	 * 小云灯数据获取
	*/
	public function smallLightGet( $host, $code ){
		$bench = strlen( $code ) - 8;
		$type = (int)substr( $code, 0, $bench );
		$id = substr( $code, $bench );
		//获取数据
		$control = 0x01;
		$data = 0x00;
		//编码组合
		$arr = pack( 'vVC2', $type, $id, $control, $data );
		$length = strlen( $arr );
		$arrLen = pack( 'Cv', 0x68, $length );
		//算cs
		$cs = 0;
		$arr = $arrLen.$arr;
		//计算cs从头0x68开始 长度2位
		$length += 3;
		for ( $i = 0; $i<$length; $i++ ){
			$cs += ord($arr[$i]);
		}
		$cs %= 256;
		//云灯控制码
		$arr .= chr($cs).chr(0x16);
	
		return $this->doubPackage( $host, $arr );
	}
	
	
	/**
	 * 双重封装
	 * @param string $host	主机控制码
	 * @param string $code	目标设备码
	 * @param string $showcolor 改变颜色
	 */
	public function doubPackage( $host, $arr ){
		//主机控制码
		$pbench = strlen( $host ) - 8;
		$ptype = (int)substr( $host, 0, $pbench );
		$ptype |= 0xC000;
		$pid = substr( $host, $pbench );

		//控制需求
		$pcontrol = 0x09;
		$pdata = 0x00;
		//数据码组合
		$parr = pack( 'vVC2', $ptype, $pid, $pcontrol, $pdata ) . $arr;
		//长度计算
		$plength = strlen( $parr );//05 00
		//cs之前的参数设定完成
		$parrLen = pack( 'Cv', 0x68, $plength );
		//最终cs
		$pcs = 0;
		$parr = $parrLen.$parr;
		$plength += 3;
		//计算cs
		for ( $n = 0; $n < $plength; $n++ ){
			$pcs += ord($parr[$n]);
		}
		$pcs %= 256;
		
		$last_arr = $parr.chr($pcs).chr(0x16);
		
	/*	for ( $i = 0; $i<strlen($arr); $i++ ){
			printf("%02x ", ord($arr[$i]));
		}
	*/	
		return $last_arr;
	}
	
	
	
	/**
	 * 发送格式化
	 */
	public function makeSend( $code, $showColor ){
		$bench = strlen( $code ) - 8;
		$type = (int)substr( $code, 0, $bench );
		$id = substr( $code, $bench );
		//发送到服务器，前两位置1 (自定义)->0xC000就是1100 0000 0000 0000	=》并且11代表客户端发送
		$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
		$hexId = str_split(sprintf('%08X', (int)$id), 2);	
		if( $showColor[0] == 0 && $showColor[1] == 0 && $showColor[2] == 0 ){
			$light = 'FF';
		}else{
			$light = '00';
		}
		//参数组合(占2个字节的，就要有2个位数)
		$infomation = array( '68', '0C', '00', $hextype[1], $hextype[0], $hexId[3], $hexId[2], $hexId[1], $hexId[0], '02', '00', sprintf('%02X',$showColor[0]), sprintf('%02X', $showColor[1]), sprintf('%02X', $showColor[2]), $light );
		//校验
		$ck = 0;
		foreach ( $infomation as $if ){
			//字符转数值
			$ck += hexdec( $if );
		}
		//溢出只拿最后两位
		$ck = sprintf('%02X', $ck%256 );
		array_push( $infomation, $ck, '16' );
		return implode( '', $infomation );
	}
	
	/**
	 * 子设备特别发送
	 */
	public function childSendCode( $send_buff, $act = 0x00 ){
		// 创建 socket
		$socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		//超时设置
		socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>1, "usec"=>500000));
		//链接 socket

		$connection = socket_connect( $socket, $this->device_ip, $this->port );
		
		if(!$connection){socket_close($socket);exit;}
		
	//	$send_buff = pack( "H*", $send_buff );
		//写数据到socket缓存
		if(!socket_send($socket, $send_buff,strlen($send_buff),0)){
			socket_close($socket);
			return array( 0, 'send failed' );
		}

		$buffer=@socket_read($socket,1024,PHP_BINARY_READ);

		if($buffer){
			$bin = bin2hex($buffer);
			$ff = str_split( $bin, 2 );

			if( count( $ff ) >= 13 ){
				if( hexdec( $ff[9] ) == $act ){
					socket_close($socket);
					return array( 1, $ff );
				}elseif( hexdec( $ff[9] ) == 0xc3 ){
					socket_close($socket);
					return array( 0, '设备忙' );
				}elseif( hexdec( $ff[9] ) == 0xc4 ){
					socket_close($socket);
					return array( 0, '设备不在线' );
				}else{
					socket_close($socket);
					return array( 0, '数据无法解析' );
				}
			}else{
				socket_close($socket);
				return array( 0, '通信出错' );
			}
		}else{
			socket_close($socket);
		/*	
			$strs = '';
			for($i = 0 ; $i < strlen($send_buff); ++$i) {
				$strs .= printf("%02x ", ord($send_buff[$i]));
			}
		*/	
			return array( 0, '响应超时或阻塞' );
			//访问信息无返回，可能信息阻塞，强制关闭访问
		}
	}
	
	
	/**
	 * 发送
	 */
	public function sendCode( $send_buff, $act = 0x00 ){
		// 创建 socket
		$socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		//超时设置
		socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>1, "usec"=>500000));
		//链接 socket

		$connection = socket_connect( $socket, $this->device_ip, $this->port );
		
		if(!$connection){socket_close($socket);exit;}
		
		$send_buff = pack( "H*", $send_buff );
		//写数据到socket缓存
		if(!socket_send($socket, $send_buff,strlen($send_buff),0)){
			socket_close($socket);
			return array( 0, 'send failed' );
		}

		$buffer=@socket_read($socket,1024,PHP_BINARY_READ);

		if($buffer){
			$bin = bin2hex($buffer);
			$ff = str_split( $bin, 2 );

			if( count( $ff ) >= 13 ){
				if( hexdec( $ff[9] ) == $act ){
					socket_close($socket);
					return array( 1, $ff );
				}elseif( hexdec( $ff[9] ) == 0xc3 ){
					socket_close($socket);
					return array( 0, '设备忙' );
				}elseif( hexdec( $ff[9] ) == 0xc4 ){
					socket_close($socket);
					return array( 0, '设备不在线' );
				}else{
					socket_close($socket);
					return array( 0, '数据无法解析' );
				}
			}else{
				socket_close($socket);
				return array( 0, '通信出错' );
			}
		}else{
			socket_close($socket);
		/*	
			$strs = '';
			for($i = 0 ; $i < strlen($send_buff); ++$i) {
				$strs .= printf("%02x ", ord($send_buff[$i]));
			}
		*/	
			return array( 0, '响应超时或阻塞' );
			//访问信息无返回，可能信息阻塞，强制关闭访问
		}
	}
	
	
	public function set_socket( $code = '', $act = '', $data = array() ){
		$bench = strlen( $code ) - 8;
		$token = substr( $code, 0, $bench );
		$type = (int)$token;
		$id = substr( $code, $bench );
		$res = array();
		if( $type > 0 && $type < 1000 ){
			//插板
			$this->port = '1'.$token;
			$res = $this->receptacle_change( $token, $id, $act, $data );
		}elseif( $type > 3000 && $type < 4000 ){
			//空测误操作	
		}elseif( $type > 4000 && $type < 5000 ){
			//云灯
			$res = $this->light_change( $token, $id, $act, $data );
		}elseif( $type > 6000 && $type < 7000 ){
			//自行车
				
		}
		return $res;
	}
	
	
	public function get_socket( $code = '', $source = '' ){
		$bench = strlen( $code ) - 8;
		$token = substr( $code, 0, $bench );
		$type = (int)$token;
		$id = substr( $code, $bench );
		$res = array();
		if( $type > 0 && $type < 1000 ){
			//插板
			$this->port = '1'.$token;
			$res = $this->receptacle_socket( $token, $id, 0x03 );
		}elseif( $type > 3000 && $type < 4000 ){
			//空测
			$this->port = '1'.$token;
			$res = $this->airck_socket( $token, $id, 0x03 );
		}elseif( $type > 4000 && $type < 5000 ){
			//云灯
			$source = $source == 'site' ? 'ff' : '00';
			$res = $this->light_socket( $token, $id, 0x01, $source );
		}elseif( $type > 6000 && $type < 7000 ){
			//自行车
			
		}
		return $res;
	}
	
	/**
	 * 单插控制
	 * @param unknown $type
	 * @param unknown $id
	 * @param string $act
	 * @param unknown $data
	 * @return Ambigous <multitype:number string , multitype:number multitype: >
	 */
	public function receptacle_change( $type, $id, $act = '', $data = array() ){
		switch ( $act ){
			case '0':		//关闭
				$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
				$hexId = str_split(sprintf('%08X', (int)$id), 2);
				$size = count( $hextype ) + count( $hexId ) + 2 + count( $data );
				$control = array( '02', '3f', '00' );
				break;
			case '1':		//打开
				$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
				$hexId = str_split(sprintf('%08X', (int)$id), 2);
				$size = count( $hextype ) + count( $hexId ) + 2 + count( $data );
				$control = array( '02', '3f', '01' );
				break;
		}
		$infomation = array( '68', '0A', '00', $hextype[1], $hextype[0], $hexId[3], $hexId[2], $hexId[1], $hexId[0], $control[0], $control[1], $control[2], '00' );
		//校验
		$ck = 0;
		foreach ( $infomation as $if ){
			//字符转数值
			$ck += hexdec( $if );
		}
		//溢出只拿最后两位
		$ck = sprintf('%02X', $ck%256 );
		array_push( $infomation, $ck, '16' );
		$sendcode = implode( '', $infomation );
		$respone = $this->sendCode( $sendcode, 0x82 );
		return $respone;
	}
	
	
	/**
	 * 云灯控制
	 * @param unknown $type
	 * @param unknown $id
	 * @param string $act
	 * @param unknown $data
	 * @return Ambigous <multitype:number string , multitype:number multitype: >
	 */
	public function light_change( $type, $id, $act = '', $data = array() ){
		switch ( $act ){
			case 0xff:		//关灯
				$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
				$hexId = str_split(sprintf('%08X', (int)$id), 2);
				$size = count( $hextype ) + count( $hexId ) + 2 + count( $data );
				$control = array( 'ff', '00' );
				break;
			case 0x00:		//开灯
				$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
				$hexId = str_split(sprintf('%08X', (int)$id), 2);
				$size = count( $hextype ) + count( $hexId ) + 2 + count( $data );
				$control = array( '00', '00' );
				break;	
			case 0x02:		//变色
				$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );
				$hexId = str_split(sprintf('%08X', (int)$id), 2);
				$value = explode( ',', $data );
				$size = count( $hextype ) + count( $hexId ) + 2 + count( $value );
				$control = array( '02', '00' );
				$datamail = array(sprintf('%02X',$value[0]), sprintf('%02X',$value[1]), sprintf('%02X',$value[2]), sprintf('%02X',$value[3]));
				break;
		}
		$infomation = array( '68', sprintf('%02X',$size), '00', $hextype[1], $hextype[0], $hexId[3], $hexId[2], $hexId[1], $hexId[0], $control[0], $control[1] );
		if( !empty( $datamail ) ){
			$infomation = array_merge( $infomation, $datamail );
		}
		//校验
		$ck = 0;
		foreach ( $infomation as $if ){
			//字符转数值
			$ck += hexdec( $if );
		}
		//溢出只拿最后两位
		$ck = sprintf('%02X', $ck%256 );
		array_push( $infomation, $ck, '16' );
		$sendcode = implode( '', $infomation );
		$respone = $this->sendCode( $sendcode, $act );
		return $respone;
	}
	
	/**
	 * 空测连接
	 * @param unknown $type
	 * @param unknown $id
	 * @param unknown $act
	 * @return multitype:string number Ambigous <string, number> unknown
	 */
	public function airck_socket( $type, $id, $act ){
		$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
		$hexId = str_split(sprintf('%08X', (int)$id), 2);
		
		//参数组合(占2个字节的，就要有2个位数)
		$infomation = array( '68', '08', '00', $hextype[1], $hextype[0], $hexId[3], $hexId[2], $hexId[1], $hexId[0], sprintf('%02X',$act), '0E' );
		//校验
		$ck = 0;
		foreach ( $infomation as $if ){
			//字符转数值
			$ck += hexdec( $if );
		}
		//溢出只拿最后两位
		$ck = sprintf('%02X', $ck%256 );
		array_push( $infomation, $ck, '16' );
		$sendcode = implode( '', $infomation );
		$act |= 0x80;
		$respone = $this->sendCode( $sendcode, $act );
		return $this->airck_fomat( $respone, $type );
	}
	
	
	/**
	 * 获取云灯参数
	 * @param unknown $type
	 * @param unknown $id
	 * @param unknown $act
	 * @return multitype:number string
	 */
	public function light_socket( $type, $id, $act, $source = '00' ){
		//发送到服务器，前两位置1 (自定义)->0xC000就是1100 0000 0000 0000	=》并且11代表客户端发送
		$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
		$hexId = str_split(sprintf('%08X', (int)$id), 2);
		
		//参数组合(占2个字节的，就要有2个位数)
		$infomation = array( '68', '08', '00', $hextype[1], $hextype[0], $hexId[3], $hexId[2], $hexId[1], $hexId[0], sprintf('%02X',$act), $source );
		//校验
		$ck = 0;
		foreach ( $infomation as $if ){
			//字符转数值
			$ck += hexdec( $if );
		}
		//溢出只拿最后两位
		$ck = sprintf('%02X', $ck%256 );
		array_push( $infomation, $ck, '16' );
		$sendcode = implode( '', $infomation );
		$respone = $this->sendCode( $sendcode, $act );
		return $this->light_fomat( $respone, $act );
	}
	
	
	/**
	 * 插板相关数据
	 * @param unknown $type
	 * @param unknown $id
	 * @param unknown $act
	 * @return multitype:string number Ambigous <string, number> unknown
	 */
	public function receptacle_socket( $type, $id, $act ){
		//发送到服务器，前两位置1 (自定义)->0xC000就是1100 0000 0000 0000	=》并且11代表客户端发送
		$hextype = str_split( sprintf('%04X',((int)$type)|0xC000), 2 );//000001
		$hexId = str_split(sprintf('%08X', (int)$id), 2);
		
		//参数组合(占2个字节的，就要有2个位数)
		$infomation = array( '68', '08', '00', $hextype[1], $hextype[0], $hexId[3], $hexId[2], $hexId[1], $hexId[0], sprintf('%02X',$act), '00' );
		//校验
		$ck = 0;
		foreach ( $infomation as $if ){
			//字符转数值
			$ck += hexdec( $if );
		}
		//溢出只拿最后两位
		$ck = sprintf('%02X', $ck%256 );
		array_push( $infomation, $ck, '16' );
		$sendcode = implode( '', $infomation );
		$act |= 0x80;
		$respone = $this->sendCode( $sendcode, $act );
		return $this->receptacle_fomat( $respone, $type );
	}
	
	
	
	/**
	 * 返回云灯数据格式化
	 * @param unknown $respone
	 */
	public function light_fomat( $respone, $act ){
		$res = array();
		if( $respone[0] == 1 ){
			//获取成功
			$result = $respone[1];	
			if( $act == 0x01 && $result[10] == '00' ){
				$res['switch'] = $result[11] == 0x01 ? '1' : 0;
				$res['color'] = $result[12].','.$result[13].','.$result[14];
				$res['light'] = hexdec( $result[15] );
				$res['error'] = 0;
			}elseif( $act == 0x01 && $result[10] == 'ff' ){
				$res['switch'] = $result[11] == 0x01 ? '1' : 0;
				$res['color'] = $result[12].','.$result[13].','.$result[14];
				$res['light'] = hexdec( $result[15] );
				$res['voltage'] = hexdec( $result[25].$result[24].$result[23].$result[22] ) / 100;
				$res['current'] = hexdec( $result[21].$result[20].$result[19].$result[18] ) / 1000;
				$res['power'] = hexdec( $result[29].$result[28].$result[27].$result[26] ) / 100;
				$res['electricity'] = round(hexdec( $result[33].$result[32].$result[31].$result[30] ) / 3200,3, PHP_ROUND_HALF_UP);
				$res['error'] = 0;
			}else{
				$res['switch'] = 0;
				$res['color'] = '00,00,00';
				$res['light'] = 0;
				$res['error'] = 1;
				$res['mess'] = '获取失败';
			}
		}else{
			//出错
			$res['switch'] = 0;
			$res['color'] = '00,00,00';
			$res['light'] = 0;
			$res['error'] = 1;	
			$res['mess'] = $respone[1];
		}
		return $res;
	}
	
	
	/**
	 * 返回空测数据格式化
	 * @param unknown $respone
	 */
	public function airck_fomat( $respone, $type ){
		$res = array();
		if( $respone[0] == 1 ){
			//获取成功
			$result = $respone[1];
			if( $type == '3002' ){
				$res['temp'] = hexdec( $result[11] );
				$res['rh'] = hexdec( $result[12] );
				$res['hcho'] = 0;
				$res['pm'] = hexdec( $result[16].$result[15] );
				$res['error'] = 0;
			}elseif( $type == '3003' ){
				$res['temp'] = hexdec( $result[11] );
				$res['rh'] = hexdec( $result[12] );
				$res['hcho'] = hexdec( $result[18].$result[17] ) / 10;
				$res['pm'] = hexdec( $result[16].$result[15] );
				$res['error'] = 0;
			}else{
				$res['temp'] = 0;
				$res['rh'] = 0;
				$res['hcho'] = 0;
				$res['pm'] = 0;
				$res['error'] = 1;
				$res['mess'] = '获取失败';
			}
		}else{
			//出错
			$res['temp'] = 0;
			$res['rh'] = 0;
			$res['hcho'] = 0;
			$res['pm'] = 0;
			$res['error'] = 1;
			$res['mess'] = $respone[1];
		}
		return $res;
	}
	
	
	/**
	 * 返回插座数据格式化
	 * @param unknown $respone
	 */
	public function receptacle_fomat( $respone, $type ){
		$res = array();	
		if( $respone[0] == 1 ){
			//获取成功
			$result = $respone[1];
			if( $type == '0001' ){
				$res['voltage'] = hexdec( $result[12].$result[11] ) / 10;
				$res['current'] = hexdec( $result[14].$result[13] ) / 100;
				$res['power'] = hexdec( $result[22].$result[21] ) / 10;
				$res['electricity'] = hexdec( $result[32].$result[31].$result[30].$result[29] ) / 1000;
				$res['switch'] = hexdec( $result[96] );
				$res['error'] = 0;
			}elseif( $type == '0002' ){
				$res['voltage'] = hexdec( $result[11] ) / 10;
				$res['current'] = hexdec( $result[14].$result[13] ) / 100;
				$res['power'] = hexdec( $result[22].$result[21] ) / 10;
				$res['electricity'] = hexdec( $result[32].$result[31].$result[30].$result[29] ) / 1000;
				$res['error'] = 0;
			}else{
				$res['voltage'] = 0;
				$res['current'] = 0;
				$res['power'] = 0;
				$res['electricity'] = 0;
				$res['switch'] = 0;
				$res['error'] = 1;
				$res['mess'] = '获取失败';
			}
		}else{
			//出错
			$res['voltage'] = 0;
			$res['current'] = 0;
			$res['power'] = 0;
			$res['electricity'] = 0;
			$res['switch'] = 0;
			$res['error'] = 1;
			$res['mess'] = $respone[1];
		}
		return $res;
	}
}