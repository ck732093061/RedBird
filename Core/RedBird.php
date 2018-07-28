<?php
namespace RedBird\Core;

class RedBird{
    public function __construct(){		
        set_exception_handler("RedBirdException");
        set_error_handler("RedBirdError");
        if(!defined('APP_PATH')) throw new \Exception("APP_PATH常量未定义");   
		if(!defined('APP_NAME')) throw new \Exception("APP_NAME常量未定义");   
        Config(include REDBIRD_PATH . DIRECTORY_SEPARATOR .'Config' . DIRECTORY_SEPARATOR . 'config.php');		
        if (is_file(APP_PATH . DIRECTORY_SEPARATOR .'Common' . DIRECTORY_SEPARATOR . 'Functions.php')){
            include APP_PATH . DIRECTORY_SEPARATOR .'Common' . DIRECTORY_SEPARATOR . 'Functions.php';
        }
		if(is_file(APP_PATH .DIRECTORY_SEPARATOR . APP_NAME . DIRECTORY_SEPARATOR .'Config' .DIRECTORY_SEPARATOR . 'Config.php')){
            Config(include APP_PATH .DIRECTORY_SEPARATOR . APP_NAME . DIRECTORY_SEPARATOR .'Config' .DIRECTORY_SEPARATOR . 'Config.php');
        }
    }
    
    public static function ParseRoute(){
        if (!empty($_SERVER['PATH_INFO'])){
            $path = trim($_SERVER['PATH_INFO'], '/');
            $_ext = strtolower(pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION));
            $paths = explode('/', $path, 3);
            $paths[0] = isset($paths[0]) ? $paths[0] : '';
            $paths[1] = isset($paths[1]) ? $paths[1] : '';
            $controller = preg_replace('/\.'. $_ext .'$/i', '',$paths[0]);
            $_GET['c'] = empty($controller) ? 'Index' : $controller; 
            $action = preg_replace('/\.'. $_ext .'$/i', '',$paths[1]);
            $_GET['a'] = empty($act) ? 'Index' : $action;
            $_SERVER['PATH_INFO'] = isset($paths[2]) ? $paths[2] : '';
            $var = [];
            preg_replace_callback('/(\w+)\/([^\/]+)/', function($match) use(&$var){$var[$match[1]]=strip_tags($match[2]);}, $_SERVER['PATH_INFO']);
            $_GET = array_merge($var, $_GET);
        }
    }
    
    public static function Run(){
        $controller = isset($_GET['c']) ? $_GET['c'] : 'Index';
        $action = isset($_GET['a']) ? $_GET['a'] : 'Index';
        if (!preg_match('/[a-zA-Z]{1,20}/', $action)){
            exit('act error!');
        }
        if (!preg_match('/[a-zA-Z]{1,20}/', $controller)){
            exit('mod error!');
        }
        define('CONTROLLER_NAME', ucfirst($controller));
        define('ACTION_NAME', ucfirst($action));
        $class = APP_NAME.'\\Controller\\'.ucfirst($controller);
        if (class_exists($class)){
            $controller = new $class();
            $controller->$action();
        } else {
            return false;
        }
    }
}
