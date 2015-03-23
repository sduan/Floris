<?php
 
/*
 * Error Code
 */

define('ERROR_CODE_SUCCESS',                                0);
define('ERROR_CODE_USER_CREATED_SUCCESSFULLY',              1);
define('ERROR_CODE_USER_CREATE_FAILED',                     2);
define('ERROR_CODE_USER_ALREADY_EXISTED',                   3);

define('ERROR_CODE_DB_QUERY_FAILED',                        4);
define('ERROR_CODE_DB_NO_RECORD_FOUND',                     5);

define('ERROR_CODE_LOGIN_FAILED',                           6);
define('ERROR_CODE_LOGIN_WRONG_CREDENTIAL',                 7);

define('ERROR_CODE_ACCOUNT_LOCKED',                         8);
define('ERROR_CODE_ACCOUNT_NEED_RESET_PASSWD',              9);
define('ERROR_CODE_ACCOUNT_ALREADY_LOGGED_IN',              10);
define('ERROR_CODE_ACCOUNT_NOT_LOGGED_IN',      	    11);

define('ERROR_CODE_FAIL_ADDING_TLOG', 		     	    12);
define('ERROR_CODE_INVALID_REST_PARAMS', 		    13);

?>
