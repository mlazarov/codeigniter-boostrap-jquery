<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created: 07.07.2012
 * Updated: 04.11.2012
 * Created by: Martin Lazarov
 *
 * Changelog:
 * 2012.08.04 - Added json reponse on invalid session
 * 2012.08.23 - Added user autologin feature
 * 2012.10.07 - Extending user remember me cookie
 */
class My_Controller extends CI_Controller{
	var $require_auth = false;
	var $title = '';
	var $description = '';
	var $keywords = '';
	var $css = array();
	var $js = array();
	var $flat = false;
	var $user_id = 0;
	var $user = false;

	public function __construct(){
		parent::__construct();
		//$this->output->enable_profiler(TRUE);

		if(!$this->session->userdata('id')){
			$user_code = $this->input->cookie('remember');
			if($user_code){
				$this->db->where('user_code',$user_code);
				$this->db->where('user_ip',$_SERVER['REMOTE_ADDR']);
				$autologin = $this->db->get('user_autologins',1)->row();
				if($autologin->user_id){
					$this->db->where('id',$autologin->user_id);
					$this->user = $this->db->get('users')->row();
					if($this->user){
						$this->session->set_userdata((array)$this->user);

						// Extend cookie lifetime
						$cookie = array(
						    'name'   => 'remember',
						    'value'  => $user_code,
						    'expire' => 7*86500, // 7 days
						    'path'   => '/',
						    'secure' => false
						);
						$this->input->set_cookie($cookie);
					}
				}
			}
		}
		if($this->require_auth){
			if(!$this->session->userdata('id')){
				if($this->input->is_ajax_request()){
					$data = array('error'=>1,'errstr'=>'User session time out. <a href="/profile/login/">Relogin</a> to continue');
					Header('Content-type: application/json');
					echo json_encode($data);
		  			exit;
				}
				Redirect('profile/login');
			}
		}

		if($this->session->userdata('id')){
			$this->db->set('lastused',time());
			if(!$user->created_ip)$this->db->set('created_ip',$_SERVER['REMOTE_ADDR']);
			$this->db->where('id',$this->session->userdata('id'));
			$this->db->update('users');

			if(!$this->user){
				$user = $this->db->where('id',$this->session->userdata('id'))->get('users',1)->row();
				$this->user = (object)$user;
				$this->session->set_userdata((array)$user);
			}
		}

		$this->user_id = $this->session->userdata('id');

	}

	/**
	 * Flash message
	 */
	protected function flash($text,$url,$title=false,$time=3){

		if ( !preg_match('#^https?://#i', $url)) {
			$url = site_url($url);
		}
		if(!$title){
			$title = mb_substr(strip_tags($text),0,50);
		}

		$data=array(
			'title' => $title,
			'message' => $text,
			'url' => $url,
			'time' => $time
		);

		echo $this->load->view('layout/flash',$data,true);
		exit;
	}

	/**
	 * Simple page render
	 *
	 */
	protected function Render($view_file,$data=array()){
		$this->load->view('layout/header');
		$this->load->view($view_file,$data);
		$this->load->view('layout/footer');
	}

	/**
	 * Dump session vars & other important stuff
	 */
	public function Dump(){

		//if(ENVIRONMENT!='development') return;

		$this->load->helper('functions');

		echo "<h3>Session</h3>";
		array_to_table($this->session->all_userdata());

		echo "<h3>Cookie</h3>";
		array_to_table($_COOKIE);

		echo "<h3>User</h3>";
		array_to_table($this->user);
	}
}
?>
