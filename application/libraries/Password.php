<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Password{
	
	//构造方法
	public function __construct(){
		
	}
	
	/**
	 * 用户加密方式
	 * @param unknown $pass
	 * @param unknown $salt
	 * @return boolean|string
	 */
	public function makePass( $pass, $salt ){
		if( empty( $pass ) || empty( $salt ) ){
			return false;
		}else{
			return md5( md5( $pass ).md5( $salt ) );  
		}
	}
	
	
	/**
	 * 管理员加密方式
	 * @param unknown $pass
	 * @param unknown $salt
	 * @return boolean|string
	 */
	public function adminPass( $admin, $pass, $salt ){
		if( empty( $pass ) || empty( $salt ) || empty( $admin ) ){
			return false;
		}else{
			return md5( md5( $admin, true ).md5( $pass, true ).md5( $salt, true ) );
		}
	}
	
} 