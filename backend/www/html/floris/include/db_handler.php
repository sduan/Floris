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
     * @param String $user_id User login email id
     * @param String $fields columns interested in users table
     * @return array[error_code, user_data]
     */
    public function getUserInfo($user_id, $fields) {
        $result = array();

        // Set query
        $this->db->query("SELECT $fields FROM users WHERE email = :user_id");

        // Bind the email
        $this->db->bind(':user_id', $user_id);

        if (!$this->db->execute()) {
            $result['error_code'] = ERROR_CODE_DB_QUERY_FAILED;
            return result;
        }

        if ($this->db->rowCount() == 0) {
            $result['error_code'] = ERROR_CODE_DB_NO_RECORD_FOUND;
            return result;
        }

        // Save returned row
        $result['data'] = $this->db->single();
        if (!$result['data']) {
            $result['error_code'] = ERROR_CODE_DB_NO_RECORD_FOUND;
            return result;
        }

        $result['error_code'] = ERROR_CODE_SUCCESS;
        return result;
    }


    /**
     * Lock User
     * @param String $user_id User login email id
     * @return boolean
     */
    public function lockUser($user_id, $lock=true) {
        // Set query
        $this->db->query("UPDATE users SET locked = $lock WHERE email = :user_id");

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
     * @param String $user_id User login email id
     * @return boolean
     */
    public function unlockUser($user_id) {
        $this->lockUser($user_id, false);
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($user_id, $password) {
        // Set query
        $this->db->query('SELECT password_hash FROM users WHERE email = :user_id');
   
        // Bind the email
        $this->db->bind(':user_id', $user_id);
 
        if (!$this->db->execute()) {
            return ERROR_CODE_LOGIN_FAILED;
        }

        if ($this->db->rowCount() == 0) {
            error_log( "Could not find user:$user_id", 0 );
            return ERROR_CODE_LOGIN_WRONG_CREDENTIAL;
        }

        // Save returned row
        $user = $this->db->single();
        if (!$user) {
            error_log( "Could not find valid user:$user_id", 0 );
            return ERROR_CODE_LOGIN_WRONG_CREDENTIAL;   
        }

        if (!PassHash::check_password($user['password_hash'], $password)) {
            error_log( "Credential not match for user:$user_id", 0 );
            return ERROR_CODE_LOGIN_WRONG_CREDENTIAL;
        }

        return ERROR_CODE_SUCCESS;
    }

}

?>
