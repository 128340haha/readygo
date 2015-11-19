<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 分页函数
 * 使用该分页文件路径必须一致，否则无法使用(index不能省略)
 * 格式 :   控制器/文件/函数        
 * http://www.example.com/device/device/index/
 * 
 * @author Shaka
 *
 */
class Page_model extends CI_Model{
	private $per_page = 20;		//每页显示多少
	private $all_page = 1;		//一共多少页
	private $all_num = 0;		//一共多少条
	private $page_url = '';		//页面地址
	private $this_page = 1;
	private $n = 3;
	/**
	 * 构造函数
	 */
	public function __construct(){
		parent::__construct();
		$this->load->library('pagination');
	}
	
	
	public function make_page( $number, $per_page = false, $n = false, $page = '' ){
		if( $number <= 0 ){
			return 0;
		}
		$this->n = $n ? $n : $this->n;
		//获取url页数参数,url_no为程序URL修正
		if( empty( $page ) ){
			$page = $this->uri->segment( $this->config->item('url_no') + $this->n );
		}else{
			$page = $page ? $page : 1;
		}
		//当前页最小值
		if( empty( $page ) || $page < 0 || !is_numeric( $page ) ){
			$page = 1;
		}
		$this->all_num = $number;
		$this->per_page = $per_page ? $per_page : $this->per_page;	
		//一共多少页
		$this->all_page = ceil( $number / $this->per_page );
		if( $page > $this->all_page ){
			$page = $this->all_page;
		}
		//当前页
		$this->this_page = $page;
		return $per_page * ( $page - 1 );
	}
	
	/**
	 * ci分页
	 * @param string $url
	 * @param string $num_links
	 */
	public function page_bar( $url = false, $num_links = false, $from = 'admin', $param = '' ){	
		$default_url = base_url().$this->uri->segment(1).'/'.$this->uri->segment(2).'/'.$this->uri->segment(3).'/?';
		if( !empty( $param ) ){
			$default_url .= $param;
		}
		
		$config['total_rows'] = $this->all_num;
		$config['per_page'] = $this->per_page;
		$config['use_page_numbers'] = TRUE;			//显示每页记录数
		$config['page_query_string'] = TRUE;				
		$config['num_links'] = $num_links ? $num_links : 3;
		$config['uri_segment'] = $this->config->item('url_no') + $this->n;
		$config['page_query_string'] = TRUE;
		
		//分页page的key值
		$config['query_string_segment'] = 'page';		
		
		//页面相关
		$config['first_link'] = '首页';
		$config['prev_link'] = '上一页';
		$config['next_link'] = '下一页';
		$config['last_link'] = '末页';
		
		//加载css
		if( $from == 'admin' ){
			$config['anchor_class'] = 'class="number"';
			$config['cur_tag_open'] = '<a href="#" class="number current">';
			$config['cur_tag_close'] = '</a>';
		}elseif( $from == 'business' ){	
			//首页
			$config['first_tag_open'] = '<span class="first paginate_button">';
			$config['first_tag_close'] = '</span>';
			//上一页
			$config['prev_tag_open'] = '<span class="previous paginate_button">';
			$config['prev_tag_close'] = '</span>';
			//下一页
			$config['next_tag_open'] = '<span class="next paginate_button">';
			$config['next_tag_close'] = '</span>';
			//末页
			$config['last_tag_open'] = '<span class="last paginate_button">';
			$config['last_tag_close'] = '</span>';
			//当前页
			$config['cur_tag_open'] = '<span class="paginate_active Amodel1">';
			$config['cur_tag_close'] = '</span>';
			//数字页面
			$config['num_tag_open'] = '<span class="paginate_button">';
			$config['num_tag_close'] = '</span>';
			//加入css		
			$config['anchor_class'] = 'class="Amodel1"';
		}
		$config['base_url'] = $url ? $url : $default_url;
		$this->pagination->initialize($config);
		return $this->pagination->create_links();
		
	}
	
	/**
	 * 分页信息
	 * @return multitype:NULL
	 */
	public function page_info(){
		$res = array(
			'all_num'	=>	$this->all_num,
			'per_page'	=>	$this->per_page,
			'all_page'	=>	$this->all_page,
			'this_page'	=>	$this->this_page,
		);
		return $res;
	}
	
	
} 