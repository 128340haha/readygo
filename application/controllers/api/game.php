<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Game extends CI_Controller {
	
	public function __construct() { 
		parent::__construct(); 
		$this->load->model( 'Game_model', 'game' );
		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		if( $this->input->get_post('sessionid') ){
			session_id( $this->input->get_post('sessionid') );
		}
		session_start();
		is_login( $this, 'app' );
	}
	
	/**
	 * 我的游戏信息
	 */
	public function game_info(){
		$uid = $_SESSION['uid'];
		$where = array( 'user_id' => $uid );
		$list = $this->game->game_info( $where, 'list' );
		echo json_encode( array( 'res'=>1, 'list'=>$list ) );		
	}
	
	/**
	 * 我的游戏记录(暂时面对单一用)
	 */
	public function my_record(){
		$uid = $_SESSION['uid'];
		$start = $this->input->get('start');
		$end = $this->input->get('end');
		$where = array( 'user_id' => $uid, 'act_time >='=>$start, 'act_time <='=>$end );
		$list = $this->game->record_list( $where );
		echo json_encode( array( 'res'=>1, 'list'=>$list ) );
	}
	
	
	/**
	 * 操作记录
	 */
	public function take_record(){
		$uid = $_SESSION['uid'];
		
		$coin = $this->game->check_data( $this->input->post('coin'), 'coin' );
		
		$electricity = $this->game->check_data( $this->input->post('electricity'), 'electricity' );
		//获得与使用道具
		$get_stage = $this->input->post('get_stage');
		$use_stage = $this->input->post('use_stage');
		$param = array(
			'user_id'		=>	$uid,
			'coin'			=>	$coin,
			'electricity'	=>	$electricity,
			'get_stage'		=>	$get_stage, 
			'use_stage'		=>	$use_stage,
			'act_time'		=>	time()
		);
		
		//写入数据
		$res = $this->game->add_record( $param );
		if( $res == 1 ){
			echo return_back( array('res'=>1,'info'=>'') );
		}else{
			echo return_back( array('res'=>0,'info'=>$res) );
		}
	}
	
	
}