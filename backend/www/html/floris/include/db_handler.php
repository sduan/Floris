<?php
 
// /**
//  * Class to handle all db operations
//  * This class will have CRUD methods for database tables
//  *
//  */
// class DbHandler {
 
//     private $conn;
 
//     function __construct() {
//         require_once 'db_connect.php';
//         //require_once dirname(__FILE__) . './db_connect.php';
//         // opening db connection
//         $db = new DbConnect();
//         $this->conn = $db->connect();
//     }
 
//     /* ------------- `users` table method ------------------ */
 
//     /**
//      * Creating new user
//      * @param String $name User full name
//      * @param String $email User login email id
//      * @param String $password User login password
//      */
//     public function createUser($name, $email, $password) {
//         require_once 'pass_hash.php';
//         $response = array();

// 	// First check if user already existed in db
//         if (!$this->isUserExists($email)) {
//             // Generating password hash
//             $password_hash = PassHash::hash($password);
 
//             // Generating API key
//             $api_key = $this->generateApiKey();
 
//             // insert query
//             $stmt = $this->conn->prepare("INSERT INTO users(name, email, password_hash, api_key, status) values(?, ?, ?, ?, 1)");
//             $stmt->bind_param("ssss", $name, $email, $password_hash, $api_key);
 
//             $result = $stmt->execute();
 
//             $stmt->close();
 
//             // Check for successful insertion
//             if ($result) {
//                 // User successfully inserted
//                 return USER_CREATED_SUCCESSFULLY;
//             } else {
//                 // Failed to create user
//                 return USER_CREATE_FAILED;
//             }
//         } else {
//             // User with same email already existed in the db
//             return USER_ALREADY_EXISTED;
//         }
 
//         return $response;
//     }
 
//     /**
//      * Checking user login
//      * @param String $email User login email id
//      * @param String $password User login password
//      * @return boolean User login status success/fail
//      */
//     public function checkLogin($email, $password) {
//         // fetching user by email
//         $stmt = $this->conn->prepare("SELECT password_hash FROM users WHERE email = ?");
 
//         $stmt->bind_param("s", $email);
 
//         $stmt->execute();
 
//         $stmt->bind_result($password_hash);
 
//         $stmt->store_result();
 
//         if ($stmt->num_rows > 0) {
//             // Found user with the email
//             // Now verify the password
 
//             $stmt->fetch();
 
//             $stmt->close();
 
//             if (PassHash::check_password($password_hash, $password)) {
//                 // User password is correct
//                 return TRUE;
//             } else {
//                 // user password is incorrect
//                 return FALSE;
//             }
//         } else {
//             $stmt->close();
 
//             // user not existed with the email
//             return FALSE;
//         }
// 	return TRUE;
//     }
 
//     /**
//      * Checking for duplicate user by email address
//      * @param String $email email to check in db
//      * @return boolean
//      */
//     private function isUserExists($email) {
//         $stmt = $this->conn->prepare("SELECT id from users WHERE email = ?");
//         $stmt->bind_param("s", $email);
//         $stmt->execute();
//         $stmt->store_result();
//         $num_rows = $stmt->num_rows;
//         $stmt->close();
//         return $num_rows > 0;
//     }
 
//     /**
//      * Fetching user by email
//      * @param String $email User email id
//      */
//     public function getUserByEmail($email) {
//         $stmt = $this->conn->prepare("SELECT name, email, api_key, status, created_at FROM users WHERE email = ?");
//         $stmt->bind_param("s", $email);
//         if ($stmt->execute()) {
//             $user = $stmt->get_result()->fetch_assoc();
//             $stmt->close();
//             return $user;
//         } else {
//             return NULL;
//         }
//     }
 
//     /**
//      * Fetching user api key
//      * @param String $user_id user id primary key in user table
//      */
//     public function getApiKeyById($user_id) {
//         $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
//         $stmt->bind_param("i", $user_id);
//         if ($stmt->execute()) {
//             $api_key = $stmt->get_result()->fetch_assoc();
//             $stmt->close();
//             return $api_key;
//         } else {
//             return NULL;
//         }
//     }
 
//     /**
//      * Fetching user id by api key
//      * @param String $api_key user api key
//      */
//     public function getUserId($api_key) {
//         $stmt = $this->conn->prepare("SELECT id FROM users WHERE api_key = ?");
//         $stmt->bind_param("s", $api_key);
//         if ($stmt->execute()) {
//             $user_id = $stmt->get_result()->fetch_assoc();
//             $stmt->close();
//             return $user_id;
//         } else {
//             return NULL;
//         }
//     }
 
//     /**
//      * Validating user api key
//      * If the api key is there in db, it is a valid key
//      * @param String $api_key user api key
//      * @return boolean
//      */
//     public function isValidApiKey($api_key) {
//         $stmt = $this->conn->prepare("SELECT id from users WHERE api_key = ?");
//         $stmt->bind_param("s", $api_key);
//         $stmt->execute();
//         $stmt->store_result();
//         $num_rows = $stmt->num_rows;
//         $stmt->close();
//         return $num_rows > 0;
//     }
 
//     /**
//      * Generating random Unique MD5 String for user Api key
//      */
//     private function generateApiKey() {
//         return md5(uniqid(rand(), true));
//     }
 
//     /* ------------- `tasks` table method ------------------ */
 
//     /**
//      * Creating new task
//      * @param String $user_id user id to whom task belongs to
//      * @param String $task task text
//      */
//     public function createTask($user_id, $task) {        
//         $stmt = $this->conn->prepare("INSERT INTO tasks(task) VALUES(?)");
//         $stmt->bind_param("s", $task);
//         $result = $stmt->execute();
//         $stmt->close();
 
//         if ($result) {
//             // task row created
//             // now assign the task to user
//             $new_task_id = $this->conn->insert_id;
//             $res = $this->createUserTask($user_id, $new_task_id);
//             if ($res) {
//                 // task created successfully
//                 return $new_task_id;
//             } else {
//                 // task failed to create
//                 return NULL;
//             }
//         } else {
//             // task failed to create
//             return NULL;
//         }
//     }
 
//     /**
//      * Fetching single task
//      * @param String $task_id id of the task
//      */
//     public function getTask($task_id, $user_id) {
//         $stmt = $this->conn->prepare("SELECT t.id, t.task, t.status, t.created_at from tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
//         $stmt->bind_param("ii", $task_id, $user_id);
//         if ($stmt->execute()) {
//             $task = $stmt->get_result()->fetch_assoc();
//             $stmt->close();
//             return $task;
//         } else {
//             return NULL;
//         }
//     }
 
//     /**
//      * Fetching all user tasks
//      * @param String $user_id id of the user
//      */
//     public function getAllUserTasks($user_id) {
//         $stmt = $this->conn->prepare("SELECT t.* FROM tasks t, user_tasks ut WHERE t.id = ut.task_id AND ut.user_id = ?");
//         $stmt->bind_param("i", $user_id);
//         $stmt->execute();
//         $tasks = $stmt->get_result();
//         $stmt->close();
//         return $tasks;
//     }
 
//     /**
//      * Updating task
//      * @param String $task_id id of the task
//      * @param String $task task text
//      * @param String $status task status
//      */
//     public function updateTask($user_id, $task_id, $task, $status) {
//         $stmt = $this->conn->prepare("UPDATE tasks t, user_tasks ut set t.task = ?, t.status = ? WHERE t.id = ? AND t.id = ut.task_id AND ut.user_id = ?");
//         $stmt->bind_param("siii", $task, $status, $task_id, $user_id);
//         $stmt->execute();
//         $num_affected_rows = $stmt->affected_rows;
//         $stmt->close();
//         return $num_affected_rows > 0;
//     }
 
//     /**
//      * Deleting a task
//      * @param String $task_id id of the task to delete
//      */
//     public function deleteTask($user_id, $task_id) {
//         $stmt = $this->conn->prepare("DELETE t FROM tasks t, user_tasks ut WHERE t.id = ? AND ut.task_id = t.id AND ut.user_id = ?");
//         $stmt->bind_param("ii", $task_id, $user_id);
//         $stmt->execute();
//         $num_affected_rows = $stmt->affected_rows;
//         $stmt->close();
//         return $num_affected_rows > 0;
//     }
 
//     /* ------------- `user_tasks` table method ------------------ */
 
//     /**
//      * Function to assign a task to user
//      * @param String $user_id id of the user
//      * @param String $task_id id of the task
//      */
//     public function createUserTask($user_id, $task_id) {
//         $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
//         $stmt->bind_param("ii", $user_id, $task_id);
//         $result = $stmt->execute();
//         $stmt->close();
//         return $result;
//     }
 
// }

include_once 'error_code.php';
include_once 'database.php';

define('DB_FIELD_USER_ID',                               'user_id');
define('DB_FIELD_APP_ID',                                'app_id');
define('DB_FIELD_PASSWORD',                              'password');
define('DB_FIELD_PASSWD_HASH',                           'password_hash');
define('DB_FIELD_RESET_PASSWD',                          'reset_passwd');
define('DB_FIELD_LOCKED',                                'locked');

define('DB_FIELD_DEVICE_ID',                             'device_id');
define('DB_FIELD_DEVICE_NAME',                           'device_name');
define('DB_FIELD_LAST_TRANSACTION_ID',                   'last_transaction_id');
define('DB_FIELD_LAST_LOGIN_TIME',                       'last_login_time');
define('DB_FIELD_SYNC_ID',                               'sync_id');
define('DB_FIELD_OP_CODE',                               'op_code');
define('DB_FIELD_LOG',                                   'log');

define('RESPONSE_FIELD_ERROR_CODE',                      'error_code');
define('RESPONSE_FIELD_MESSAGE',                         'message');
define('RESPONSE_FIELD_SESSION_ID',                      'session_id');

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 */
class DBHandler {

    private $db;
 
    function __construct() {
        // Instantiate new Database object
        $this->db = new Database;
    }


    /**
     * Get user info
     * @param String $user_id User login email
     * @param String $fields columns interested in users table
     * @return array[error_code, user_data]
     */
    public function getUserInfo($user_id, $app_id, $fields) {
        $result = array();

        // Set query
        $this->db->query("SELECT $fields FROM users WHERE user_id = :user_id and app_id = :app_id");

        // Bind the user_id
        $this->db->bind(':user_id', $user_id);
        $this->db->bind(':app_id', $app_id);

        if (!$this->db->execute()) {
            $result[RESPONSE_FIELD_ERROR_CODE] = ERROR_CODE_DB_QUERY_FAILED;
            return $result;
        }

        if ($this->db->rowCount() == 0) {
            $result[RESPONSE_FIELD_ERROR_CODE] = ERROR_CODE_DB_NO_RECORD_FOUND;
            return $result;
        }

        // Save returned row
        $result['data'] = $this->db->single();
        if (!$result['data']) {
            $result[RESPONSE_FIELD_ERROR_CODE] = ERROR_CODE_DB_NO_RECORD_FOUND;
            return $result;
        }

        $result[RESPONSE_FIELD_ERROR_CODE] = ERROR_CODE_SUCCESS;
        return $result;
    }


    /**
     * Lock User
     * @param String $user_id User login email
     * @return boolean
     */
    public function lockUser($user_id, $lock=true) {
        // Set query
        $this->db->query("UPDATE users SET locked = $lock WHERE user_id = :user_id");

        // Bind data
        $this->db->bind(':user_id', $user_id);

        // Attempt execution
        // If successful
        if($this->db->execute()){
            // Return True
            return true;
        }
        // Return False
        return false;
    }

    /**
     * Unlock User
     * @param String $user_id User login email
     * @return boolean
     */
    public function unlockUser($user_id) {
        $this->lockUser($user_id, false);
    }

    /**
     * Adding TLog
     * @param Array tlog_info
     * @return boolean User login status success/fail
     */
    public function addTLog($tlog_info) {
        // Set query
        $this->db->query('INSERT INTO transaction_log (`device_id`, `user_id`, `sync_id`, `op_code`, `log`) VALUES (:device_id, :user_id, :sync_id, :op_code, :log)');

        // Bind data
        $this->db->bind(':device_id',   $tlog_info[DB_FIELD_DEVICE_ID]);
        $this->db->bind(':user_id',     $tlog_info[DB_FIELD_USER_ID]);
        $this->db->bind(':sync_id',     $tlog_info[DB_FIELD_SYNC_ID]);
        $this->db->bind(':op_code',     $tlog_info[DB_FIELD_OP_CODE]);
        $this->db->bind(':log',         $tlog_info[DB_FIELD_LOG]);

        // Attempt Execution
        // If successful
        if($this->db->execute()){
            return ERROR_CODE_SUCCESS;
        } else {
            return ERROR_CODE_FAIL_ADDING_TLOG;
        }
    }


    /**
     * Write
     */
    public function upsertUserDevice($user_id, $device_id, $device_name, $last_transaction_id = null){
        // Create time stamp
        $last_login_time = time();

        // Set query
        if($last_transaction_id) {
            $this->db->query('REPLACE INTO devices (`user_id`, `device_id`, `device_name`, `last_transaction_id`, `last_login_time`) VALUES (:user_id, :device_id, :device_name, :last_transaction_id, :last_login_time)');
            $this->db->bind(':last_transaction_id',   $last_transaction_id);
        } else {
            $this->db->query('REPLACE INTO devices (`user_id`, `device_id`, `device_name`, `last_login_time`) VALUES (:user_id, :device_id, :device_name, :last_login_time)');
        }

        // Bind data
        $this->db->bind(':user_id',               $user_id);
        $this->db->bind(':device_id',             $device_id);
        $this->db->bind(':device_name',           $device_name);
        $this->db->bind(':last_login_time',       $last_login_time);

        // Attempt Execution
        // If successful
        if($this->db->execute()){
          // Return True
          return true;
        }

        // Return False
        return false;
    }


    public function createUser($name, $user_id, $password, $account_type, $device_id, $device_name, $app_id) {

        if( $this->isUserExists($user_id, $app_id) ) {
            return ERROR_CODE_USER_ALREADY_EXISTED;
        }

        // Generating password hash
        $password_hash = PassHash::hash($password);

        // Set query
        $this->db->query('REPLACE INTO users (`name`, `user_id`, `password_hash`, `account_type`, `app_id`) VALUES (:name, :user_id, :password_hash, :account_type, :app_id)');

        // Bind data
        $this->db->bind(':name',                  $name);
        $this->db->bind(':user_id',               $user_id);
        $this->db->bind(':password_hash',         $password_hash);
        $this->db->bind(':account_type',          $account_type);
        $this->db->bind(':app_id',                $app_id);

        // Attempt Execution
        // If successful
        if($this->db->execute()){
          // Return True
          return ERROR_CODE_SUCCESS;
        }

        // Return False
        return ERROR_CODE_USER_CREATE_FAILED;
    }

    /**
     * Checking for duplicate user by email address
     * @param String $user_id email to check in db
     * @return boolean
     */
    private function isUserExists($user_id, $app_id) {
        // Set query
        $this->db->query("SELECT id from users WHERE user_id = :user_id and app_id = :app_id");

        // Bind data
        $this->db->bind(':user_id',               $user_id);
        $this->db->bind(':app_id',                $app_id);

        if (!$this->db->execute()) {
            return false;
        }

        if ($this->db->rowCount() != 1) {
            return false;
        }

        return true;
    }


    public function addPhoto($user_id, $app_id, $device_id, $photo_name, $photo_path, $file_hash) {

        if(!$this->isUserExists($user_id, $app_id)) {
            return ERROR_CODE_INVALID_USER;
        }

        // Set query
        $this->db->query('REPLACE INTO photos (`user_id`, `app_id`, `device_id`, `name`, `path`, `hash`) VALUES (:user_id, :app_id, :device_id, :photo_name, :photo_path, :file_hash)');

        // Bind data
        $this->db->bind(':user_id',               $user_id);
        $this->db->bind(':app_id',                $app_id);
        $this->db->bind(':device_id',             $device_id);
        $this->db->bind(':photo_name',            $photo_name);
        $this->db->bind(':photo_path',            $photo_path);
        $this->db->bind(':file_hash',             $file_hash);

        // Attempt Execution
        // If successful
        if($this->db->execute()){
          // Return True
          return ERROR_CODE_SUCCESS;
        }

        // Return False
        return ERROR_CODE_ERROR_INSERT_FILE;
    }

}

?>
