<?php

define('DEBUG', true);  
define('REDBIRD_PATH',__DIR__); 
if(DEBUG){
	ini_set("display_errors","On");
}else{
	ini_set("display_errors","Off");
}

require __DIR__ . DIRECTORY_SEPARATOR .'Core'.DIRECTORY_SEPARATOR .'Autoloader.php';   //载入自动加载类
require __DIR__ . DIRECTORY_SEPARATOR .'Common'.DIRECTORY_SEPARATOR .'Functions.php';  // 载入核心方法库

$redbird = new RedBird\Core\RedBird;
$redbird::ParseRoute();         
$redbird::Run();            
return $redbird;                
