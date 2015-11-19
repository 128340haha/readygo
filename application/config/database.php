<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'root';
$db['default']['password'] = '';
$db['default']['database'] = 'myhome';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

$db['bbs']['hostname'] = 'localhost';
$db['bbs']['username'] = 'root';
$db['bbs']['password'] = '';
$db['bbs']['database'] = 'bbs';
$db['bbs']['dbdriver'] = 'mysql';
$db['bbs']['dbprefix'] = '';
$db['bbs']['pconnect'] = TRUE;
$db['bbs']['db_debug'] = TRUE;
$db['bbs']['cache_on'] = FALSE;
$db['bbs']['cachedir'] = '';
$db['bbs']['char_set'] = 'utf8';
$db['bbs']['dbcollat'] = 'utf8_general_ci';
$db['bbs']['swap_pre'] = '';
$db['bbs']['autoinit'] = TRUE;
$db['bbs']['stricton'] = FALSE;

$db['4002']['hostname'] = 'localhost';
$db['4002']['username'] = 'root';
$db['4002']['password'] = '';
$db['4002']['database'] = 'family4002';
$db['4002']['dbdriver'] = 'mysql';
$db['4002']['dbprefix'] = '';
$db['4002']['pconnect'] = TRUE;
$db['4002']['db_debug'] = TRUE;
$db['4002']['cache_on'] = FALSE;
$db['4002']['cachedir'] = '';
$db['4002']['char_set'] = 'utf8';
$db['4002']['dbcollat'] = 'utf8_general_ci';
$db['4002']['swap_pre'] = '';
$db['4002']['autoinit'] = TRUE;
$db['4002']['stricton'] = FALSE;

$db['4003']['hostname'] = 'localhost';
$db['4003']['username'] = 'root';
$db['4003']['password'] = '';
$db['4003']['database'] = 'family4003';
$db['4003']['dbdriver'] = 'mysql';
$db['4003']['dbprefix'] = '';
$db['4003']['pconnect'] = TRUE;
$db['4003']['db_debug'] = TRUE;
$db['4003']['cache_on'] = FALSE;
$db['4003']['cachedir'] = '';
$db['4003']['char_set'] = 'utf8';
$db['4003']['dbcollat'] = 'utf8_general_ci';
$db['4003']['swap_pre'] = '';
$db['4003']['autoinit'] = TRUE;
$db['4003']['stricton'] = FALSE;

$db['3001']['hostname'] = 'localhost';
$db['3001']['username'] = 'root';
$db['3001']['password'] = '';
$db['3001']['database'] = 'family3001';
$db['3001']['dbdriver'] = 'mysql';
$db['3001']['dbprefix'] = '';
$db['3001']['pconnect'] = TRUE;
$db['3001']['db_debug'] = TRUE;
$db['3001']['cache_on'] = FALSE;
$db['3001']['cachedir'] = '';
$db['3001']['char_set'] = 'utf8';
$db['3001']['dbcollat'] = 'utf8_general_ci';
$db['3001']['swap_pre'] = '';
$db['3001']['autoinit'] = TRUE;
$db['3001']['stricton'] = FALSE;

$db['3002']['hostname'] = 'localhost';
$db['3002']['username'] = 'root';
$db['3002']['password'] = '';
$db['3002']['database'] = 'family3002';
$db['3002']['dbdriver'] = 'mysql';
$db['3002']['dbprefix'] = '';
$db['3002']['pconnect'] = TRUE;
$db['3002']['db_debug'] = TRUE;
$db['3002']['cache_on'] = FALSE;
$db['3002']['cachedir'] = '';
$db['3002']['char_set'] = 'utf8';
$db['3002']['dbcollat'] = 'utf8_general_ci';
$db['3002']['swap_pre'] = '';
$db['3002']['autoinit'] = TRUE;
$db['3002']['stricton'] = FALSE;

$db['0001']['hostname'] = 'localhost';
$db['0001']['username'] = 'root';
$db['0001']['password'] = '';
$db['0001']['database'] = 'family0001';
$db['0001']['dbdriver'] = 'mysql';
$db['0001']['dbprefix'] = '';
$db['0001']['pconnect'] = TRUE;
$db['0001']['db_debug'] = TRUE;
$db['0001']['cache_on'] = FALSE;
$db['0001']['cachedir'] = '';
$db['0001']['char_set'] = 'utf8';
$db['0001']['dbcollat'] = 'utf8_general_ci';
$db['0001']['swap_pre'] = '';
$db['0001']['autoinit'] = TRUE;
$db['0001']['stricton'] = FALSE;

$db['0002']['hostname'] = 'localhost';
$db['0002']['username'] = 'root';
$db['0002']['password'] = '';
$db['0002']['database'] = 'family0002';
$db['0002']['dbdriver'] = 'mysql';
$db['0002']['dbprefix'] = '';
$db['0002']['pconnect'] = TRUE;
$db['0002']['db_debug'] = TRUE;
$db['0002']['cache_on'] = FALSE;
$db['0002']['cachedir'] = '';
$db['0002']['char_set'] = 'utf8';
$db['0002']['dbcollat'] = 'utf8_general_ci';
$db['0002']['swap_pre'] = '';
$db['0002']['autoinit'] = TRUE;
$db['0002']['stricton'] = FALSE;
/* End of file database.php */
/* Location: ./application/config/database.php */
