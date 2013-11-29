<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
// ------------------------------------------------------------------------

/**
 * MemcacheDB Class
 *
 * @package	CodeIgniter
 * @subpackage	Libraries
 * @category	MemcacheDB
 * @author	Martin Lazarov
 * @link	https://github.com/mlazarov/codeigniter-boostrap-jquery
 * 
 * TODO: Add retry if anything fails
 */

class Memcachedb {
	private $config;  // Config variables
	private $master;  // Holds the master memcached object
	private $slave;  // Holds the slave memcached object
	private $slaves; // Aviable slave servers
	private $servers; // List of aviable servers
	
	public function __construct($config = array()) {
		
		$this->ci =& get_instance();
		
		$this->ci->load->config('memcache');

		if (count($config)) {
			$this->config = $config;
		} else {
			$cfg = $this->ci->config->item('memcachedb');
			$this->config = $cfg['default'];
		}
		foreach($this->config['servers'] as $server){
			if(strstr($server,':')){
				list($host,$port) = explode(":",$server);
			}else{
				$host = $server;
				$port = '11211'; // Set default port if missing
			}
			$this->servers[] = array("host"=>$host,'port'=>$port);
		}

		log_message('debug', "MemcacheDB Library Class Loaded");
	}
	/**
	 * Get memcache data
	 *
	 * @param string $key Memcache key
	 * @return string Result data or false if none found
	 */
	public function get($key) {
		log_message('debug','Memcachedb:: Reading from memcache data for key `'.$key.'`');
		# return data or false if not found
		return $this->readFromRandomActiveSlave($key);
	}

	/**
     * Replace or add data to memcache.
     * Uses master detection.
     *
     * @param string $key Memcache key
     * @param string $data Data to write
     * @param integer $duration Expire duration
     * @throws Exception When master not set
     * @return boolean True if writing was ok
     */
	public function save($key, $data, $duration=3600) {
		
		//TODO: first try to write to first server and if there is a problem try to find active master
		
		if(!$this->setActiveMaster()) return false;
		# replace memcache key
        $result = $this->master->replace($key, $data, $duration);
        # get result code from replace
        $replaceResultCode = $this->master->getResultCode();
        switch ($replaceResultCode) {
                case Memcached::RES_NOTSTORED :
                        log_message('debug','Memcachedb:: Key not found, adding instead');
                        $result = $this->master->add($key, $data, $duration);
						if($this->master->getResultCode() !== Memcached::RES_SUCCESS){
							log_message('error','Memcachedb:: error adding key `'.$key.'`');
							return false;
						}
                        # all ok, return
						return $result;
                case Memcached::RES_SUCCESS :
                        # break not needed, we're returning the result
                        return $result;
                default:
                        log_message('error','Memcachedb:: Result for key `'.$key.'` from connection to memcache host '.print_r($this->master->getServerList(),1).' was:'.$replaceResultCode);
                        return false;
        }
	}

	/**
	 * Delete key from memcache
	 *
	 * @param string $key Memcache key
	 * @throws Exception If master was unwritable even when it was detected first
	 * @return boolean Result of the deletion
	 */
	public function delete($key) {
		if(!$this->setActiveMaster()) return false;
		//TODO: Handle && log errors
		return $this->master->delete($key);
	}
	
	/**
	 * Find a random accessible slave for reading
	 *
	 * @throws Exception When no memcache is available for reading
	 * @return boolean When memcache was found
	 */
	private function readFromRandomActiveSlave($key) {
		
		
		if(!$this->setActiveSlave()) return false;
		
		# try to read
		$result = $this->slave->get($key);
		$resultCode = $this->slave->getResultCode();

		switch ($resultCode) {
			# if not found just log and fall to next case
			case Memcached::RES_NOTFOUND:
				log_message('error','Memcachedb:: No record found for key `'.$key.'`');
				return false;
			case Memcached::RES_SUCCESS:
				return $result;
			default:
				log_message('error','Memcachedb:: Result for '.$key.' from connection to memcache host '.$server['host'].':'.$server['port'].' was:'.$resultCode);
				return false;
		}
	}
		
	
	/**
	 * Find the active master memcache and set it as a master host
	 *
	 * @throws Exception On socket error or when no master was found
	 * @return boolean Returns true when master was found. We depend on exceptions to handle other cases
	 */
	private function setActiveMaster($force=false) {
		// Skip if we already have active master
		if($this->master && $force === false) return true;
		
		$this->slaves = $this->servers;
		
		# try all hosts in a row
		foreach ($this->servers as $server_key=>$server) {
			# attempt opening of socket
			$socket = @fsockopen($server['host'], $server['port'], $err, $errMessage, 1);
			
			# skip this host if no socket could be opened
			if ($socket === false) {
				log_message('error','Memcachedb:: Could not open socket to ' . $server['host'] . ':' . $server['port'] . '. Error ' . $err . ' - ' . $errMessage);
				continue;
			}
			# we have a working socket by this line
			
			log_message('debug','Memcachedb:: Socked opened to ' . $server['host'] . ':' . $server['port']);
			# set socket timeout, 1 second currently
			stream_set_timeout($socket, 0, 1000);
			# attempt write
			$result = fwrite($socket, "stats repms\r\n", strlen("stats repms\r\n"));
			if ($result === false){
				log_message('error','Memcachedb:: Could not write to socket:');
				continue;
			}
			# read response
			while ($line = fgets($socket, 1024)) {
				log_message('debug',"Line: ".trim($line));
				# break on error
				if (strpos($line, 'ERROR')) {
					log_message('error','Memcachedb:: Got error from ' . $server['host'] . ':' . $server['port'] . ' on memcachedb: stats repms. Continuing to next host');
					break;
				}
				# break on end
				if (strpos($line, 'END')) {
					log_message('error','Memcachedb:: No master found in stats rpms on ' . $server['host'] . ':' . $server['port']);
					break;
				}
				# if we have MASTER line
				if (strpos($line, '/MASTER/')) {
					preg_match('/(\d+\.){3}\d+/', $line, $res);
					if (preg_match('/(\d+\.){3}\d+/', $line, $res) === false) {
						log_message('error','Memcachedb:: Could not find IP in line:' . $line);
						continue;
					}
					
					foreach ($this->servers as $hostObject) {
						if ($hostObject['host'] == $res[0]) {
							# assign master configuration reference
							$this->master = $hostObject;
							log_message('debug','Memcachedb:: Assigned master:' . $hostObject['host']);
							# close socket and end execution
							fclose($socket);
							if($this->connectToMaster($hostObject)){
								unset($this->slaves[$server_key]);					
								return true;
							}
							break 3;
						}
					}
				}
			}
			log_message('debug','Memcachedb:: Closing socket to ' . $server['host'] . ':' . $server['port']);
			if(is_resource($socket))fclose($socket);
		}
		log_message('error','Memcachedb:: No memcache master found');
		return false;
	}

	/**
	 * Find the active memcache slave and set it as a slave host
	 *
	 * @throws Exception On socket error or when no master was found
	 * @return boolean Returns true when master was found. We depend on exceptions to handle other cases
	 */
	private function setActiveSlave($force=false){
		// Skip if we already have active slave
		if($this->slave && $force === false) return true;
		
		log_message('debug','Memcachedb:: Getting random memcache slave');
		if(!$this->slaves) $this->slaves = $this->servers;
		# scramble for random access order
		shuffle($this->slaves);
		# test for accessibility
		foreach ($this->slaves as $server) {
			if(!$this->connectToSlave($server)){
				continue;
			}
			return true;			
		}
		log_message('error','Memcachedb:: Cant connect to any slave host');
		return false;
	}

	private function connectToMaster($server){
		$this->master = new Memcached();
		$this->master->setOption(Memcached::OPT_COMPRESSION, false);
		$this->master->addServer($server['host'],$server['port']);
		$version = $this->master->getVersion();
		switch($this->master->getResultCode()){
			case Memcached::RES_SUCCESS:
				log_message('debug','Memcachedb:: Connected to Master Server ' . $server['host'].':'.$server['port'].' Version('.array_pop($version).')');
				return true;
				break;
			default:
				log_message('error','Memcachedb:: Cant connect to Master Server ' . $server['host'].':'.$server['port']);
				return false;
		}
	}
	private function connectToSlave($server){
		$this->slave = new Memcached();
		$this->slave->setOption(Memcached::OPT_COMPRESSION, false);
		$this->slave->addServer($server['host'],$server['port']);
		$version = $this->slave->getVersion();
		switch($this->slave->getResultCode()){
			case Memcached::RES_SUCCESS:
				log_message('debug','Memcachedb:: Connected to Slave Server ' . $server['host'].':'.$server['port'].' Version('.array_pop($version).')');
				return true;
				break;
			default:
				log_message('error','Memcachedb:: Cant connect to Slave Server ' . $server['host'].':'.$server['port']);
				return false;
		}
	}

}

// END MemcacheDB Class

/* End of file Memcache.php */
/* Location: ./libraries/Memcachedb.php */
