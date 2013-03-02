<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {

	public function Index(){
		$this->load->view('home');
	}
}
