<?php

namespace System\Controller;

use System\Communicate\Debug\Console;
use System\Communicate\Cache;

class RouteHandleException extends \Exception{}


/**
 * Route is a class to manage virtual urls like routing and show related pages.
 */
class Route{

    private static $RefNull = null;
    private static $RouteIdTable = [];
    private static $GroupListData = [];
    private static $CurrentGroupName = null;

    //error codes
    const MiddleWare = 0x2;
    const MethodParams = 0x4;
    const UrlParams = 0x8;
    const Others = 0x16;
    const Method = 0x32;
    

    // error on return data from validators
    const NotValid = "~~~ROUTE_NOT_VALID~~~";

    // use in events
    const ALL = 0x32ab;


    const ACCEPTABLE_METHODS = [
        "post","get","file","put","delete","patch",
        "copy","options","link","unlink","purge","lock",
        "unlock","propfind","view"
    ];


    const ROOT = __ROOT__;
    const HOST = __HOST__;
    const UPLOAD = self::ROOT . DIRECTORY_SEPARATOR. "Upload" . DIRECTORY_SEPARATOR;
    const UPLOAD_URL = self::HOST . "/public/";
    const APP = self::ROOT . DIRECTORY_SEPARATOR . __APP_FOLDER__ . DIRECTORY_SEPARATOR;
    const VIEW_PATH = self::APP . "View" . DIRECTORY_SEPARATOR;
    const CONTROLLER_PATH = self::APP  . "Controller" . DIRECTORY_SEPARATOR;
    const MODEL_PATH = self::APP  . "Model" . DIRECTORY_SEPARATOR;
    const LANG_PATH = self::APP  . "Language" . DIRECTORY_SEPARATOR;
    const HTACCESS_DEFAULT = '<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase {{PATH}}
    Options -Indexes
    
    
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
    
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteCond %{REQUEST_URI} !^{{PATH}}$
    RewriteRule ^ fault/403 [QSA,L]
    
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule \.*$ fault/403
    
    </IfModule>';

    // the name of root folder if it is in a subfolder
    protected static $rootPath = "";
    // list of virtual pathes in shape of tree
    protected static $virtuals = array();
    // list of params filters
    protected static $filters = array();
    // list of route middlewares
    protected static $middlewares = array();
    // list of events to fire on routing
    protected static $events = array(
        "before"=>array(),
        "after"=>array(),
        "start"=>array(),
        "init"=>array(),
        "accept"=>array(),
        "end"
    );
    protected static $_hasRouteError = false;
    public static function routeError($yes=true){
        self::$_hasRouteError = !(!$yes);
    }

    public static function hasError(){
        return self::$_hasRouteError;
    }
    

    // current page id
    protected static $_currentId = null;

    //current route register that found
    protected static $_currentRouteRegister = null;

    /**
     * get current page id
     * @return string
     */
    public static function currentID(){
        return self::$_currentId;
    }


    /**
     * current route reffrence
     * @return RouteRegister
     */
    public static function current(){
        return self::$_currentRouteRegister;
    }



    /**
     * checks if there is requested filter or not
     * @param string $name
     * @return bool
     */
    public static function hasFilter($name){
        if( !is_string($name) ){
            return false;
        }
        $name = strtolower($name);
        $fl = &self::$filters;
        if( isset($fl[$name]) ){
            return true;
        }
        return false;
    }

    public static function find($pattern,$folder=null){
        if( $folder === null ){
            $folder = self::dir();
        }
        else if( is_string($folder) ){
            $folder = self::dir($folder);
        }
        else{
            throw new RouteHandleException("folder name is not currect");
        }

        if( !is_string($pattern) ){
            throw new RouteHandleException("pattern is not currect");
        }

        $dir = new \RecursiveDirectoryIterator($folder);
        $ite = new \RecursiveIteratorIterator($dir);
        $files = new \RegexIterator($ite, $pattern, \RegexIterator::GET_MATCH);
        $fileList = array();
        foreach($files as $file) {
            $fileList = array_merge($fileList, $file);
        }
        return $fileList;
    }

    /**
     * call filter with passing arguments
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callFilter( $name , ...$args ){
        if( $args === null ){
            $args = array();
        }

        if( self::hasFilter($name) ){
            return call_user_func_array(self::$filters[strtolower($name)],$args);
        }
        return null;
    }

    /**
     * checks if there is requested middleware or not
     * @param string $name
     * @return bool
     */
    public static function hasMiddleWare($name){
        if( !is_string($name) ){
            return false;
        }
        $fl = &self::$middlewares;
        if( isset($fl[$name]) ){
            return true;
        }
        return false;
    }

    /**
     * call middleware with passing arguments
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function callMiddleWare( $name , ...$args ){
        if( $args === null ){
            $args = array();
        }

        if( self::hasMiddleWare($name) ){
            return call_user_func_array(self::$middlewares[$name],$args);
        }
        return null;
    }

    /**
     * generate the route url if requested or root url
     * @param string $route
     * @param array $params
     * @return string
     */
    public static function url($route=null,$params=null){
        if( is_string($route) ){
            if( !preg_match("/[\/]/",$route) ){
                $path = self::compileRouteId($route,$params);
                if( $path !== false ){
                    return self::HOST . self::$rootPath. "/" . $path;
                }
            }
            return self::HOST . self::$rootPath. "/" . $route;
        }
        return self::HOST . self::$rootPath;
    }

    /**
     * generate the route url if requested or root url
     * @param string $route
     * @param array $params
     * @return string
     */
    public static function dir($route=null,$params=null){
        
        if( is_dir($route) ){
            return $route;
        }
        else if( is_string($route) ){
            $path = self::compileRouteId($route,$params);
            if( !$path ){
                return self::ROOT . DIRECTORY_SEPARATOR . $route . DIRECTORY_SEPARATOR;
            }
            return self::ROOT . DIRECTORY_SEPARATOR . $path. DIRECTORY_SEPARATOR;
        }
        return self::ROOT . DIRECTORY_SEPARATOR;
    }

    /**
     * create path from route id and params
     * @param string $route
     * @param array $params
     * @return string|false
     */
    public static function compileRouteId($route,$params=null){
        $_route = explode(".",strtolower($route));
        
        $tree = array("body"=>null,"sub"=>self::$virtuals);
        foreach( $_route as $key ){
            if( isset( $tree["sub"][$key] ) ){
                $tree = $tree["sub"][$key];
            }
            else{
                return false;
            }
        }
        if( $tree["body"] !== null ){
            try{
                return $tree["body"]->path($params);
            }
            catch(\Exception $e){
                return "";
            }
        }
        return false;
    }

    /**
     * parse url to route details
     * @param string $url
     * @return array
     * @throws RouteHandleException
     */
    public static function parseRouteURL($url){
        $pattern = "/^\{((\\\\\{|\\\\\}|[^{}])+)\}(\[(\.\.\.|[0-9]+)\])?(\?)?$/";
        if( is_string($url) ){
            $url = explode("/",trim(strtolower($url),"/"));
            $details = array();
            $optionalStart = false;
            $toEndData = false;
            foreach( $url as $key=>$path ){
                if( !(!preg_match($pattern,$path,$matches)) ){
                    $isOptional = isset($matches[5]) && !empty($matches[5]);
                    $dataCount = (isset($matches[4]) && !empty($matches[4]))?($matches[4]=="..."?(-1):((int)$matches[4])):1;
                    $matches = explode("|",$matches[1]);

                    if( !$toEndData && $dataCount == -1 ){
                        $toEndData = true;
                    }
                    else if( $toEndData ){
                        throw new RouteHandleException("no params permitted to be exist after unlimited count param");
                    }
                    
                    if( !$optionalStart && $isOptional ){
                        $optionalStart = true;
                    }
                    else if( $optionalStart && !$isOptional ){
                        throw new RouteHandleException("url params after a optional param must be optional");
                    }

                    if( count($matches) <= 1 ){
                        throw  new RouteHandleException("url directives must have a name that separated by `|` .");
                    }
                    $details[] = array(
                        "name"=>trim($matches[1]),
                        "filter"=>trim($matches[0]),
                        "optional"=>$isOptional,
                        "count"=>$dataCount
                    );
                }
                else{
                    $details[] = $path;
                }
            }

            return $details;
        }
        throw new RouteHandleException("url must be string");
    }

    /**
     * handling route if exist 
     * @param string $url
     * @param string $method
     * @return bool
     * @throws RouteHandleException
     */
    public static function call($url,$method){
        if( !is_string($url) || !is_string($method) ){
            throw new RouteHandleException("arguments are not in correct format");
        }

        $method = strtolower($method);
        $route = explode("/",strtolower($url));
        $tree = array("body"=>null,"sub"=>&self::$virtuals);
        $finded = false;
        $counter = 0;
        while( isset($tree["sub"]) && !empty($tree["sub"]) ){

            if( $finded && $tree["body"] !== null && $tree["body"]->isUrlAcceptable($url) ){
                break;
            }

            if( isset($route[$counter]) && isset( $tree["sub"][$route[$counter]] ) ){
                $tree = $tree["sub"][$route[$counter]];
                $finded = true;
            }
            else if( isset( $tree["sub"]["arg".($counter+1)] ) ){
                $tree = $tree["sub"]["arg".($counter+1)];
                $finded = true;
            }
            else{
                $finded = false;
                break;
            }
            $counter++;
        }
        
        if( $finded && isset($tree["body"]) && !empty($tree["body"]) ){

            self::$_currentRouteRegister = $tree["body"];

            self::$_currentId = $tree["body"]->id();
            
            self::fireEvent( "start",$tree["body"]->id() , $tree["body"] );
            
            if( !$tree["body"]->accept($method) ){
                return $tree["body"]->fireError(Route::Method);
            }
            
            $params = $tree["body"]->validateRoute($url);
            
            if( $params === false ){
                return false;
            }
            else if( $params === true ){
                return true;
            }

            self::fireEvent( "accept",$tree["body"]->id() , $tree["body"] );
            
            $res = $tree["body"]->call($method,$params);
            Route::routeError(false);

            self::fireEvent( "after",$tree["body"]->id() , $tree["body"] );
            return $res;
        }
        else{
            Route::routeError();
            return false;
        }
    }

    /**
     * get file path from controllers/models/langs/views
     * @param string $name in format of "view: index.river.php"
     * @return string|null
     */
    public static function path($name){
        
        if( file_exists($name) ){
            return $name;
        }
        $cat = explode(":",$name,2);
        $path = "";
        if( count($cat) > 1 ){
            $cat[0] = trim($cat[0]);
            $cat[1] = trim($cat[1]);

            if( file_exists($cat[1]) ){
                return $cat[1];
            }
            switch(trim(strtolower($cat[0]))){
                case "control":$path = self::CONTROLLER_PATH . $cat[1];break;
                case "model":$path = self::MODEL_PATH . $cat[1];break;
                case "lang":$path = self::LANG_PATH . $cat[1];break;
                default:$path = self::VIEW_PATH . $cat[1];break;
            }
            return $path;
        }
        else if( !filter_var($cat[0], FILTER_VALIDATE_URL) ){
            $path = self::ROOT . '/' . trim($cat[0],"/");
        }
        else{
            return null;
        }
        if( file_exists($path) ){
            return $path;
        }
        return null;
    }

    /**
     * register new route
     * @param string $path
     * @return RouteRegister
     * @throws RouteHandleException
     */
    public static function &register($pathes){
        $fileCalled = @debug_backtrace();
        if( empty($fileCalled) ){
            $fileCalled = null;
        }
        else{
            try{
                $file = rtrim(str_replace(self::CONTROLLER_PATH,"",$fileCalled[0]["file"]),"/\\");
                $fileCalled = rtrim(dirname($file)) . DIRECTORY_SEPARATOR . basename($file,".controller.php");
            }
            catch(\Exception $e){
                $fileCalled = null;
            }
        }

        $output = [];

        if( !is_array($pathes) ){
            $pathes = [$pathes];
        }

        foreach( $pathes as $path ){
            if( self::$CurrentGroupName !== null ){
                $path = (self::$CurrentGroupName["path"] ? self::$CurrentGroupName["path"] : "") . $path;
            }

            $path = self::parseRouteURL($path);

            $newPath = array();
            $counter = 1;
            $idPath = [];
            foreach( $path as $id ){
                if( is_string($id) ){
                    $newPath[] = $id;
                    $idPath[] = $id;
                }
                else{
                    $newPath[] = "arg".$counter;
                }
                $counter++;
            }

            self::$RouteIdTable[implode(".",$idPath)] = $newPath;

            $pointer = &self::virtualRecursiveEdit($newPath);

            if( $pointer !== null && $pointer["body"] === null ){
                $pointer["body"] = new RouteRegister($path,$fileCalled,self::$CurrentGroupName);
                $output[] = &$pointer["body"];
            }
            else{
                throw new RouteHandleException("the route has been registered before!");
            }
        }

        $uc = count($output);
        if( $uc > 1 ){
            return $output;
        }
        else if( $uc <=0 ){
            return null;
        }
        return $output[0];
    }

    /**
     * walk the virtual pathes tree to fetch requested route if exist or create it if not
     * @param array $list the list of route details
     * @param array $tree
     * @param int $n the length of $list
     * @param int $now current position
     * @return array|null
     * @throws RouteHandleException
     */
    protected static function &virtualRecursiveEdit($list,&$tree=null,$n=0,$now=0){
        if( $tree === null ){
            $tree = array("body"=>null,"sub"=>&self::$virtuals);
            $n = count($list) - 1;
            if( $n < 0 ){
                return self::$RefNull;
            }
        }

        if( !is_string($list[$now]) ){
            throw new RouteHandleException("path details must be array of string");
        }

        if( $n == $now ){
            if( !isset($tree["sub"][$list[$n]]) ){
                $tree["sub"][$list[$n]] = array(
                    "body"=>null,
                    "sub"=>array()
                );
            }
            
            return $tree["sub"][$list[$n]];
        }
        else if( !isset( $tree["sub"][$list[$now]] ) ){
            $tree["sub"][$list[$now]] = array(
                "body"=>null,
                "sub"=>array()
            );
        }
        return self::virtualRecursiveEdit($list,$tree["sub"][$list[$now]],$n,$now+1);

    }

    
    /**
     * create a group with parameters like path, middleware , ... to apply to all registers in sub of
     * this group
     * @param string $name
     * @param object $val
     */
    public static function createGroup($name,$val){
        if( is_array($val) ){
            $value["path"] = (isset($val["path"]) && is_string($val["path"])) ? $val["path"] : null;
            $value["error"] = (isset($val["error"]) && is_callable($val["error"])) ? $val["error"] : null;
            $value["output"] = (isset($val["output"]) && is_callable($val["output"])) ? $val["output"] : null;
            $value["middlewares"] = (isset($val["middlewares"]) && is_array($val["middlewares"])) ? $val["middlewares"] : null;
            $value["attaches"] = (isset($val["attaches"]) && is_array($val["attaches"])) ? $val["attaches"] : null;
    
            foreach(self::ACCEPTABLE_METHODS as $meth){
                if( isset($val[$meth]) && is_array($val[$meth]) ){
                    $value[$meth] = $val[$meth];
                }
                else{
                    $value[$meth] = null;
                }
            }
            self::$GroupListData[$name] = $value;
        }
    }


    public static function group($names,$callback){
        if( !is_array($names) ){
            $names = [$names];
        }
        
        foreach($names as $name){
            if( isset(self::$GroupListData[$name]) && is_callable($callback) ){
                self::$CurrentGroupName = self::$GroupListData[$name];
                $ref = &self::$GroupListData[$name];

                $args = [$ref["path"],$ref["error"],$ref["middlewares"],$ref["output"]];
        
                $methods = [];
                foreach(self::ACCEPTABLE_METHODS as $meth){
                    $methods[$meth] = $ref[$meth];
                }

                $args[] = $methods;

                call_user_func_array(
                    $callback,
                    $args
                );
            }
        }
        self::$CurrentGroupName = null;
        return false;
    }



    /**
     * remove regestered route
     * @param string $name
     * @return bool
     */
    public static function unRegister($name){
        $table = &self::$RouteIdTable;
        if( !isset($table[$name]) ){
            return false;
        }

        $pointer = &self::virtualRecursiveEdit($table[$name]);
        if( $pointer !== null && $pointer["body"] !== null ){
            unset($pointer["body"]);
            $pointer["body"] = array();
            unset(self::$RouteIdTable[$name]);
            return true;
        }
        return false;
    }

    /**
     * init .htaccess file for the first time and reinitialize it on needs
     * @param string $path
     * @param array $others
     * @param bool $force
     * @return bool
     * @throws RouteHandleException
     */
    public static function htaccess($path,$others=array(),$force=false){
        self::$rootPath= empty(trim(trim($path,"/")))?"":("/" . trim($path,"/"));

        if( !$force && file_exists(self::ROOT . "/.htaccess") ){
            return false;
        }

        $htaccess = str_replace("{{PATH}}",empty(trim($path,"/"))?"/":("/".trim($path,"/")."/"),self::HTACCESS_DEFAULT);
        if( is_array($others) ){
            foreach( $others as &$val ){
                if( is_string($val) ){
                    $htaccess .= $val;
                }
            }
        }

        try{
            $file = fopen(self::ROOT . DIRECTORY_SEPARATOR.".htaccess","w");
            echo fwrite($file,$htaccess);
            fclose($file);
        }
        catch(RouteHandleException $e){
            throw new RouteHandleException("cant open .htaccess file : ".$e->getMessage());
        }
        return true;
    }

    /**
     * check route if a route has been registered
     * @param string $name
     * @return bool|null null if not exists . false if path exists but with no routerregister
     */
    public static function check($path){
        if( is_string( $path ) ){
            $path = explode(".",$path);
        }
        else if( !is_array($path) ){
            throw new RouteHandleException("the path can be route id in string or route details in array. ");
        }

        $tree = array("body"=>null,"sub"=>self::$virtuals);
        foreach( $path as $id ){
            if( isset( $tree["sub"][$id] ) ){
                $tree = $tree["sub"][$id];
            }
            else{
                return null;
            }
        }

        if( $tree["body"] === null ){
            return false;
        }
        return true;
    }

    /**
     * insert a filter
     * @param string $name
     * @param callable $callback
     * @return void 
     * @throws RouteHandleException
     */
    public static function filter($name,$callback){
        if( !is_string($name) || !is_callable(($callback)) ){
            throw new RouteHandleException("arguments are not in correct format!");
        }
        self::$filters[strtolower($name)] = $callback;
    }

    /**
     * insert a middleware
     * @param string $name
     * @param callable $callback
     * @return void 
     * @throws RouteHandleException
     */
    public static function middleWare($name,$callback){
        if( !is_string($name) || !is_callable(($callback)) ){
            throw new RouteHandleException("arguments are not in correct format!");
        }
        self::$middlewares[$name] = $callback;
    }

    /**
     * redirect to an other route like ("route: user.admin") or a url
     * @param string $route
     * @param array $params
     * @return void 
     */
    public static function redirect($route,$params=array(),$code=302){
        $cat = explode(":",$route,2);
        $path = "";
        if( count($cat) > 1 && trim(strtolower($cat[0])) == "route" ){
            $path = self::url(trim(trim($cat[1],"/\\")),$params);
        }
        else{
            if( filter_var($route, FILTER_VALIDATE_URL) ){
                $path = $route;
            }
            else{
                $path = self::url() . DIRECTORY_SEPARATOR . trim($route,"/\\");
            }
        }
        Cache::storeEdited();
        header("Location: $path",true,$code==302?302:301);
        exit();
    }

    /**
     * set header to temporarily  redirect
     * @param string $route
     * @param array $params
     * @return void 
     */
    public static function tempRedirect($route,$params=array()){
        self::redirect($route,$params,301);
    }

    /**
     *  get a header data 
     * @param  string $name
     * @return string|null
     */
    public static function headers($name=null){
        $headers = [];
        if( function_exists('\getallheaders') ){
            $headers = \getallheaders();
        }
        else if(function_exists('\apache_request_headers')){
            $headers = \apache_request_headers();
        }
        else{
            
            foreach ($_SERVER as $name => $value) { 
                if (substr($name, 0, 5) == 'HTTP_') { 
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))); 
                    $headers[$name] = $value; 
                } 
                else if ($name == "CONTENT_TYPE") { 
                    $headers["Content-Type"] = $value; 
                } 
                else if ($name == "CONTENT_LENGTH") { 
                    $headers["Content-Length"] = $value; 
                } 
            } 
        }

        if( $name === null ){
            return $headers;
        }

        if( isset($headers[$name]) ){
            return $headers[$name];
        }
        
        return null;
    }

    /**
     * listen to the request to fire its route
     * @return void
     * @throws RouteHandleException
     */
    public static function listen(){
        $URL_Request = trim(isset($_GET["q"])?$_GET["q"]:"","/");
        unset($_GET["q"]);

        self::fireEvent( "init", 0 , null );
        $res = self::call($URL_Request,strtolower($_SERVER['REQUEST_METHOD']));
        if( $res === false ){
            $res = self::call("fault/403","get");
            
            if( !$res ){
                throw new RouteHandleException("the route is not found and the error route is not accessable!");
            }
            else if( $res instanceof  \System\Controller\Response ){
                echo $res->printContent();
            }
            return;
        }
        else if( $res instanceof  \System\Controller\Response ){
            echo $res->printContent();
        }

        self::fireEvent( "end",self::$_currentId,self::$_currentRouteRegister);

        if( Console::isAutoFlush() && RouteRegister::getAttaches("api") === null && !RouteRegister::getAttaches("noConsole") ){
            Console::flush();
        }
    }

    /**
     * insert event to fire on start of route proccess
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    public static function onStart($vpath,$callback){
        return self::addEvent("start",$vpath,$callback);
    }

    /**
     * insert event to fire before route proccess
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    public static function onBefore($vpath,$callback){
        return self::addEvent("before",$vpath,$callback);
    }

    /**
     * insert event to fire after route proccess
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    public static function onAfter($vpath,$callback){
        return self::addEvent("after",$vpath,$callback);
    }

    /**
     * insert event to fire on route initialization proccess
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    public static function onInit($callback){
        return self::addEvent("init",self::ALL,$callback);
    }

    /**
     * insert event to fire on end of route proccess
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    public static function onEnd($vpath,$callback){
        return self::addEvent("end",$vpath,$callback);
    }

    /**
     * insert event to fire on right before calling route proccess
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    public static function onAccept($vpath,$callback){
        return self::addEvent("accept",$vpath,$callback);
    }


    /**
     * add event 
     * @param string $on
     * @param string $vpath
     * @param callable $callback
     * @return void
     * @throws RouteHandleException
     */
    protected static function addEvent($on,$vpath,$callback){
        if( (!is_string($vpath) && $vpath !== self::ALL) || !is_callable($callback) ){
            throw new RouteHandleException("Route Event has no correct argument");
        }
        $event = &self::$events[$on];
        if( !isset($event[$vpath]) ){
            $event[$vpath] = array();
        }
        $event[$vpath][] = $callback;
    }

    /**
     * evaluate event 
     * @param string $side
     * @param string $name
     * @param RouteRegister $route
     * @return void
     */
    public static function fireEvent($side,$name,$route=null){
        $side = &self::$events[$side == "before"?"before":($side=="after"?"after":($side=="init"?"init":($side=="accept"?"accept":($side=="start"?"start":"end"))))];
        if( isset( $side[$name] ) && is_array($side[$name]) ){
            foreach( $side[$name] as $event ){
                if( is_callable($event) ){
                    call_user_func($event,$route);
                }
            }
        }

        if( isset( $side[self::ALL] ) && is_array($side[self::ALL]) ){
            foreach( $side[self::ALL] as $event ){
                if( is_callable($event) ){
                    call_user_func($event,$route);
                }
            }
        }
    }

    /**
     * return the visitor ip address
     * @return string|false
     */
    static public function ip(){
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    $ip = trim($ip);
                    // attempt to validate IP
                    if (self::validateIP($ip)) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : false;
    }

    /**
     * parse the user agent of cient to extract os , browser and its version
     * Parses a user agent string into its important parts
     *
     * @author Jesse G. Donat <donatj@gmail.com>
     *
     * @link https://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
     * @link https://github.com/donatj/PhpUserAgent
     *
     * @license MIT
     * @return Array
     */
    static public function userAgent(){
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
    
        $platform = null;
        $browser  = null;
        $version  = null;
    
        $empty = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );
    
        if( !$u_agent ) {
            return $empty;
        }
    
        if( preg_match('/\((.*?)\)/m', $u_agent, $parent_matches) ) {
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|(Open|Net|Free)BSD|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS|Switch)|Xbox(\ One)?)
                    (?:\ [^;]*)?
                    (?:;|$)/imx', $parent_matches[1], $result);
    
            $priority = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11' );
    
            $result['platform'] = array_unique($result['platform']);
            if( count($result['platform']) > 1 ) {
                if( $keys = array_intersect($priority, $result['platform']) ) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } elseif( isset($result['platform'][0]) ) {
                $platform = $result['platform'][0];
            }
        }
    
        if( $platform == 'linux-gnu' || $platform == 'X11' ) {
            $platform = 'Linux';
        } elseif( $platform == 'CrOS' ) {
            $platform = 'Chrome OS';
        }
    
        preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
                    TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|Edg|CriOS|UCBrowser|Puffin|OculusBrowser|SamsungBrowser|
                    Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
                    Valve\ Steam\ Tenfoot|
                    NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
                    (?:\)?;?)
                    (?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent, $result);
    
        // If nothing matched, return null (to avoid undefined index errors)
        if( !isset($result['browser'][0]) || !isset($result['version'][0]) ) {
            if( preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result) ) {
                return array( 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null );
            }
    
            return $empty;
        }
    
        if( preg_match('/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result) ) {
            $rv_result = $rv_result['version'];
        }
    
        $browser = $result['browser'][0];
        $version = $result['version'][0];
    
        $lowerBrowser = array_map('strtolower', $result['browser']);
    
        $find = function ( $search, &$key = null, &$value = null ) use ( $lowerBrowser ) {
            $search = (array)$search;
    
            foreach( $search as $val ) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if( $xkey !== false ) {
                    $value = $val;
                    $key   = $xkey;
    
                    return true;
                }
            }
    
            return false;
        };
    
        $findT = function ( array $search, &$key = null, &$value = null ) use ( $find ) {
            $value2 = null;
            if( $find(array_keys($search), $key, $value2) ) {
                $value = $search[$value2];
    
                return true;
            }
    
            return false;
        };
    
        $key = 0;
        $val = '';
        if( $findT(array( 'OPR' => 'Opera', 'UCBrowser' => 'UC Browser', 'YaBrowser' => 'Yandex', 'Iceweasel' => 'Firefox', 'Icecat' => 'Firefox', 'CriOS' => 'Chrome', 'Edg' => 'Edge' ), $key, $browser) ) {
            $version = $result['version'][$key];
        }elseif( $find('Playstation Vita', $key, $platform) ) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } elseif( $find(array( 'Kindle Fire', 'Silk' ), $key, $val) ) {
            $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if( !($version = $result['version'][$key]) || !is_numeric($version[0]) ) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif( $find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS' ) {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif( $find('Kindle', $key, $platform) ) {
            $browser = $result['browser'][$key];
            $version = $result['version'][$key];
        } elseif( $find('Opera', $key, $browser) ) {
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif( $find('Puffin', $key, $browser) ) {
            $version = $result['version'][$key];
            if( strlen($version) > 3 ) {
                $part = substr($version, -2);
                if( ctype_upper($part) ) {
                    $version = substr($version, 0, -2);
    
                    $flags = array( 'IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows' );
                    if( isset($flags[$part]) ) {
                        $platform = $flags[$part];
                    }
                }
            }
        } elseif( $find(array( 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome' ), $key, $browser) ) {
            $version = $result['version'][$key];
        } elseif( $rv_result && $find('Trident') ) {
            $browser = 'MSIE';
            $version = $rv_result;
        } elseif( $browser == 'AppleWebKit' ) {
            if( $platform == 'Android' ) {
                $browser = 'Android Browser';
            } elseif( strpos($platform, 'BB') === 0 ) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
                $browser = 'BlackBerry Browser';
            } else {
                $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
            }
    
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif( $pKey = preg_grep('/playstation \d/i', $result['browser']) ) {
            $pKey = reset($pKey);
    
            $platform = 'PlayStation ' . preg_replace('/\D/', '', $pKey);
            $browser  = 'NetFront';
        }
    
        return array( 'platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null );
    }


    /**
     * validate not private and reserved ip address
     * @return bool
     */
    static public function validateStrictIP($ip){
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        return true;
    }

    /**
     * validate any ip address
     * @return bool
     */
    static public function validateIP($ip){
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4) === false) {
            return false;
        }
        return true;
    }

    /**
     * validate any ip address
     * @return bool
     */
    static public function validateUrl($url){
        if( !preg_match('/^http/',$url) ){
            $url = "http://".$url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        return true;
    }
}