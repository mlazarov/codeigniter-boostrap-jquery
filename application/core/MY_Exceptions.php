<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// ------------------------------------------------------------------------

/**
 * Exceptions Class
 *
 * @package	CodeIgniter
 * @subpackage	Libraries
 * @category	Exceptions
 * @author	mlazarov
 * @link	https://github.com/mlazarov/codeigniter-boostrap-jquery
 */
class MY_Exceptions extends CI_Exceptions {

	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	private
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @param 	int		the status code
	 * @return	string
	 */
	function show_error($heading, $message, $template = 'error_general', $status_code = 500) {
		set_status_header($status_code);
		//var_dump($_SERVER);exit;
		if((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') || (isset($_GET['format']) && $_GET['format'] =='json')){
			Header('Content-Type: application/json');
			return json_encode(array('message'=>$message,'heading'=>strip_tags($heading),'status_code'=>$status_code));
		}
		
		$message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		ob_start();
		include(APPPATH.'errors/'.$template.'.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line){
		$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

		$filepath = str_replace("\\", "/", $filepath);

		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, '/')){
			$x = explode('/', $filepath);
			$filepath = $x[count($x)-2].'/'.end($x);
		}

		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		ob_start();
		include(APPPATH.'errors/error_php.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}


}
// END Exceptions Class

/* End of file Exceptions.php */
/* Location: ./system/core/Exceptions.php */
