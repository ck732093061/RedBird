<?php

function Filter($name, $default = '', $filter = ''){
    if (strpos($name, '.')) {
        list($method, $name) = explode('.', $name, 2);
    } else {
        return '';
    }
    $method = strtoupper($method);
    if ('GET' == $method) {
        $_arr = array_change_key_case($_GET);
    } elseif('POST' == $method){
        $_arr = array_change_key_case($_POST);
    }
    if (isset($_arr[$name])) {
        switch (strtolower($filter)) {
            case 'int':
                $data = (int)$_arr[$name]; 
                break;
            case 'float':
                $data = (float)$_arr[$name];
                break;
            case 'string':
                $data = preg_replace('/[^0-9a-zA-Z]/', '', $_arr[$name]);
                break;
            case 'utf8':
                $data = preg_replace('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', '', $_arr[$name]);
                break;
            case 'num':
                $data = preg_replace('/[^0-9]/', '', $_arr[$name]);
                break;
            default:
                $data = (string)$_arr[$name];
                break;
        }
        return $data;
    } else {
        return $_arr;
    }
}

function Config($name = null, $value = null,$default = null){
    static $_config = [];
    if (empty($name)) {
        return $_config;
    }
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
            if (is_null($value)) {
                return isset($_config[$name]) ? $_config[$name] : $default;
            }
            $_config[$name] = $value;
            return null;
        }
        $name = explode('.', $name);
        $name[0]   =  strtoupper($name[0]);
        if (is_null($value)) {
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        }
        $_config[$name[0]][$name[1]] = $value;
        return null;
    }
    if (is_array($name)){
        $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
        return null;
    }
    return null;
}


function RedBirdException($exception){
  if(DEBUG){
	   echo "<b>异常信息:  </b>".$exception->getMessage()."</br>";
  }
}
   
function RedBirdError($errNo, $errStr, $errFile, $errLine){
  if(DEBUG){
	 echo "<b>错误信息:  </b>".$errStr."</br> <b>错误编号:</b> $errNo </br> <b>错误行数:</b> $errLine</br><b>错误文件: </b>$errFile </br>"; 
  }
}