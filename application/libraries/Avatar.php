<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Avatar extends CI_Model{
	public function __construct() {
		parent::__construct();

	}
	// 本次页面请求的 url
	private function getThisUrl()	{
		$thisUrl = $_SERVER['SCRIPT_NAME'];
		$thisUrl = "http://{$_SERVER['HTTP_HOST']}{$thisUrl}";
		return $thisUrl;
	}

	// 本次页面请求的 base-url（尾部有 /）
	private function getBaseUrl()	{
		$baseUrl = $this->getThisUrl();
		$baseUrl = substr( $baseUrl, 0, strrpos( $baseUrl, '/' ) + 1 );
		return $baseUrl;
	}

	// 用于存储的本地文件夹（尾部有一个 DIRECTORY_SEPARATOR）
	private function getBasePath()	{
		$basePath = $_SERVER['SCRIPT_FILENAME'];
		$basePath = substr( $basePath, 0, strrpos($basePath, '/' ) + 1 );
		$basePath = str_replace( '/', DIRECTORY_SEPARATOR, $basePath );
		return $basePath;
	}

	// 第一步：上传原始图片文件
	private function uploadAvatar( $uid )	{
		// 检查上传文件的有效性
		if ( empty($_FILES['Filedata']) ) {
			return -3; // No photograph be upload!
		}

		// 本地临时存储位置
		$tmpPath = $this->getBasePath()."uploads".DIRECTORY_SEPARATOR."head".DIRECTORY_SEPARATOR.'tmp';

		// 如果临时存储的文件夹不存在，先创建它
		if ( !file_exists( $tmpPath ) ) {
			@mkdir( $tmpPath, 0777, true );
		}
		//文件路径
		$file = $tmpPath.DIRECTORY_SEPARATOR.$uid;
		// 如果同名的临时文件已经存在，先删除它
		if ( file_exists($file) ) {
			@unlink($file);
		}

		// 把上传的图片文件保存到预定位置
		if ( @copy($_FILES['Filedata']['tmp_name'], $file) || @move_uploaded_file($_FILES['Filedata']['tmp_name'], $file)) {
			@unlink($_FILES['Filedata']['tmp_name']);
			list($width, $height, $type, $attr) = getimagesize($file);
			if ( $width < 10 || $height < 10 || $width > 3000 || $height > 3000 || $type == 4 ) {
				@unlink($file);
				return -2; // Invalid photograph!
			}
		} else {
			@unlink($_FILES['Filedata']['tmp_name']);
			return -4; // Can not write to the data/tmp folder!
		}

		// 用于访问临时图片文件的 url
		$tmpUrl = $this->getBaseUrl()."uploads".DIRECTORY_SEPARATOR."head".DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$uid;
		return $tmpUrl;
	}

	private function flashdata_decode($s) {
		$r = '';
		$l = strlen($s);
		for($i=0; $i<$l; $i=$i+2) {
			$k1 = ord($s[$i]) - 48;
			$k1 -= $k1 > 9 ? 7 : 0;
			$k2 = ord($s[$i+1]) - 48;
			$k2 -= $k2 > 9 ? 7 : 0;
			$r .= chr($k1 << 4 | $k2);
		}
		return $r;
	}

	// 第二步：上传分割后的三个图片数据流
	private function rectAvatar( $uid )	{
		// 从 $_POST 中提取出三个图片数据流
	//	$bigavatar    = $this->flashdata_decode( $_POST['avatar1'] );
		$middleavatar = $this->flashdata_decode( $_POST['avatar2'] );
		$smallavatar  = $this->flashdata_decode( $_POST['avatar3'] );
		if ( !$middleavatar || !$smallavatar ) {
			return '<root><message type="error" value="-2" /></root>';
		}

		// 保存为图片文件
	//	$bigavatarfile    = $this->getBasePath() . "data" . DIRECTORY_SEPARATOR . "{$uid}_big.jpg";
		$savepath = "uploads".DIRECTORY_SEPARATOR."head".DIRECTORY_SEPARATOR.date('Ym').DIRECTORY_SEPARATOR.$uid;
		$middleavatarfile = $this->getBasePath()."uploads".DIRECTORY_SEPARATOR."head".DIRECTORY_SEPARATOR.date('Ym').DIRECTORY_SEPARATOR."{$uid}_middle.jpg";
		$smallavatarfile  = $this->getBasePath()."uploads".DIRECTORY_SEPARATOR."head".DIRECTORY_SEPARATOR.date('Ym').DIRECTORY_SEPARATOR."{$uid}_small.jpg";
		
		$dir = dirname( $middleavatarfile );
		if ( !file_exists( $dir ) ) {
			@mkdir( $dir, 0777, true );
		}
		
		$success = 1;
	/*	$fp = @fopen($bigavatarfile, 'wb');
		@fwrite($fp, $bigavatar);
		@fclose($fp);
	*/
		$fp = @fopen($middleavatarfile, 'wb');
		@fwrite($fp, $middleavatar);
		@fclose($fp);

		$fp = @fopen($smallavatarfile, 'wb');
		@fwrite($fp, $smallavatar);
		@fclose($fp);

		// 验证图片文件的正确性
	//	$biginfo    = @getimagesize($bigavatarfile);
		$middleinfo = @getimagesize($middleavatarfile);
		$smallinfo  = @getimagesize($smallavatarfile);
		if ( !$middleinfo || !$smallinfo || $middleinfo[2] == 4 || $smallinfo[2] == 4 ) {
		//	file_exists($bigavatarfile) && unlink($bigavatarfile);
			file_exists($middleavatarfile) && unlink($middleavatarfile);
			file_exists($smallavatarfile) && unlink($smallavatarfile);
			$success = 0;
		}

		//图片记录入库
		$this->load->model( 'User_model', 'user' );
		$this->user->save_face( $uid, $savepath );
		// 删除临时存储的图片
		$tmpPath = $this->getBasePath()."uploads".DIRECTORY_SEPARATOR."head".DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR."{$uid}";
		@unlink($tmpPath);

		return '<?xml version="1.0" ?><root><face success="' . $success . '"/></root>';
	}

	// 从客户端访问头像图片的 url
	public function getAvatarUrl( $uid, $size='middle' )	{
		return $this->getBaseUrl() . "data/{$uid}_{$size}.jpg";
	}

	// 处理 HTTP Request
	// 返回值：如果是可识别的 request，处理后返回 true；否则返回 false。
	public function processRequest()	{
		// 从 input 参数里拆解出自定义参数
		$arr = array();
		parse_str( $_GET['input'], $arr );
		$uid = intval($arr['uid']);

		if ( $_GET['a'] == 'uploadavatar') {

			// 第一步：上传原始图片文件
			echo $this->uploadAvatar( $uid );
			return true;

		} else if ( $_GET['a'] == 'rectavatar') {
		
			// 第二步：上传分割后的三个图片数据流
			echo $this->rectAvatar( $uid );
			return true;
		}

		return false;
	}

	// 编辑页面中包含 camera.swf 的 HTML 代码
	public function renderHtml( $uid )	{
		// 把需要回传的自定义参数都组装在 input 里
		$input = urlencode( "uid={$uid}" );

		$baseUrl = $this->getBaseUrl();
		$uc_api = urlencode( $this->getThisUrl() );
		$urlCameraFlash = "{$baseUrl}camera.swf?ucapi={$uc_api}&input={$input}";
		$urlCameraFlash = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="447" height="477" id="mycamera" align="middle">
				<param name="allowScriptAccess" value="always" />
				<param name="scale" value="exactfit" />
				<param name="wmode" value="transparent" />
				<param name="quality" value="high" />
				<param name="bgcolor" value="#ffffff" />
				<param name="movie" value="'.$urlCameraFlash.'" />
				<param name="menu" value="false" />
				<embed src="'.$urlCameraFlash.'" quality="high" bgcolor="#ffffff" width="447" height="477" name="mycamera" align="middle" allowScriptAccess="always" allowFullScreen="false" scale="exactfit"  wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
			</object>';
		return $urlCameraFlash;
	}
}