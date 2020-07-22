<?php
namespace System\Communicate;
/**
 * data structure of cookie
 * @property string $__name
 * @property mixed $__value
 * @property bool $__isLock
 * @property string $__expireAt
 * @property string $__instance
 * @property string $__lastUpdate
 */
class CookieData{

    private $__name;
    private $__value;
    private $__isLock;
    private $__createAt;
    private $__expireAt;
    private $__instance;
    private $__lastUpdate;

    /**
     * @param string $name
     * @param int $exp
     */
    public function __construct($name,$exp)
    {

        if( gettype($exp) == "integer" ){
            $exp2 = new \DateTime();
            $exp2->add(new \DateInterval("P".($exp)."D"));
            $exp = $exp2->format("Y-m-d H:i:s");
        }

        $this->__name = $name;
        $this->__value = null;
        $this->__instance = null;
        $this->__isLock = false;

        $this->__createAt = date("Y-m-d H:i:s");
        $this->__expireAt = $exp;
        $this->__lastUpdate = date("Y-m-d H:i:s");

    }


    /**
     * export cookie data object to store in cookie
     * @return array
     * @throws CookieHandleException
     */
    public function export(){
        $obj = array();
        $obj["name"] = $this->__name;
        $obj["isLock"] = $this->__isLock;
        $obj["createAt"] = $this->__createAt;
        $obj["expireAt"] = $this->__expireAt;
        $obj["instance"] = $this->__instance;
        $obj["lastUpdate"] = $this->__lastUpdate;

        try{
            $obj["value"] = serialize($this->__value);
        }
        catch(CookieHandleException $e){
            throw $e;
        }

        return $obj;
    }

    /**
     * import serialized cookies into object
     * @param array $obj
     * @throws CookieHandleException
     */
    public function import($obj){
        if( 
            gettype($obj) == "array" &&
            isset( $obj["name"] ) &&
            isset( $obj["value"] ) &&
            isset( $obj["isLock"] ) &&
            isset( $obj["createAt"] ) &&
            isset( $obj["expireAt"] ) &&
            isset( $obj["instance"] ) &&
            isset( $obj["lastUpdate"] )
        ){
            $this->__name = $obj["name"];
            $this->__isLock = $obj["isLock"];
            $this->__createAt = $obj["createAt"];
            $this->__expireAt = $obj["expireAt"];
            $this->__instance = $obj["instance"];
            $this->__lastUpdate = $obj["lastUpdate"];

            if( $obj["instance"] !== null ){
                try{
                    $this->__value = unserialize($obj["value"]);
                }
                catch(CookieHandleException $e){
                    throw $e;
                }
            }
            else{
                $this->__value = $obj["value"];
            }
        }
    }

    public function __set($name, $value)
    {
        switch($name){
            case "value":
                if( !$this->__isLock ){
                    $type = gettype($value);
                    $this->__value = $value;
                    $this->__instance = $type == "object"?get_class($value):$type;
                    Cookie::saveOnAuto();
                }
                break;
            case "isLock":
                $this->__isLock = !(!$value);
                Cookie::saveOnAuto();
                break;
            case "expire":
                try{
                    $exp = null;
                    if( gettype($value) == "int" ){ // add or 
                        $exp = new \DateTime($this->__expireAt);
                        $exp->add(new \DateInterval("P".(abs($value))."D"));
                        $exp = $exp->format("Y-m-d H:i:s");
                    }
                    else if( gettype($value) == "string" ){
                        $exp = (new \DateTime($value))->format("Y-m-d H:i:s");
                    }
                    else if( $value instanceof \DateTime ){
                        $exp = $value->format("Y-m-d H:i:s");
                    }
                    if( $exp !== null ){
                        $this->__expireAt = $exp;
                    }
                    Cookie::saveOnAuto();
                }
                catch(CookieHandleException $e){}
                
                break;
            default:
                throw new CookieHandleException("the property $name is not exist!");
        }
        return;
    }

    public function __get($name)
    {
        switch($name){
            case "value":
                return $this->__value;
            case "name":
                return $this->__name;
            case "isLock":
                return !(!$this->__isLock);
            case "lastUpdate":
                return new \DateTime($this->__lastUpdate);
            case "expireAt":
                return new \DateTime($this->__expireAt);
            case "isExpired":
                return  strtotime($this->__expireAt) - strtotime(date("Y-m-d H:i:s")) <= 0;
            case "createAt":
                return new \DateTime($this->__createAt);
            case "instance":
                return ($this->__instance);
        }
        throw new CookieHandleException("the property $name is not exist!");
    }
}
