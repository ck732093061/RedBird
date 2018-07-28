<?php

namespace RedBird\View;


class View{
    
    private $tVar = [];

    private $viewFolder = "";
    
    private $tmpView = '';
    
    private $debug = true;
    
    private $replace = [];
    
    protected $tags = [
        'php'       =>  [],
        'volist'    =>  ['attr' => 'name,id,key', 'level' => 3],
        'if'        =>  ['attr' => 'condition', 'level' => 3],
        'elseif'    =>  ['attr' => 'condition', 'close' => 1],
        'else'      =>  ['attr' => '', 'close' => 1],
    ];
    
    protected $comparison = [
        ' nheq ' =>  ' !== ',
        ' heq '  =>  ' === ',
        ' neq '  =>  ' != ',
        ' eq '   =>  ' == ',
        ' egt '  =>  ' >= ',
        ' gt '   =>  ' > ',
        ' elt '  =>  ' <= ',
        ' lt '   =>  ' < '
    ];
    
    public function __construct($debug = false){
        $this->viewFolder = APP_PATH . DIRECTORY_SEPARATOR .APP_NAME . DIRECTORY_SEPARATOR . 'View' .DIRECTORY_SEPARATOR;
        $this->tmpView = APP_PATH . DIRECTORY_SEPARATOR .APP_NAME . DIRECTORY_SEPARATOR . 'temp' .DIRECTORY_SEPARATOR;
        $this->debug = $debug;
    }
    
    public function assign($name, $value){
        $this->tVar[$name] = $value;
    }
    
    public function display($tmpFile = ''){
        $content = $this->fetch($tmpFile);
        $this->render($content);
    }
    
    private function fetch($tmpFile){
        $tmpFile = $this->parseTemplate($tmpFile);
        ob_start();
        ob_implicit_flush(0);
        $params = [
            'var' => $this->tVar, 
            'file' => $tmpFile
        ];
        $this->view_parse($params);
        $content = ob_get_clean();
        return $content;
    }
    
    private function render($content){
        header('Content-Type:text/html; charset=utf-8');
        echo $content;
    }
    
    private function parseTemplate($tmpFile){
        if ('' == $tmpFile ) {
            $file = $this->viewFolder . CONTROLLER_NAME . '/' .ACTION_NAME . '.html';
        } elseif (strpos($tmpFile, '/')) {
            $file = $this->viewFolder . $tmpFile . '.html';
        } elseif (strpos($tmpFile, ':')) {
            $_tmpFile = str_replace(':', '/', $tmpFile);
            $file = $this->viewFolder . $_tmpFile . '.html';
        } else {
            $file = $this->viewFolder . $tmpFile . '.html';
        }
        if (!is_file($file)) {
            exit('Template Not Found!');
        }
        return $file;
    }
    
    private function view_parse($_data){
        if ($this->checkCache($_data['file']) && false === $this->debug) {
            if (!is_null($_data['var'])){
                extract($_data['var'], EXTR_OVERWRITE);
            }
            $tmplCacheFile = $this->tmpView . md5($_data['file']) . '.php';
            include $tmplCacheFile;
        } else {
            $tmplContent =  file_get_contents($_data['file']);
            $tmplCacheFile = $this->tmpView . md5($_data['file']) . '.php';
            $tmplContent = $this->compiler($tmplContent);
            $this->put($tmplCacheFile, $tmplContent);
            $this->load($tmplCacheFile, $_data['var']);
        }
    }
   
    private function checkCache($file){
        $tmplCacheFile = $this->tmpView . md5($file) .'.php';
        if (!is_file($tmplCacheFile)) {
            return false;
        } elseif (filemtime($file) > filemtime($tmplCacheFile)) {
            return false;
        }
        return true;
    }
    
    private function put($filename, $content){
        $dir         =  dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (false === file_put_contents($filename, $content)) {
            return false;
        }
        return true;
    }
   
    private function load($_filename, $vars=null){
        if (!is_null($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        include $_filename;
    }
    
    private function compiler($content){
        if (empty($content)) {
            return '';
        }
        $content = $this->parseInclude($content);
        $content    =   $this->parsePhp($content);
        $content = preg_replace('#<!\-\-([^\[])([\s\S]*?)([^\]])\-\->#is', '', $content);
        foreach ($this->tags as $key => $tag) {
            $closeTag = isset($tag['close']) ? true : false;
            $level = isset($tag['level']) ? $tag['level'] : false;
            $n1 = empty($tag['attr']) ? '(\s*?)' : '\s([^>]*)';
            $that = $this;
            
            if ($closeTag) {
                $patterns = '/<' . $key . $n1 . '\/(\s*?)>/is';
                $content = preg_replace_callback($patterns, function($matches) use($key, $that){
                    $parse = '_' . $key;
                    return $that->$parse($matches[1], $matches[2]);
                }, $content);
            } else {
                $patterns = '/<' . $key . $n1 . '>(.*?)<\/' . $key . '(\s*?)>/is';
                $content = preg_replace_callback($patterns, function($matches) use($key, $that){
                    $parse = '_' . $key;
                    return $that->$parse($matches[1], $matches[2]);
                }, $content);
                if ($level) {
                    for ($i = 0; $i < $level; $i++) {
                        $patterns = '/<' . $key . $n1 . '>(.*?)<\/' . $key . '(\s*?)>/is';
                        $content = preg_replace_callback($patterns, function($matches) use($key, $that){
                            $parse = '_' . $key;
                            return $that->$parse($matches[1], $matches[2]);
                        }, $content);
                    }
                }
            }
        }
        $content = str_ireplace(array_keys($this->comparison),array_values($this->comparison), $content);
        $content = preg_replace_callback('/({)([^\d\w\s{}].+?)(})/is', [$this, 'parseTag'], $content);
        $content = preg_replace('/\?>(\s+)<\?php /i', '', $content);
        $content = str_replace(array_keys($this->replace), array_values($this->replace), $content);
        return $content;
    }
   
    private function parseInclude($content){
        $find = preg_match_all('/<include\s(.+?)\s*?\/>/is', $content, $matches);
        if ($find) {
            for ($i = 0; $i < $find; $i++) {
                $include    =   $matches[1][$i];
                $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")\s?/';
                preg_match_all($reg, $include, $array);
                $new_arr = array_combine($array[1], $array[2]);
                $file       =   trim($new_arr['file'], '"\'');
                $content    =   str_replace($matches[0][$i], $this->parseIncludeItem($file), $content);
            }
        }
        return trim($content);
    }
    
    private function parseIncludeItem($file){
        
        if (false !== strpos($file, ':')) {
            $file = $this->viewFolder . '/' . str_replace(':', '/', $file) . '.html';
        } else {
            $file = $this->viewFolder . '/' . $file . '.html';
        }
        if (!is_file($file)) {
            exit('Include Template Not Found!');
        }
        return file_get_contents($file);
    }
    
    private function parsePhp($content){
        if (ini_get('short_open_tag')) {
            $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $content );
        }
        return $content;
    }
    
    private function parseTag($tagStr){
        if (is_array($tagStr)) {
            $tagStr = $tagStr[2];
        }
        $tagStr = stripslashes($tagStr);
        $flag   =  substr($tagStr,0,1);
        $flag2  =  substr($tagStr,1,1);
        $name   = substr($tagStr,1);
        if ('$' == $flag && '.' != $flag2 && '(' != $flag2) {
            if (false !== strpos($name, '.')) {
                $vars = explode('.', $name);
                $var  =  array_shift($vars);
                $name = $var;
                foreach ($vars as $key => $val){
                    $name .= '["' . $val . '"]';
                }
                return '<?php echo ' . $flag . $name . ';?>';
            } else {
                return  '<?php echo ' . $flag . $name . ';?>';
            }
        } elseif ('//' == substr($tagStr, 0, 2) || '/*' == (substr($tagStr, 0, 2) && '*/' == substr(rtrim($tagStr), -2))) {
            return '';
        }
    }
   
    private function _php($content){
        $parseStr = '<?php ' . $content . ' ?>';
        return $parseStr;
    }
   
    private function _if($match1, $match2){
        $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")(\s?)/';
        preg_match_all($reg, $match1, $match);
        $new_arr = array_combine($match[1], $match[2]);
        $parseStr = '<?php if(' . substr($new_arr['condition'], 1, -1) . '){ ?>' . $match2 . '<?php }?>';
        return $parseStr;
    }
    
    private function _elseif($match1, $match2){
        $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")(\s?)/';
        preg_match_all($reg, $match1, $match);
        $new_arr = array_combine($match[1], $match[2]);
        $parseStr = '<?php }elseif(' . trim($new_arr['condition'], '"') . '){ ?>';
        return $parseStr;
    }
   
    private function _else(){
        $parseStr = '<?php }else{ ?>';
        return $parseStr;
    }
    
    private function _volist($match1, $match2){
        $reg = '/(\w+)=(\'[^\']+\'|"[^"]+")(\s?)/';
        preg_match_all($reg, $match1, $match);
        $new_arr = array_combine($match[1], $match[2]);
        
        $name  =    '$' . trim($new_arr['name'], '"');
        $id    =    '$' . trim($new_arr['id'], '"');
        
        $parseStr = '<?php if(is_array(' . $name . ')){foreach(' . $name . ' as $key => ' . $id . '){ ?>' . $match2 . '<?php }}?>';
        return $parseStr;
    }
}