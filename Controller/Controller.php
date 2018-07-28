<?php

namespace RedBird\Controller;

use RedBird\View\View;

abstract class Controller{
    
    private $view = '';
    
    public function __construct(){
        $this->view = new View(DEBUG);
    }
    
    public function JsonReturn($data){
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode($data);
    }
	
	public function JsonPReturn($data){
        header('Content-Type:application/json; charset=utf-8');
        $result=json_encode($data);
		$callback=$_GET['callback'];  
        echo $callback."($result)";
    }
    
    public function assign($name, $value){
        $this->view->assign($name, $value);
    }
    
    public function display($view = ''){
        $this->view->display($view);
    }
}
