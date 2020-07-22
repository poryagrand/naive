<?php
namespace System\Controller;

use System\Security\Crypt;
use System\Communicate\Debug\Console;
use System\Security\Utilities;


class ErrorResponseOfRoute{
    public $code;
    public $message;
    function __construct($text,$code)
    {
        $this->code = $code;
        $this->message = $text;
    }
}

class Response{
    protected $__method;
    protected $__route;
    protected $__river;
    protected $__headers;
    protected $__content;

    const CONTINUE = [
        "code"=>100,
        "name"=>"Continue"
    ];
    const SWITCHING_PROTOCOLS = [
        "code"=>101,
        "name"=>"Switching Protocols"
    ];
    const PROCESSING = [
        "code"=>102,
        "name"=>"Processing"
    ];
    const EARLY_HINTS = [
        "code"=>103,
        "name"=>"Early Hints"
    ];
    const OK = [
        "code"=>200,
        "name"=>"OK"
    ];
    const CREATED = [
        "code"=>201,
        "name"=>"Created"
    ];
    const ACCEPTED = [
        "code"=>202,
        "name"=>"Accepted"
    ];
    const NON_AUTHORITATIVE_INFORMATION = [
        "code"=>203,
        "name"=>"Non-Authoritative Information"
    ];
    const NO_CONTENT = [
        "code"=>204,
        "name"=>"No Content"
    ];
    const RESET_CONTENT = [
        "code"=>205,
        "name"=>"Reset Content"
    ];
    const PARTIAL_CONTENT = [
        "code"=>206,
        "name"=>"Partial Content"
    ];
    const MULTI_STATUS = [
        "code"=>207,
        "name"=>"Multi-Status"
    ];
    const ALREADY_REPORTED = [
        "code"=>208,
        "name"=>"Already Reported"
    ];
    const IM_USED = [
        "code"=>226,
        "name"=>"IM Used"
    ];
    const MULTIPLE_CHOICES = [
        "code"=>300,
        "name"=>"Multiple Choices"
    ];
    const MOVED_PERMANENTLY = [
        "code"=>301,
        "name"=>"Moved Permanently"
    ];
    const FOUND = [
        "code"=>302,
        "name"=>"Found"
    ];
    const SEE_OTHER = [
        "code"=>303,
        "name"=>"See Other"
    ];
    const NOT_MODIFIED = [
        "code"=>304,
        "name"=>"Not Modified"
    ];
    const USE_PROXY = [
        "code"=>305,
        "name"=>"Use Proxy"
    ];
    const SWITCH_PROXY = [
        "code"=>306,
        "name"=>"Switch Proxy"
    ];
    const TEMPORARY_REDIRECT = [
        "code"=>307,
        "name"=>"Temporary Redirect"
    ];
    const PERMANENT_REDIRECT = [
        "code"=>308,
        "name"=>"Permanent Redirect"
    ];
    const BAD_REQUEST = [
        "code"=>400,
        "name"=>"Bad Request"
    ];
    const UNAUTHORIZED = [
        "code"=>401,
        "name"=>"Unauthorized"
    ];
    const PAYMENT_REQUIRED = [
        "code"=>402,
        "name"=>"Payment Required"
    ];
    const FORBIDDEN = [
        "code"=>403,
        "name"=>"Forbidden"
    ];
    const NOT_FOUND = [
        "code"=>404,
        "name"=>"Not Found"
    ];
    const METHOD_NOT_ALLOWED = [
        "code"=>405,
        "name"=>"Method Not Allowed"
    ];
    const NOT_ACCEPTABLE = [
        "code"=>406,
        "name"=>"Not Acceptable"
    ];
    const PROXY_AUTHENTICATION_REQUIRED = [
        "code"=>407,
        "name"=>"Proxy Authentication Required"
    ];
    const REQUEST_TIMEOUT = [
        "code"=>408,
        "name"=>"Request Timeout"
    ];
    const CONFLICT = [
        "code"=>409,
        "name"=>"Conflict"
    ];
    const GONE = [
        "code"=>410,
        "name"=>"Gone"
    ];
    const LENGTH_REQUIRED = [
        "code"=>411,
        "name"=>"Length Required"
    ];
    const PRECONDITION_FAILED = [
        "code"=>412,
        "name"=>"Precondition Failed"
    ];
    const PAYLOAD_TOO_LARGE = [
        "code"=>413,
        "name"=>"Payload Too Large"
    ];
    const URI_TOO_LONG = [
        "code"=>414,
        "name"=>"URI Too Long"
    ];
    const UNSUPPORTED_MEDIA_TYPE = [
        "code"=>415,
        "name"=>"Unsupported Media Type"
    ];
    const REQUESTED_RANGE_NOT_SATISFIABLE = [
        "code"=>416,
        "name"=>"Requested Range Not Satisfiable"
    ];
    const EXPECTATION_FAILED = [
        "code"=>417,
        "name"=>"Expectation Failed"
    ];
    const IM_A_TEAPOT = [
        "code"=>418,
        "name"=>"I'm a teapot"
    ];
    const PAGE_EXPIRED = [
        "code"=>419,
        "name"=>"Page Expired"
    ];
    const METHOD_FAILURE = [
        "code"=>420,
        "name"=>"Method Failure"
    ];
    const MISDIRECTED_REQUEST = [
        "code"=>421,
        "name"=>"Misdirected Request"
    ];
    const UNPROCESSABLE_ENTITY = [
        "code"=>422,
        "name"=>"Unprocessable Entity"
    ];
    const LOCKED = [
        "code"=>423,
        "name"=>"Locked"
    ];
    const FAILED_DEPENDENCY = [
        "code"=>424,
        "name"=>"Failed Dependency"
    ];
    const UPGRADE_REQUIRED = [
        "code"=>426,
        "name"=>"Upgrade Required"
    ];
    const PRECONDITION_REQUIRED = [
        "code"=>428,
        "name"=>"Precondition Required"
    ];
    const TOO_MANY_REQUESTS = [
        "code"=>429,
        "name"=>"Too Many Requests"
    ];
    const REQUEST_HEADER_FIELDS_TOO_LARGE = [
        "code"=>431,
        "name"=>"Request Header Fields Too Large"
    ];
    const UNAVAILABLE_FOR_LEGAL_REASONS = [
        "code"=>451,
        "name"=>"Unavailable For Legal Reasons"
    ];
    const INVALID_TOKEN = [
        "code"=>491,
        "name"=>"Invalid Token"
    ];
    const TOKEN_EXPIRED = [
        "code"=>498,
        "name"=>"Token Expired"
    ];
    const TOKEN_REQUIRED = [
        "code"=>499,
        "name"=>"Token Required"
    ];
    const INTERNAL_SERVER_ERROR = [
        "code"=>500,
        "name"=>"Internal Server Error"
    ];
    const NOT_IMPLEMENTED = [
        "code"=>501,
        "name"=>"Not Implemented"
    ];
    const BAD_GATEWAY = [
        "code"=>502,
        "name"=>"Bad Gateway"
    ];
    const SERVICE_UNAVAILABLE = [
        "code"=>503,
        "name"=>"Service Unavailable"
    ];
    const GATEWAY_TIMEOUT = [
        "code"=>504,
        "name"=>"Gateway Timeout"
    ];


    /**
     * @param string $method
     * @param RouteRegister $route
     * @param River $route
     */
    function __construct($method,$route,&$river)
    {
        if( !is_string($method) || !($route instanceof RouteRegister) ){
            throw new RouteHandleException("Response arguments are not correct");
        }

        $this->__method = $method;
        $this->__route = $route; 
        $this->__river = &$river;  
        $this->__headers = array();
        $this->__content = "";
    }


    /**
     * return the output content to print
     * @return string
     */
    public function getContent(){
        return $this->__content;
    }

    /**
     * print the output content to print
     * @return string
     */
    public function printContent(){
        if( is_array($this->__content) ){
            $ref = &$this->__content;
            if( isset($ref["file"]) ){
                readfile($ref["file"]);
            }
        }
        else{
            echo $this->__content;
        }
    }

    /**
     * set header data
     * @param string $key
     * @param string $value
     * @return Response
     */
    public function header($key,$value){
        if( !is_string($key) || (!is_string($value)&&!is_numeric($value)) ){
            return new RouteHandleException("header arguments are not correct");
        }
        $this->__headers[] = $key.":".$value;
        return $this;
    }

    /**
     * check if a header has been setted or not
     * @param string $name
     * @return bool
     */
    public function hasHeader($key){
        $ref = &$this->__headers;
        return isset($ref[$key]);
    }

    /**
     * set headers 
     * @return Response
     */
    protected function setHeaders(){
        foreach( $this->__headers as $header ){
            try{
                header($header);
            }
            catch(RouteHandleException $e){throw new RouteHandleException("there is an error in header ($header)");}
        }
        return $this;
    }

    /**
     * @brief set http code in header
     * @param[in] string|number $code
     * @return bool
     */
    public function setHeaderHttpCode($code){
        if( !is_array($code) || !isset($code["code"]) || !isset($code["name"]) ){
            return false;
        }
        //Console::log($_SERVER['SERVER_PROTOCOL'] . ' '.$code["code"].' '.$code["name"], true, (int)$code["code"]);
        //Console::flush();
        header($_SERVER['SERVER_PROTOCOL'] . ' '.$code["code"].' '.$code["name"], true, (int)$code["code"]);
        return true;
    }

    /**
     * add shared data to view
     * @param string $name
     * @param mixed $value
     * @return Response
     */
    public function share($name=null,$value=null){
        $this->__river->share($name,$value);
        return $this;
    }

    /**
     * remove shared data to view
     * @param string $name
     * @param mixed $value
     * @return Response
     */
    public function removeShare($name){
        $this->__river->removeShare($name);
        return $this;
    }

    /**
     * add shared data to view as reffrence
     * @param string $name
     * @param mixed $value
     * @return Response
     */
    public function getShare($name=null){
        return $this->__river->Share($name);
    }

    /**
     * show page as pdf page
     * @param string $filename
     * @return Response
     * @throws RouteHandleException
     */
    public function pdf($filename=null){

        if($filename instanceof Response ) return$filename;

        if( $filename === null || !is_string($filename) ){
            $filename = $this->__route->getViewPath();
        }
        else{
            $filename = Route::path($filename);
        }

        if( empty($filename) || !file_exists($filename) ){
            throw new RouteHandleException("view file is not found");
        }

        $this->setHeaderHttpCode(self::OK);

        //if( !$this->hasHeader("Content-Type") ){
        //    $this->header("Content-Type","text/html; charset=utf-8");
        //}
        
        $this->setHeaders();
        
        $content = $this->__river->render($filename);

        Utilities::convertToPDF($content);

        $this->__content = $content;
        
        return $this;
    }

    /**
     * add or get shared data to view
     * @param string $filename
     * @return Response
     * @throws RouteHandleException
     */
    public function view($filename=null){
        if( $filename instanceof Response ) return $filename;

        if( $filename === null || !is_string($filename) ){
            $filename = $this->__route->getViewPath();
        }
        else{
            $filename = Route::path($filename);
        }

        if( empty($filename) || !file_exists($filename) ){
            throw new RouteHandleException("view file is not found");
        }

        $this->setHeaderHttpCode(self::OK);

        if( !$this->hasHeader("Content-Type") ){
            $this->header("Content-Type","text/html; charset=utf-8");
        }
        
        $this->setHeaders();
        
        $this->__content = $this->__river->render($filename);
        
        return $this;
    }

    public function error($message,$code){
        return new ErrorResponseOfRoute($message,$code);
    }

    /**
     * add or get shared data to view
     * @param string $filename
     * @return Response
     * @throws RouteHandleException
     */
    public function raw($content,$code=null){

        if( $content instanceof Response ) return $content;

        if( $content instanceof ErrorResponseOfRoute ){
            $code = $content->code;
            $content = $content->message;
        }

        if( !is_array($code) || !isset($code["code"]) || !isset($code["name"]) ){
            $code = self::OK;
        }

        $this->setHeaderHttpCode($code);

        $this->setHeaders();

        if( !is_string($content) ){
            $content = json_encode($content);
        }

        $this->__content = $content;
        return $this;
    }

    public function file($filepath,$code=null){

        if( $filepath instanceof Response ) return $filepath;

        if( $filepath instanceof ErrorResponseOfRoute ){
            $code = $filepath->code;
            $filepath = $filepath->message;
        }
        
        if( !is_array($code) || !isset($code["code"]) || !isset($code["name"]) ){
            $code = self::OK;
        }

        if( !file_exists($filepath) ){
            $this->__content = ""; 
            
        }
        else{
            $this->header("Content-Length",filesize($filepath));
            if( !$this->hasHeader("Content-Type") ){
                $this->header("Content-Type",mime_content_type($filepath));
            }
            $this->header("Content-Transfer-Encoding","Binary"); 
            $this->header("Content-disposition","attachment; filename=\"" . basename($filepath) . "\""); 
        }

        $this->setHeaderHttpCode($code);

        $this->setHeaders();

        $this->__content = [
            "file"=>$filepath
        ];//readfile($content);
        return $this;
    }

    /**
     * add or get shared data to view
     * @param string $filename
     * @return Response
     * @throws RouteHandleException
     */
    public function json($content,$code=null,$others=array()){

        if( $content instanceof Response ) return $content;

        if( $content instanceof ErrorResponseOfRoute ){
            $code = $content->code;
            $content = $content->message;
        }
        
        if( !is_array($code) || !isset($code["code"]) || !isset($code["name"]) ){
            $code = self::OK;
        }

        $this->setHeaderHttpCode($code);

        $this->setHeaders();

        header('Content-Type: application/json');
        
        
        $output = array(
            "code"=>(int)$code["code"],
            "name"=>$code["name"]
        );

        $output["message"]=$content;
        $output["timestamp"] = Crypt::currentTime();

        if( is_array($others) ){
            $output = $output + $others;
        }

        $this->__content = json_encode($output);

        return $this;
    }


    
    /**
     * add or get shared data to view
     * @param string $filename
     * @return Response
     * @throws RouteHandleException
     */
    public function xml($content,$code=null,$others=array()){

        if( $content instanceof Response ) return $content;

        if( $content instanceof RouteHandleException ){
            $code = $content->code;
            $content = $content->message;
        }

        if( !is_array($code) || !isset($code["code"]) || !isset($code["name"]) ){
            $code = self::OK;
        }

        $this->setHeaderHttpCode($code);

        $this->setHeaders();

        header('Content-Type: application/xml');


        $xml = new \SimpleXMLElement('<Response/>');
            $name = $xml->addChild("Name",$code["name"]);
                $name->addAttribute("Code",(int)$code["code"]);

                if( gettype($content) == "array" ){
                    $message = $xml->addChild("Message");
                    $message->addAttribute("Raw",0);
                        $message = $message->addChild("List");
                        self::recursiveXmlTreeMaker($content,$message);
                }
                else{
                    $message = $xml->addChild("Message",$content);
                    $message->addAttribute("Raw",1);
                }
                
        if( !empty($others) ){
            $xml->addChild("TimeStamp",Crypt::currentTime());
            $extend = $xml->addChild("Extend");
            self::recursiveXmlTreeMaker($others,$extend,null);
        }

        $this->__content = $xml->asXML();

        return $this;
    }


    protected static function recursiveXmlTreeMaker($array,&$parent,$elmName="Item"){
        foreach( $array as $key=>$val ){
            if( $elmName === null ){
                $item = $parent->addChild($key);
            }
            else{
                $item = $parent->addChild("Item");
                $item->addAttribute("key",$key);
            }

            if( gettype($val) == "array" ){
                $item->addAttribute("Raw",0);
                self::recursiveXmlTreeMaker($val,$item->addChild("List"));
            }
            else{
                $item->addAttribute("Raw","1");
                $item[0] = $val;
            }
        }
    }



}