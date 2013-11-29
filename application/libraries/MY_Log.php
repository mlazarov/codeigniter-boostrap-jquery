<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// ------------------------------------------------------------------------

/**
 * Logging Class
 *
 * @package	CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author	mlazarov
 * @link	https://github.com/mlazarov/codeigniter-boostrap-jquery	
 */
class MY_Log extends CI_Log {

	protected $_log_path;
	protected $_threshold	= 1;
	protected $_date_fmt	= 'Y-m-d H:i:s';
	protected $_enabled	= TRUE;
	protected $_levels	= array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

	// --------------------------------------------------------------------

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	string	the error level
	 * @param	string	the error message
	 * @param	bool	whether the error is a native PHP error
	 * @return	bool
	 */
	public function write_log($level = 'error', $msg, $php_error = FALSE){
		if ($this->_enabled === FALSE) {
			return FALSE;
		}

		$level = strtoupper($level);

		if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold)) {
			return FALSE;
		}

		$callers=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		
		$filepath = $this->_log_path.'log-'.date('Y-m-d').'.php';
		$message  = $level.' '.(($level == 'INFO') ? ' -' : '-').' '.str_pad(getmypid(),5," ").' - '.date($this->_date_fmt). ' | '.
			$callers[2]['class'].$callers[2]['type'].$callers[2]['function'].'  --> '.$msg."\n";

		$this->write($filepath,$message);
		
		if($level == 'ERROR'){
			$filepath = $this->_log_path.'log-'.date('Y-m-d').'_errors.php';
			$this->write($filepath,$message);
		}
		return true;
	}
	private function write($filepath,$message){
		
		if ( ! file_exists($filepath)) {
			$message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}

		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
			return FALSE;
		}		

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, FILE_WRITE_MODE);
		
		return true;
		
	}
}
// END Log Class

/* End of file Log.php */
/* Location: ./libraries/Log.php */
