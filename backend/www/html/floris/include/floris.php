<?php
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

}
