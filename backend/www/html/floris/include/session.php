<?php
include_once 'database.php';

/**
 * Session item name constants
 */
define('LOCKED',                                'locked');
define('USER_ID',                               'user_id');
define('LOGIN_ERROR_COUNT',                     'login_error_count');
define('LAST_ACTIVITY',                         'last_activity');
define('CREATED',                               'created');

class Session {

    /**
     * Db Object
     */
    private $db;

    public function __construct(){
        // Instantiate new Database object
        $this->db = new Database;

        // Set handler to overide SESSION
        session_set_save_handler(
            array($this, "_open"),
            array($this, "_close"),
            array($this, "_read"),
            array($this, "_write"),
            array($this, "_destroy"),
            array($this, "_gc")
        );
 
        // Start the session
        session_cache_limiter(false);
        session_start();
    }

    /**
     * Open
     */
    public function _open(){
      // If successful
      if($this->db){
        // Return True
        return true;
      }
      // Return False
      return false;
    }


    /**
     * Close
     */
    public function _close(){
      // Close the database connection
      // If successful
      if($this->db->close()){
        // Return True
        return true;
      }
      // Return False
      return false;
    }

    /**
     * Read
     */
    public function _read($id){
      // Set query
      $this->db->query('SELECT data FROM sessions WHERE id = :id');

      // Bind the Id
      $this->db->bind(':id', $id);

      // Attempt execution
      // If successful
      if($this->db->execute()){
        // Save returned row
        $row = $this->db->single();
        // Return the data
        return $row['data'];
      }else{
        // Return an empty string
        return '';
      }
    }
     
    /**
     * Write
     */
    public function _write($id, $data){
      // Create time stamp
      $access = time();

      // Set query
      $this->db->query('REPLACE INTO sessions VALUES (:id, :access, :data)');

      // Bind data
      $this->db->bind(':id', $id);
      $this->db->bind(':access', $access);
      $this->db->bind(':data', $data);

      // Attempt Execution
      // If successful
      if($this->db->execute()){
        // Return True
        return true;
      }

      // Return False
      return false;
    }


    /**
     * Destroy
     */
    public function _destroy($id){
      // Set query
      $this->db->query('DELETE FROM sessions WHERE id = :id');

      // Bind data
      $this->db->bind(':id', $id);

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
     * Garbage Collection
     */
    public function _gc($max){
      // Calculate what is to be deemed old
      $old = time() - $max;

      // Set query
      $this->db->query('DELETE * FROM sessions WHERE access < :old');

      // Bind data
      $this->db->bind(':old', $old);

      // Attempt execution
      if($this->db->execute()){
        // Return True
        return true;
      }

      // Return False
      return false;
    }

    /**
     * Get Session User ID
     */
    public function getUserID(){
        if( isset($_SESSION[USER_ID]) ) {
            return $_SESSION[USER_ID];
        }
        return null;
    }


    public function checkSessionTimeout() {
        $timeout = false;
        if (isset($_SESSION[LAST_ACTIVITY]) && (time() - $_SESSION[LAST_ACTIVITY] > 300)) {
           // last request was more than 5 minutes ago
            session_unset();     // unset $_SESSION variable for the run-time
            session_destroy();   // destroy session data in storage
            $timeout = true;
        }
        $_SESSION[LAST_ACTIVITY] = time(); // update last activity time stamp

        if (!isset($_SESSION[CREATED])) {
            $_SESSION[CREATED] = time();
        } else if (time() - $_SESSION[CREATED] > 300) {
            // session started more than 5 minutes ago
            session_regenerate_id(true);    // change session ID for the current session an invalidate old session ID
            $_SESSION[CREATED] = time();  // update creation time
        }
        return $timeout;
    }
}
