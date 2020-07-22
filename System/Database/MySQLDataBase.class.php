<?php

namespace System\Database;

use System\Communicate\Debug\Console;

/**
 * connect to a mysql database via PDO
 */
class MySQL extends Gate{


    /**
     * @brief connect to a mysql database . each server can extend this class to connect to its server
     * 
     * @param string $host
     * @param string $dbname
     * @param string $username
     * @param string $password
     * @throws \Exception
     */
    function __construct($host , $dbname , $usernam , $password){
        if( !is_string($host) || !is_string($dbname) || !is_string($usernam) || !is_string($password) ){
            throw new \Exception("Arguments are not in correct format!");
        }
        parent::__construct(
            "mysql:host=$host",
            $usernam,
            $password,
            array(
                Gate::ATTR_TIMEOUT => 2, // in seconds
                Gate::ATTR_ERRMODE => Gate::ERRMODE_EXCEPTION
            )
        );

        $dbname = "`".str_replace("`","``",$dbname)."`";
        $this->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->query("use $dbname");
        $this->dbName = $dbname;
    }


    /**
     * creating table
     * @param string $table
     * @param array $details informat of \Base columns property
     * @param string $engine
     * @param string @charset
     * @return MySQL
     */
    public function create($table,$details,$engine="",$charset="",$otherAttributes=[],$columnDetails=[]){
        if( !is_string($table) || !is_array($details) || !is_string($engine) || !is_string($charset) || !is_array($otherAttributes) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $attributes = array();
        $primaries = array();

        if( count( $details) <= 0 ){
            return null;
        }

        foreach( $details as $key=>$val ){
            $temp = "`".$key."`";
            if( isset($val["type"]) ){
                $temp .= " ".$val["type"];
            }
            if( isset($val["charset"]) ){
                $temp .= " CHARACTER SET `".$val["charset"]."`";
            }
            if( isset($val["collate"]) ){
                $temp .= " COLLATE `".$val["collate"]."`";
            }
            if( !isset($val["isNull"]) || !$val["isNull"] ){
                $temp .= " NOT NULL";
            }
            if( isset($val["auto"]) && $val["auto"] ){
                $temp .= " AUTO_INCREMENT";
            }
            if( isset($val["default"]) ){
                $temp .= " DEFAULT ".(is_bool($val["default"])?( $val["default"] ? 1 : 0 ):("'".$val["default"]."'"));
            }
            if( isset($val["attributes"]) && !empty($val["attributes"]) ){
                $temp .= " ".implode(" ",$val["attributes"]);
            }
            if( isset($val["primary"]) && $val["primary"] ){
                $primaries[] = $key;
            }
            if( isset($val["unique"]) && $val["unique"] ){
                $temp .= " UNIQUE";
            }
            if( $temp != $key ){
                $attributes[] = $temp;
            }
        }

        $attributes = implode(",",$attributes);
        $primaries = ", PRIMARY KEY(".implode(",",$primaries).")";
        
        $attrs = "";
        foreach( $otherAttributes as $key=>$val ){
            $attrs .= strtoupper($key)."=".($val)." ";
        }

        if(count($columnDetails)>0){
            $columnDetails = ", " . implode(",",$columnDetails);
        }
        else{
            $columnDetails = "";
        }

        $query = "CREATE TABLE IF NOT EXISTS `$table`({$attributes}{$primaries}{$columnDetails})" . ( empty($engine)?"":" ENGINE=$engine" ) . ( empty($charset)?"":" DEFAULT CHARSET=$charset" ) . ( (empty($attrs))?"":" ".$attrs );

        return $this->query($query);
    } 

    /**
     * altering table data
     * @param string $table
     * @param array $columns list of columns name
     * @return MySQL
     */
    function alterDrop($table,$columns){
        if( !is_string($table) || !is_array($columns) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $query = "ALTER TABLE $table DROP ".(implode(", DROP ",$columns));

        return $this->query($query);
    }

    /**
     * altering table to add data
     * @param string $table
     * @param array $details informat of \Base columns property
     * @return MySQL
     */
    function alterAdd($table,$details){
        if( !is_string($table) || !is_array($details) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $attributes = array();
        $primKeys = array();

        foreach( $details as $key=>$val ){
            $temp = "`".$key."`";
            if( isset($val["type"]) ){
                $temp .= " ".$val["type"];
            }
            if( isset($val["isNull"]) && !$val["isNull"] ){
                $temp .= " NOT NULL";
            }
            if( isset($val["auto"]) && $val["auto"] ){
                $temp .= " AUTO_INCREMENT";
            }
            if( isset($val["default"]) ){
                $temp .= " DEFAULT '".$val["default"]."'";
            }
            if( isset($val["attributes"]) && !empty($val["attributes"]) ){
                $temp .= implode(" ",$val["attributes"]);
            }
            if( isset($val["primary"]) && $val["primary"] ){
                $primKeys[] = $key;
            }
            if( $temp != $key ){
                $attributes[] = $temp;
            }
        }

        $query = "ALTER TABLE $table ADD "
            .(implode(", ADD ",$attributes))
            . (empty($primKeys)?"":(count($attributes)>0?", ADD ":"")." PRIMARY KEY(".implode(",",$primKeys).")");

        return $this->query($query);
    }


    /**
     * altering table to modify data except primary keys
     * @param string $table
     * @param array $details informat of \Base columns property
     * @return MySQL
     */
    function alterModify($table,$details){
        if( !is_string($table) || !is_array($details) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $attributes = array();

        foreach( $details as $key=>$val ){
            $temp = "`".$key."`";
            if( isset($val["type"]) ){
                $temp .= " ".$val["type"];
            }
            if( !isset($val["isNull"]) || !$val["isNull"] ){
                $temp .= " NOT NULL";
            }
            if( isset($val["auto"]) && $val["auto"] ){
                $temp .= " AUTO_INCREMENT";
            }
            if( isset($val["default"]) ){
                $temp .= " DEFAULT '".$val["default"]."'";
            }
            if( isset($val["attributes"]) && !empty($val["attributes"]) ){
                $temp .= implode(" ",$val["attributes"]);
            }
            if( $temp != $key ){
                $attributes[] = $temp;
            }
        }

        $query = "ALTER TABLE $table MODIFY ".(implode(", MODIFY ",$attributes));
        return $this->query($query);
    }

    /**
     * altering table to change data except primary keys
     * @param string $table
     * @param array $details informat of \Base columns property
     * @return MySQL
     */
    function alterChnage($table,$details){
        if( !is_string($table) || !is_array($details) ){
            throw new \Exception("Arguments are not in correct format!");
        }

        $attributes = array();

        foreach( $details as $key=>$val ){
            $temp = "`".$key."`";
            if( isset($val["type"]) ){
                $temp .= " ".$val["type"];
            }
            if( isset($val["charset"]) ){
                $temp .= " CHARACTER SET ".$val["charset"];
            }
            if( isset($val["collate"]) ){
                $temp .= " COLLATE ".$val["collate"];
            }
            if( !isset($val["isNull"]) || !$val["isNull"] ){
                $temp .= " NOT NULL";
            }
            if( isset($val["auto"]) && $val["auto"] ){
                $temp .= " AUTO_INCREMENT";
            }
            if( isset($val["default"]) ){
                $temp .= " DEFAULT ".( is_string($val["default"]) ? "'".$val["default"]."'" : (is_bool($val["default"])?($val["default"]?1:0):$val["default"]) );
            }
            if( $temp != $key ){
                $attributes[] = "CHANGE `" . $key . "` " . $temp;
            }
        }

        $attributes = implode(",",$attributes);


        $query = "ALTER TABLE $table ".$attributes;

        return $this->query($query);
    }


}