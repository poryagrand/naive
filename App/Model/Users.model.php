<?php

use System\Database\DB;
use System\Database\Model\Base;
use System\Database\Model\TableStructure;
use System\Security\Crypt;

class UsersModel{
    use TableStructure;

    private $id_;
    private $userName_;
    private $password_;

    public function hash(){
        return Crypt::hash($this->username . $this->id);
    }
}


class UsersTable extends Base{
    static protected $tableName = "Users";
    static protected $instance = UsersModel::class;
    static protected $server = DB::class;
    static protected $engine = "innoDB";
    static protected $charset = "latin1";
    static protected $attributes = [
        "ROW_FORMAT"=>"COMPRESSED",
        "COLLATE"=>"latin1_general_ci"
    ];
    static protected $columns = [
        "id"=>[
            "attributes"=>[],
            "type"=>"BIGINT unsigned",
            "cast"=>"db.bigint",
            "isNull"=>false,
            "auto"=>true,
            "default"=>null,
            "primary"=>true,
            "unique"=>false
        ],
        "userName"=>[
            "attributes"=>[],
            "type"=>"varchar(20)",
            "cast"=>"db.username",
            "isNull"=>true,
            "auto"=>false,
            "default"=>"",
            "primary"=>false,
            "unique"=>true
        ],
        "passWord"=>[
            "attributes"=>[],
            "type"=>"varchar(70)",
            "cast"=>"db.password",
            "isNull"=>true,
            "auto"=>false,
            "default"=>"",
            "primary"=>false
        ],
    ];
}