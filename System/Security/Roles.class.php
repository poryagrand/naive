<?php
namespace System\Security;

use System\Communicate\Debug\Console;

/**
 * small management class of role permition
 */
class Roles{
    private $__permsList = [];

    private $__nameStack = [];

    private static $allPerms = [];

    public static function setParams($params){
        if( !is_array($params) ){
            return false;
        }
        self::$allPerms = $params;
        return true;
    }

    public static function isParamEmpty(){
        return count(self::$allPerms) <= 0;
    }

    function __construct($perms) {
        $this->__permsList = self::getPermitions($perms);
    }

    /**
     * push permitions name to use in other functions
     * @param string $name
     */
    public function __get($name)
    {
        $this->__nameStack[] = strtolower($name);

        return $this;

    }

    /**
     * check if pushed names or inputs are in permitions list or not
     * @return bool
     */
    public function has($list=[]){
        if( is_array($list) && count($list) > 0 ){
            $intesected = array_intersect($this->__permsList,$list);
            if( count($list) == count($intesected) ){
                return true;
            }
            return false;
        }
        else{
            if( array_search( implode("_",$this->__nameStack) , $this->__permsList) === false ){
                $this->__nameStack = [];
                return false;
            }
            $this->__nameStack = [];
            return true;
        }
    }

    /**
     * check if pushed names or inputs are in permitions list or not
     * @return bool
     */
    public function contains($list=[]){
        if( is_array($list) && count($list) > 0 ){
            $intesected = array_intersect($this->__permsList,$list);
            if( count($intesected) > 0 && count($list) >= count($intesected) ){
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * return the position element id of permition
     * @return false|int
     */
    public function id($id=null){
        $fn = array_search( strtolower($id===null?implode("_",$this->__nameStack):$id), self::$allPerms);
        $this->__nameStack = [];
        if( $fn === false ){
            return false;
        }
        return $fn;
    }

    /**
     * return the position element id of permition
     * @return false|int
     */
    public function all(){
	    $splitRE   = '/\_/';
        $returnArr = array();
        $this->__nameStack = [];
	    foreach ($this->__permsList as $val) {
            $key = $val;

	    	$parts	= preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
	    	$leafPart = array_pop($parts);


            $parentArr = &$returnArr;
	    	foreach ($parts as $part) {
                if (!isset($parentArr[$part])) {
	    			$parentArr[$part] = array();
	    		}
	    		$parentArr = &$parentArr[$part];
	    	}

	    	if (empty($parentArr[$leafPart])) {
	    		$parentArr[$leafPart] = $val;
	    	}
	    }
	    return $returnArr;
    }

   /**
     * return the name of permition
     * @return false|string
     */
    public function name(){
        $im = implode("_",$this->__nameStack);
        $fn = array_search( $im , $this->__permsList);
        $this->__nameStack = [];
        if( $fn === false ){
            return false;
        }
        return $im;
    }

    /**
     * check if permitions exist in list or not and return or return all permitions
     * @param int|array|null $prm
     * @return array
     */
    public static function getPermitions($prm=null){
        if( $prm === null ){
            return self::$allPerms;
        }
        if( is_numeric($prm) ){
            if( isset(self::$allPerms[$prm]) ){
                return [self::$allPerms[$prm]];
            }
        }
        else if( is_array($prm) && count($prm) > 0 ){
            if( is_numeric($prm[0]) ){
                $newList = [];
                foreach( $prm as $item ){
                    if( isset( self::$allPerms[$item] ) ){
                        $newList[$item] = self::$allPerms[$item];
                    }
                }
                ksort($newList);
                return array_values($newList);
            }
            return array_intersect( self::$allPerms ,$prm);
        }
        return [];
    }
}