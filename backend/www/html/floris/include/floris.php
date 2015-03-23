<?php
require_once 'database.php';
require_once 'db_handler.php';
require_once 'session.php';
require_once __DIR__ . '/../vendor/autoload.php';

class Floris
{
    private $db;
    private $db_handler;
    private $app;
    private $session;

    public function __construct(){

        // Instantiate new Database object
        $this->db = new Database;
        $this->db_handler = new DBHandler;

        // create slim app
        $this->app = new \Slim\Slim();
        
        // enable debugging
        $this->app->config('debug', true);
        $this->app->log->setEnabled(true);
        $this->app->log->setLevel(\Slim\Log::DEBUG);

        // create session
        $this->session = new Session;

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

    }

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
     * User Login
     * url - /login
     * method - POST
     * params - email, password
     */
    public function login () {

        // check if user account locked
        if(isset($_SESSION[LOCKED]) && $_SESSION[LOCKED]) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_LOCKED, "User account locked");
        }
 
        // check for required params
        $this->verifyRequiredParams(array('email', 'password'));

        // reading post params
        $email = $this->app->request()->post('email');
        $password = $this->app->request()->post('password');

        // check if user already logged in
	if(isset($_SESSION[USER_ID]) && ($_SESSION[USER_ID] == $email)) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_ALREADY_LOGGED_IN, "User account already logged in");
        }

	// get user info	
        $user_info = $this->db_handler->getUserInfo($email, "password_hash, locked, reset_passwd");
        if( $user_info['error_code'] !== ERROR_CODE_SUCCESS ) {
            if( $user_info['error_code'] === ERROR_CODE_DB_NO_RECORD_FOUND ) {
                // user credentials are wrong
                $message = "Login failed. Incorrect credentials";
            } else if( $user_info['error_code'] !== ERROR_CODE_SUCCESS ) {
                // unknown error occurred
                $message = "An error occurred. Please try again";
            }
            $this->addLoginError($email);
            $this->echoResponse(200, $user_info['error_code'], $message);
        }

        // check if account locked
        if( $user_info['data']['locked'] ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_LOCKED, "User account locked");
        }

	// validate password
        if (!PassHash::check_password($user_info['data']['password_hash'], $password)) {
            $this->app->log->debug("Credential not match for user:$email");
            $this->addLoginError($email);
            $this->echoResponse(200, ERROR_CODE_LOGIN_WRONG_CREDENTIAL, "Incorrect login credential");
        }

        // check if account need to reset password
        if( $user_info['data']['reset_passwd'] ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_NEED_RESET_PASSWD, "User account need to reset password");
        }

        // log in success
	$_SESSION['user_id'] = $email;
        $this->echoResponse(200, ERROR_CODE_SUCCESS, "Successfully logged in", session_id());
    }

    /**
     * Logout the current session
     * @param $app of Slim
     */
    public function logout() {
        if(isset($_SESSION['user_id'])) {
            session_destroy();
            $_SESSION = array();
            $this->echoResponse(200, ERROR_CODE_SUCCESS, "User logged out");
        }
        else {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_NOT_LOGGED_IN, "User not logged in");
        }
    }

    /**
     * User addTLog
     * url - /addTLog
     * method - POST
     * params - email, password
     */
    public function addTLog () {
        if( !$this->isSessionStarted() ) {
            $this->echoResponse(200, ERROR_CODE_ACCOUNT_NOT_LOGGED_IN, "User not logged in");
        }

        // check for required params
        $this->verifyRequiredParams(array('device_id', 'user_id', 'sync_id', 'op_code', 'log'));

        // reading post params
        $device_id = $this->app->request()->post('device_id');
        $user_id = $this->app->request()->post('user_id');
        $sync_id = $this->app->request()->post('sync_id');
        $op_code = $this->app->request()->post('op_code');
        $log = $this->app->request()->post('log');

        // Set query
        $this->db->query('INSERT INTO transaction_log (`device_id`, `user_id`, `sync_id`, `op_code`, `log`) VALUES (:device_id, :user_id, :sync_id, :op_code, :log)');

        // Bind data
        $this->db->bind(':device_id', $device_id);
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':sync_id', $sync_id);
        $this->db->bind(':op_code', $op_code);
        $this->db->bind(':log', $log);

        // Attempt Execution
        // If successful
        if($this->db->execute()){
            $this->echoResponse(200, ERROR_CODE_SUCCESS, "TLog added!");
        } else {
            $this->echoResponse(200, ERROR_CODE_FAIL_ADDING_TLOG, "Failed adding to TLog!");
        }
    }

    /**
     * Check if the session started
     */
    public function isSessionStarted() {
        //return session_status() === PHP_SESSION_ACTIVE ? true : false;
        return isset($_SESSION['valid_user']) ? true : false;
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
    /*
    public function echoResponse($status_code, $response) {
        $app = \Slim\Slim::getInstance();
        // Http response code
        $app->status($status_code);

        // setting response content type to json
        $app->contentType('application/json');

        echo json_encode($response);
    }
    */

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
        $response['error_code'] = $error_code;
        $response['message'] = $message;
        if($session_id) {
            $response['session_id'] = $session_id;
        }
        echo json_encode($response);
        $this->app->stop();
    }

}
