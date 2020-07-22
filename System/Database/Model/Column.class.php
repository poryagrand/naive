<?php

namespace System\Database\Model;

use System\Communicate\Debug\Console;

/**
 * represents a column of a row
 * 
 * @property string $__name name of column
 * @property mixed $__value value of column
 * @property bool $__isSelfMofied checks column has been modified
 * @property bool $__ModifieRef reffrence to table modifie checker
 * 
 * @property string $type the type of column
 * @property bool $isNull
 * @property bool $auto is auto increment
 * @property mixed $default default value if can be null
 */
class Column{
    private $__name;
    private $__value;
    private $__isSelfMofied;
    private $__ModifieRef;
    private $__isInitiated;

    private $type;
    private $isNull;
    private $auto;
    private $default;

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $modRef
     * @param array $details
     * @throws \Exception
     */
    public function __construct($name,$value,&$modRef,$details)
    {
        if( !is_string($name) || !is_bool($modRef) || !is_array($details) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $this->__ModifieRef = &$modRef;
        $this->__isSelfMofied = false;
        $this->__name = $name;
        $this->__isInitiated = false;

        $this->type = $details["cast"];
        $this->isNull = $details["isNull"];
        $this->auto = $details["auto"];
        $this->default = $details["default"];

        $value = $this->castOrThrowError($value);
        if( $value !== Base::NoChange ){
            $this->__value = $value;
        }

        $this->__isInitiated = true;
    }

    /**
     * cast data if the type is mentioned and check the state of value not to be null or set default
     * 
     * @param mixed $val
     * @return mixed
     * @throws \Exception
     */
    private function castOrThrowError($val){
        $types = ["int","integer","bool","boolean","float","double","real","string","binary","array","object"];
        
        if( $val === null ){
            if( $this->isNull ){
                if( $this->default !== null ){
                    return $this->castOrThrowError($this->default);
                }
                else{
                    return null;
                }
            }
            else{
                throw new \Exception("the column ". ($this->__name) . " is not permitted to has null value!");
            }
        }

        if( gettype($this->type) == "string" && array_search( strtolower($this->type) , $types ) !== false ){
            settype($val,strtolower($this->type));
            return $val;
        }
        else if( Base::hasFilter($this->type) ){
            try{
                return Base::callFilter($this->type,[$val,false,false,true,$this->__isSelfMofied,!$this->__isInitiated]);
            }
            catch(\Exception $e){
                throw new \Exception("the column ". ($this->__name) . " hass casting Error: ". $e->getMessage());
            }
        }
        else if( is_callable($this->type) ){
            try{
                return call_user_func($this->type,$val,false,false,true,$this->__isSelfMofied,!$this->__isInitiated);
            }
            catch(\Exception $e){
                throw new \Exception("the column ". ($this->__name) . " hass casting Error: ". $e->getMessage());
            }
        }
        return $val;
    }

    /**
     * set value to column
     * @param mixed $val
     * @return void
     */
    public function set($val,$raw=false){
        if( $this->auto && $this->__value !== null ){
            return;
        }
        
        if(!$raw){
            $value = $this->castOrThrowError($val);
        }
        else{
            $value = (string)$val;
        }
        
        if( $value !== Base::NoChange ){
            $this->__value = $value;
        }
        $this->__isSelfMofied = true;
        $this->__ModifieRef = true;
    }

    /**
     * set value to column event the auto increments
     * @param mixed $val
     * @return void
     */
    public function forceSet($val,$raw=false){
        if(!$raw){
            $value = $this->castOrThrowError($val);
        }
        else{
            $value = (string)$val;
        }
        if( $value !== Base::NoChange ){
            $this->__value = $value;
        }
        $this->__isSelfMofied = true;
        $this->__ModifieRef = true;
    }

    public function initSet($value,$raw=false){
        $this->__isSelfMofied = false;
        $this->__isInitiated = false;
        if(!$raw){
            $value = $this->castOrThrowError($value);
        }
        else{
            $value = (string)$value;
        }
        if( $value !== Base::NoChange ){
            $this->__value = $value;
        }
        $this->__isInitiated = true;
    }

    /**
     * retrieve the value of column
     * @return mixed
     */
    public function get(){
        if( Base::hasFilter($this->type) ){
            try{
                return Base::callFilter($this->type,[$this->__value,false,true,false,$this->__isSelfMofied,!$this->__isInitiated]);
            }
            catch(\Exception $e){
                throw new \Exception("the column ". ($this->__name) . " hass casting Error: ". $e->getMessage());
            }
        }
        else if( is_callable($this->type) ){
            try{
                return call_user_func($this->type,$this->__value,false,true,false,$this->__isSelfMofied,!$this->__isInitiated);
            }
            catch(\Exception $e){
                throw new \Exception("the column ". ($this->__name) . " hass casting Error: ". $e->getMessage());
            }
        }
        return $this->__value;
    }

    /**
     * get the value to save in data base . if there is a cast , it will cast it back
     * @return string
     */
    public function getToUpdate(){
        $types = ["int","integer","bool","boolean","float","double","real","string","binary","array","object"];
        if( gettype($this->type) == "string" && array_search( strtolower($this->type) , $types ) !== false ){
            return $this->__value;
        }
        else if( Base::hasFilter($this->type) ){
            try{
                return Base::callFilter($this->type,[$this->__value,true,false,false,$this->__isSelfMofied,!$this->__isInitiated]);
            }
            catch(\Exception $e){
                throw new \Exception("the column ". ($this->__name) . " hass casting Error: ". $e->getMessage());
            }
        }
        else if( is_callable($this->type) ){
            try{
                return call_user_func($this->type,$this->__value,true,false,false,$this->__isSelfMofied,!$this->__isInitiated);
            }
            catch(\Exception $e){
                throw new \Exception("the column ". ($this->__name) . " hass casting Error: ". $e->getMessage());
            }
        }
        $this->__isSelfMofied = false;
        return $this->__value;
    }

    public function getRaw(){
        return $this->__value;
    }

    /**
     * retrieve the title of the column
     * @return string
     */
    public function name(){
        return $this->__name;
    }

    /**
     * check if the column has modified or not
     * @return bool
     */
    public function isModified(){
        return $this->__isSelfMofied;
    }
}
