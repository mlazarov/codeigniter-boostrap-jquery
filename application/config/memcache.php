<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| MEMCACHEDB CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your memcachedb.
|
| For complete instructions please consult the 'MemcacheDB Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	Example:
|			memcachedb1.app:11211
|	['debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['autoinit'] Whether or not to automatically initialize the database.
*/

$config['memcachedb']['default']['servers'] 	= array('172.16.0.1','172.16.0.2','172.16.0.3');
$config['memcachedb']['default']['expiration'] 	= 3600;
$config['memcachedb']['default']['compression']	= false;

/**
 * NOT implemented
$config['memcachedb']['default']['debug'] 	 	= true;
$config['memcachedb']['default']['cache_on'] 	= false;
$config['memcachedb']['default']['cachedir'] 	= '';
$config['memcachedb']['default']['autoinit']	= true;
*/

/* End of file database.php */
/* Location: ./application/config/memcachedb.php */
