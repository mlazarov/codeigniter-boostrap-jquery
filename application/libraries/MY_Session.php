<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// ------------------------------------------------------------------------

/**
 * Memcache Session Class
 *
 * @package	CodeIgniter
 * @subpackage	Libraries
 * @category	Sessions
 * @author	Martin Lazarov
 * @link	https://github.com/mlazarov/codeigniter-boostrap-jquery	
 */
class MY_Session extends CI_Session{

	var $sess_expiration			= 7200;
	var $sess_expire_on_close		= FALSE;
	var $sess_match_ip				= FALSE;
	var $sess_match_useragent		= TRUE;
	var $sess_cookie_name			= 'ci_session';
	var $cookie_prefix				= '';
	var $cookie_path				= '';
	var $cookie_domain				= '';
	var $cookie_secure				= FALSE;
	var $sess_time_to_update		= 300;
	var $flashdata_key				= 'flash';
	var $time_reference				= 'time';
	var $gc_probability				= 5;
	var $userdata					= array();
	var $CI;
	var $now;
	var $session_id					= '';

	/**
	 * Session Constructor
	 *
	 * The constructor runs the session routines automatically
	 * whenever the class is instantiated.
	 */
	public function __construct($params = array()){
		log_message('debug', "Session Class Initialized");

		// Set the super object to a local variable for use throughout the class
		$this->CI =& get_instance();

		// Set all the session preferences, which can either be set
		// manually via the $params array above or via the config file
		foreach (array('sess_expiration', 'sess_expire_on_close', 'sess_match_ip', 'sess_match_useragent', 'sess_cookie_name', 
		'cookie_path', 'cookie_domain', 'cookie_secure', 'sess_time_to_update', 'time_reference', 'cookie_prefix') as $key){
			$this->$key = (isset($params[$key])) ? $params[$key] : $this->CI->config->item($key);
		}

		// Load the string helper so we can use the strip_slashes() function
		$this->CI->load->helper('string');

		// Load memcachedb library
		$this->CI->load->library('memcachedb');
		
		// Set the "now" time.  Can either be GMT or server time, based on the
		// config prefs.  We use this to set the "last activity" time
		$this->now = $this->_get_time();

		// Set the session length. If the session expiration is
		// set to zero we'll set the expiration two years from now.
		if ($this->sess_expiration == 0){
			$this->sess_expiration = (60*60*24*365*2);
		}

		// Set the cookie name
		$this->sess_cookie_name = $this->cookie_prefix.$this->sess_cookie_name;

		// Run the Session routine. If a session doesn't exist we'll
		// create a new one.  If it does, we'll update it.
		if ( ! $this->sess_read()){
			$this->sess_create();
		}
		else{
			$this->sess_update();
		}

		// Delete 'old' flashdata (from last request)
		$this->_flashdata_sweep();

		// Mark all new flashdata as old (data will be deleted before next request)
		$this->_flashdata_mark();

		log_message('debug', "MemcacheDB Session routines successfully run");
	}

	// --------------------------------------------------------------------

	/**
	 * Fetch the current session data if it exists
	 *
	 * @access	public
	 * @return	bool
	 */
	function sess_read() {
		// Fetch the cookie
		$this->session_id = $this->CI->input->cookie($this->sess_cookie_name);

		// No cookie?  Goodbye cruel world!...
		if ($this->session_id === FALSE)	{
			log_message('debug', 'Session cookie was NOT FOUND.');
			return FALSE;
		}
		
		log_message('debug', 'Session cookie found: '.$this->session_id);

		$json = $this->CI->memcachedb->get('memc.sess.'.$this->session_id);
		$session = @json_decode($json,1);

		// Is the session data we unserialized an array with the correct format?
		if ( ! is_array($session) OR ! isset($session['ip_address']) OR ! isset($session['user_agent']) OR ! isset($session['updated'])){
			log_message('debug', 'Missing keys in session. Destroying');
			$this->sess_destroy();
			return FALSE;
		}

		// Is the session current?
		if (($session['updated'] + $this->sess_expiration) < $this->now) {
			log_message('debug', 'Session expired. Destroying');
			$this->sess_destroy();
			return FALSE;
		}

		// Does the IP Match?
		if ($this->sess_match_ip == TRUE AND $session['ip_address'] != $this->CI->input->ip_address()) {
			log_message('error', 'Session IP diffrent from current. Destroying');
			$this->sess_destroy();
			return FALSE;
		}

		// Does the User Agent Match?
		if ($this->sess_match_useragent == TRUE AND trim($session['user_agent']) != trim(substr($this->CI->input->user_agent(), 0, 120))) {
			log_message('error', 'Session user agent missmatch. Destroying');
			$this->sess_destroy();
			return FALSE;
		}

		// Session is valid!
		$this->userdata = $session;
		unset($session);

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Write the session data
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_write(){
		// Save the data to the MemcacheDB
		$json = json_encode($this->userdata);
		$this->CI->memcachedb->save('memc.sess.'.$this->userdata['session_id'],$json,$this->sess_expiration);
	}

	// --------------------------------------------------------------------

	/**
	 * Create a new session
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_create() {
		// Generate random sess id
		$sessid = '';
		while (strlen($sessid) < 32) {
			$sessid .= mt_rand(0, mt_getrandmax());
		}

		// To make the session ID even more secure we'll combine it with the user's IP
		$sessid .= $this->CI->input->ip_address();
		$this->session_id = md5(uniqid($sessid, TRUE));
		
		$this->userdata = array(
							'session_id'	=> $this->session_id,
							'ip_address'	=> $this->CI->input->ip_address(),
							'user_agent'	=> substr($this->CI->input->user_agent(), 0, 120),
							'created'		=> $this->now,
							'updated'	=> $this->now,
							'user_data'		=> ''
							);

		// Save the data to the MemcacheDB
		//$this->sess_write();

		// Write the cookie
		$this->_set_cookie();
	}

	// --------------------------------------------------------------------

	/**
	 * Update an existing session to new SessID
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_update() {
		$this->userdata['updated'] = $this->now;
		//$this->sess_write(); // Skiped - updated only on __destruct()
		$this->_set_cookie();
		
		return; 
		
		// TODO: it isn't safe to proceed this now - check nodejs servers
		
		// We only update the session every five minutes by default
		if (($this->userdata['updated'] + $this->sess_time_to_update) >= $this->now) {
			return;
		}

		// Save the old session id so we know which record to
		// update in the database if we need it
		$old_sessid = $this->userdata['session_id'];
		$new_sessid = '';
		while (strlen($new_sessid) < 32) {
			$new_sessid .= mt_rand(0, mt_getrandmax());
		}

		// To make the session ID even more secure we'll combine it with the user's IP
		$new_sessid .= $this->CI->input->ip_address();

		// Turn it into a hash
		$this->session_id = md5(uniqid($new_sessid, TRUE));

		// Update the session data in the session data array
		$this->userdata['session_id'] = $this->session_id;
		$this->userdata['updated'] = $this->now;

		// _set_cookie() will handle this for us if we aren't using database sessions
		// by pushing all userdata to the cookie.
		$cookie_data = NULL;

		// Update the session ID and updated field in the DB if needed
		$this->sess_write();

		// Write the cookie
		$this->_set_cookie();
	}

	// --------------------------------------------------------------------

	/**
	 * Destroy the current session
	 *
	 * @access	public
	 * @return	void
	 */
	function sess_destroy() {
		// Kill the session memcache item
		$this->CI->memcachedb->delete($this->session_id);
		
		// Kill the cookie
		setcookie(
					$this->sess_cookie_name,
					'',
					($this->now - 31500000),
					$this->cookie_path,
					$this->cookie_domain,
					0
				);

		// Kill session data
		$this->userdata = array();
	}


	function set_userdata($newdata = array(), $newval = '')	{
		if (is_string($newdata))		{
			$newdata = array($newdata => $newval);
		}

		if (count($newdata) > 0) {
			foreach ($newdata as $key => $val) {
				$this->userdata[$key] = $val;
			}
		}
	}
	// --------------------------------------------------------------------
	
	/**
	 * Write the session cookie
	 *
	 * @access	public
	 * @return	void
	 */
	function _set_cookie(){

		$expire = ($this->sess_expire_on_close === TRUE) ? 0 : $this->sess_expiration + time();

		// Set the cookie
		setcookie(
					$this->sess_cookie_name,
					$this->session_id,
					$expire,
					$this->cookie_path,
					$this->cookie_domain,
					$this->cookie_secure
				);
	}

	// --------------------------------------------------------------------

	/**
	 * Garbage collection
	 *
	 * This deletes expired session rows from database
	 * if the probability percentage is met
	 *
	 * @access	public
	 * @return	void
	 */
	function _sess_gc()	{
		// MemcacheDb ?will? auto delete expired keys
		return;
	}
	function __destruct(){
		$this->sess_write();
	}

}
// END Session Class

/* End of file Session.php */
/* Location: ./libraries/Session.php */
