<?php
require_once 'db_handler.php';
require_once 'session.php';
require_once __DIR__ . '/../vendor/autoload.php';

define('FLORIS_DATA_DIR',                               '/usr/local/floris/');
define('FLORIS_PHOTO_DIR',                              FLORIS_DATA_DIR . '/photos/');
define('FLORIS_DB_DIR',                                 FLORIS_DATA_DIR . '/db_files/');

class Floris
{
    private $db_handler;
    private $app;
    private $session;

    public function __construct(){

        // Instantiate new database handler
        $this->db_handler = new DBHandler;

        // create slim app
        $this->app = new \Slim\Slim();
        
        // enable debugging
        $this->app->config('debug', true);
        $this->app->log->setEnabled(true);
        $this->app->log->setLevel(\Slim\Log::DEBUG);

        // create session
        $this->session = new Session;

        if($this->session->checkSessionTimeout()) {
            $this->echoResponse(200, ERROR_CODE_SESSION_TIMEOUT, "Session timeout");
        }

        $this->app->log->debug("Init Floris");
    }

    /**
     * Register REST
     * @param
     */
    public function registerREST() {
        $self = $this;

        // login
        $this->app->post('/login', function() use($self) {
            $self->login();
        });

        // logout
        $this->app->post('/logout', function() use($self) {
            $self->logout();
        });

        // add transaction log
        $this->app->post('/addTLog', function() use($self) {
            $self->addTLog();
        });

        // register new user
        $this->app->post('/register', function() use($self) {
            $self->register();
        });

        // upload photo
        $this->app->post('/uploadPhoto', function() use($self) {
            $self->uploadPhoto();
        });

    }

    ///////////////////////////////////////////////////////////////////////////
    //
    // REST Methods
    //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * User Login
     * url - /login
     * method - POST
     * params - user_id, password, device_id, device_name
     */
    public function login () {

        // check if user account locked
        if(isset($_SESSION[LOCKED]) && $_SESSION[LOCKED]) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_LOCKED, "User account locked");
        }
 
        // check for required params
        $this->verifyRequiredParams(array(DB_FIELD_USER_ID, DB_FIELD_APP_ID, DB_FIELD_PASSWORD, DB_FIELD_DEVICE_ID, DB_FIELD_DEVICE_NAME));

        // reading post params
        $user_id = $this->app->request()->post(DB_FIELD_USER_ID);
        $app_id = $this->app->request()->post(DB_FIELD_APP_ID);
        $password = $this->app->request()->post(DB_FIELD_PASSWORD);

        // check if user already logged in
        if(isset($_SESSION[USER_ID]) && ($_SESSION[USER_ID] == $user_id) &&
           isset($_SESSION[DB_FIELD_APP_ID]) && ($_SESSION[DB_FIELD_APP_ID] == $app_id) ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_ALREADY_LOGGED_IN, "User account already logged in");
        }

        // get user info
        $user_info = $this->db_handler->getUserInfo($user_id, $app_id, "password_hash, locked, reset_passwd");
        if( $user_info[RESPONSE_FIELD_ERROR_CODE] !== ERROR_CODE_SUCCESS ) {
            if( $user_info[RESPONSE_FIELD_ERROR_CODE] === ERROR_CODE_DB_NO_RECORD_FOUND ) {
                // user credentials are wrong
                $message = "Login failed. Incorrect credentials";
            } else if( $user_info[RESPONSE_FIELD_ERROR_CODE] !== ERROR_CODE_SUCCESS ) {
                // unknown error occurred
                $message = "An error occurred. Please try again";
            }
            $this->addLoginError($user_id);
            $this->echoResponse(200, $user_info[RESPONSE_FIELD_ERROR_CODE], $message);
        }

        // check if account locked
        if( $user_info['data'][DB_FIELD_LOCKED] ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_LOCKED, "User account locked");
        }

        // validate password
        if (!PassHash::check_password($user_info['data'][DB_FIELD_PASSWD_HASH], $password)) {
            $this->app->log->debug("Credential not match for user:$user_id");
            $this->addLoginError($user_id);
            $this->echoResponse(200, ERROR_CODE_LOGIN_WRONG_CREDENTIAL, "Incorrect login credential");
        }

        // check if account need to reset password
        if( $user_info['data'][DB_FIELD_RESET_PASSWD] ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_NEED_RESET_PASSWD, "User account need to reset password");
        }

        // reading post params
        $device_id = $this->app->request()->post(DB_FIELD_DEVICE_ID);
        $device_name = $this->app->request()->post(DB_FIELD_DEVICE_NAME);

        // upsert user device
        $this->db_handler->upsertUserDevice($user_id, $device_id, $device_name);

        // log in success
        $_SESSION[USER_ID]            = $user_id;
        $_SESSION[DB_FIELD_APP_ID]    = $app_id;
        $_SESSION[DB_FIELD_DEVICE_ID] = $device_id;
        $this->echoResponse(200, ERROR_CODE_SUCCESS, "Successfully logged in", session_id());
    }

    /**
     * Logout the current session
     * @param $app of Slim
     */
    public function logout() {
        if(isset($_SESSION[USER_ID])) {
            session_destroy();
            $_SESSION = array();
            $this->echoResponse(200, ERROR_CODE_SUCCESS, "User logged out");
        } else {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_NOT_LOGGED_IN, "User not logged in");
        }
    }

    /**
     * User addTLog
     * url - /addTLog
     * method - POST
     * params - device_id, sync_id, op_code, log
     */
    public function addTLog () {
        if( !$this->isSessionStarted() ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_NOT_LOGGED_IN, "User not logged in");
        }

        // check for required params
        $this->verifyRequiredParams(array(DB_FIELD_DEVICE_ID, DB_FIELD_SYNC_ID, DB_FIELD_OP_CODE, DB_FIELD_LOG));

        // reading post params
        $tlog_info = array();
        $tlog_info[DB_FIELD_DEVICE_ID]     = $this->app->request()->post(DB_FIELD_DEVICE_ID);
        $tlog_info[DB_FIELD_USER_ID]       = $this->session->getUserID();
        $tlog_info[DB_FIELD_SYNC_ID]       = $this->app->request()->post(DB_FIELD_SYNC_ID);
        $tlog_info[DB_FIELD_OP_CODE]       = $this->app->request()->post(DB_FIELD_OP_CODE);
        $tlog_info[DB_FIELD_LOG]           = $this->app->request()->post(DB_FIELD_LOG);
        $result = $this->db_handler->addTLog($tlog_info);

        if($result == ERROR_CODE_SUCCESS){
            $this->echoResponse(200, $result, "TLog added!");
        } else {
            $this->echoResponse(200, $result, "Failed adding to TLog!");
        }
    }

    /**
     * User Registration
     * url - /register
     * method - POST
     * params - name, email, password
     */
    public function register () {
        // check for required params
        $this->verifyRequiredParams(array('name', DB_FIELD_USER_ID, 'password', 'account_type', 'device_id', 'device_name', 'app_id' ));

        // reading post params
        $name = $this->app->request->post('name');
        $user_id = $this->app->request->post(DB_FIELD_USER_ID);
        $password = $this->app->request->post('password');
        $account_type = $this->app->request->post('account_type');
        $device_id = $this->app->request->post('device_id');
        $device_name = $this->app->request->post('device_name');
        $app_id = $this->app->request->post('app_id');

        // validating email address
        $this->validateEmail($user_id);

        $res = $this->db_handler->createUser($name, $user_id, $password, $account_type, $device_id, $device_name, $app_id);
        if ($res == ERROR_CODE_SUCCESS) {
            $message = "You are successfully registered";
        } else if ($res == ERROR_CODE_USER_CREATE_FAILED) {
            $message = "Oops! An error occurred while registereing";
        } else if ($res == ERROR_CODE_USER_ALREADY_EXISTED) {
            $message = "Sorry, this email already existed";
        }
        // echo json response
        $this->echoResponse(201, $res, $message);
    }

    /**
     * User Upload Photo
     * url - /uploadPhoto
     * method - POST
     * params - name
     */
    public function uploadPhoto () {
        // check for required params
        $this->verifyRequiredParams(array('name'));

        // reading post params
        $photo_name = $this->app->request->post('name');
        $user_id = $_SESSION[USER_ID];
        $app_id = $_SESSION[DB_FIELD_APP_ID];
        $device_id = $_SESSION[DB_FIELD_DEVICE_ID];

        // file properties
        if (!(isset($_FILES['photo']) && $_FILES['photo']['size'] > 0)) {
            $this->echoResponse(200, ERROR_CODE_INVALID_FILE, "Invalid photo file!");
        }

        $tmp_name = $_FILES['photo']['tmp_name'];
        $file_name = FLORIS_PHOTO_DIR . basename($tmp_name);
        if( !copy($tmp_name, $file_name) ) {
            $this->echoResponse(200, ERROR_CODE_ERROR_SAVE_FILE, "Error saving file!");
        }

        // insert photo filename into DB
        $ret = $this->db_handler->addPhoto($user_id, $app_id, $device_id, $photo_name, $file_name);
        if ($ret == ERROR_CODE_SUCCESS) {
            $message = "Photo successfully uploaded";
        } else if ($res == ERROR_CODE_ERROR_INSERT_FILE) {
            $message = "Oops! An error occurred while uploading photo";
        } else if ($res == ERROR_CODE_INVALID_USER) {
            $message = "Sorry, this user acccount is invalid";
        }
        // echo json response
        $this->echoResponse(201, $res, $message);
    }

    ///////////////////////////////////////////////////////////////////////////
    //
    // Helper Methods
    //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * ValidateSession
     */
    public function validateSession(){
        // check if user account locked
        $response = array();
        if(isset($_SESSION[LOCKED]) && $_SESSION[LOCKED]) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_LOCKED, "User account locked");
        }
        return ERROR_CODE_SUCCESS;
    }

    /**
     * lockUser
     */
    public function lockUser($user_id) {
        $this->db_handler->lockUser($user_id);
        $this->echoResponse(200, ERROR_CODE_ACCOUNT_LOCKED, "User account locked");
    }

    /**
     * addLoginError
     */
    public function addLoginError($user_id) {
        if(isset($_SESSION[LOGIN_ERROR_COUNT])) {
            $_SESSION[LOGIN_ERROR_COUNT] += 1;
            if( $_SESSION[LOGIN_ERROR_COUNT] > 3 ) {
                // lock user
                $this->lockUser($user_id);
            }
        }
        else {
            $_SESSION[LOGIN_ERROR_COUNT] = 1;
        }
    }

    /**
     * Check if the session started
     */
    public function isSessionStarted() {
        //return session_status() === PHP_SESSION_ACTIVE ? true : false;
        return isset($_SESSION[USER_ID]) ? true : false;
    }

    /**
     * Verifying required params posted or not
     */
    public function verifyRequiredParams($required_fields) {
        $error = false;
        $error_fields = "";
        $request_params = array();
        $request_params = $_REQUEST;
        // Handling PUT request params
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            $this->app = \Slim\Slim::getInstance();
            parse_str($this->app->request()->getBody(), $request_params);
        }
        foreach ($required_fields as $field) {
            if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
                $error = true;
                $error_fields .= $field . ', ';
            }
        }

        if ($error) {
            // Required field(s) are missing or empty
            // echo error json and stop the app
            $this->echoResponse(400, ERROR_CODE_INVALID_REST_PARAMS, 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty');
        }
    }

    /**
     * Echoing json response to client
     * @param String $status_code Http response code
     * @param Int $response Json response
     */
    public function echoResponse($status_code, $error_code, $message, $session_id=null) {
        // Http response code
        $this->app->status($status_code);

        // setting response content type to json
        $this->app->contentType('application/json');

        $response = array();
        $response[RESPONSE_FIELD_ERROR_CODE] = $error_code;
        $response[RESPONSE_FIELD_MESSAGE] = $message;
        if($session_id) {
            $response[RESPONSE_FIELD_SESSION_ID] = $session_id;
        }
        echo json_encode($response);
        $this->app->stop();
    }

    /**
     * Validating email address
     */
    public function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echoResponse(400, ERROR_CODE_INVALID_EMAIL, 'Email address is not valid');
        }
    }

}
