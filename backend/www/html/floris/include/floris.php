<?php
require_once 'db_handler.php';
require_once 'database.php';
require_once 'session.php';
require_once __DIR__ . '/../vendor/autoload.php';

class Floris
{
    private $app;
    private $session;

    public function __construct(){

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

    }

    /**
     * User Login
     * url - /login
     * method - POST
     * params - email, password
     */
    public function login () {
        // check for required params
        $this->verifyRequiredParams(array('email', 'password'));

        // reading post params
        $email = $this->app->request()->post('email');
        $password = $this->app->request()->post('password');
        $response = array();

        $db = new DbHandler();
        // check for correct email and password
        if ($db->checkLogin($email, $password)) {
            // get the user by email
            $user = $db->getUserByEmail($email);
            if ($user != NULL) {
                $response["error"] = false;
                $response['name'] = $user['name'];
                $response['email'] = $user['email'];
                $response['apiKey'] = $user['api_key'];
                $response['createdAt'] = $user['created_at'];
                $response['session_id'] = session_id();
                $response['session_name'] = session_name();
		$_SESSION['valid_user'] = $user['name'];

                $this->app->log->debug("User loged in:".$user['name']);
            } else {
                // unknown error occurred
                $response['error'] = true;
                $response['message'] = "An error occurred. Please try again";
            }
        } else {
            // user credentials are wrong
            $response['error'] = true;
            $response['message'] = 'Login failed. Incorrect credentials';
        }

        $this->echoRespnse(200, $response);
    }

    /**
     * Logout the current session
     * @param $app of Slim
     */
    public function logout() {
        $response = array();
        if(isset($_SESSION['valid_user'])) {
            session_destroy();
            $_SESSION = array();
            $response['error'] = false;
            $response['message'] = "You are now logged out!";
        }
        else {
            $response['error'] = true;
            $response['message'] = "You are not logged in!";
        }
        $this->echoRespnse(200, $response);
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
            $response = array();
            $app = \Slim\Slim::getInstance();
            $response["error"] = true;
            $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
            $this->echoRespnse(400, $response);
            $app->stop();
        }
    }

    /**
     * Echoing json response to client
     * @param String $status_code Http response code
     * @param Int $response Json response
     */
    public function echoRespnse($status_code, $response) {
        $app = \Slim\Slim::getInstance();
        // Http response code
        $app->status($status_code);

        // setting response content type to json
        $app->contentType('application/json');

        echo json_encode($response);
    }

}
