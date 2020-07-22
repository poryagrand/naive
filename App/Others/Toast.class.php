<?php
namespace App;

use System\Communicate\Message;

class Toast extends Message{

    protected static $sotrageName = "ToastStorage";
    protected static $Storage = array();

    public static $template = "jQuery(function(){new PNotify({
        title: \"{{title}}\",
        text: \"{{message}}\",
        addclass: \"{{type}}\",
        buttons: {
            sticker:{{sticky}},
            closer:{{closer}},
            closer_hover: {{closeHover}},
            sticker_hover: {{stickHover}}
        },
        delay:5000
    });});";

    public static function info($title,$messsage,$sticky=false,$closebtn=true,$closebtnOnHover=false,$stickbtnOnHover=false){
        self::add([
            "title"=>ucwords($title),
            "message"=>ucwords($messsage),
            "type"=>"stack-top-left bg-info border-info",
            "sticky"=>$sticky?"true":"false",
            "closer"=>$closebtn?"true":"false",
            "closeHover"=>$closebtnOnHover?"true":"false",
            "stickHover"=>$stickbtnOnHover?"true":"false"
        ]);
    }

    public static function warn($title,$messsage,$sticky=false,$closebtn=true,$closebtnOnHover=true,$stickbtnOnHover=false){
        self::add([
            "title"=>ucwords($title),
            "message"=>ucwords($messsage),
            "type"=>"stack-top-left bg-warning border-warning",
            "sticky"=>$sticky?"true":"false",
            "closer"=>$closebtn?"true":"false",
            "closeHover"=>$closebtnOnHover?"true":"false",
            "stickHover"=>$stickbtnOnHover?"true":"false"
        ]);
    }

    public static function error($title,$messsage,$sticky=false,$closebtn=true,$closebtnOnHover=true,$stickbtnOnHover=false){
        self::add([
            "title"=>ucwords($title),
            "message"=>ucwords($messsage),
            "type"=>"stack-top-left bg-danger border-danger",
            "sticky"=>$sticky?"true":"false",
            "closer"=>$closebtn?"true":"false",
            "closeHover"=>$closebtnOnHover?"true":"false",
            "stickHover"=>$stickbtnOnHover?"true":"false"
        ]);
    }

    public static function success($title,$messsage,$sticky=false,$closebtn=true,$closebtnOnHover=true,$stickbtnOnHover=false){
        self::add([
            "title"=>ucwords($title),
            "message"=>ucwords($messsage),
            "type"=>"stack-top-left bg-success border-success",
            "sticky"=>$sticky?"true":"false",
            "closer"=>$closebtn?"true":"false",
            "closeHover"=>$closebtnOnHover?"true":"false",
            "stickHover"=>$stickbtnOnHover?"true":"false"
        ]);
    }

    public static function primary($title,$messsage,$sticky=false,$closebtn=true,$closebtnOnHover=true,$stickbtnOnHover=false){
        self::add([
            "title"=>ucwords($title),
            "message"=>ucwords($messsage),
            "type"=>"stack-top-left bg-primary border-primary",
            "sticky"=>$sticky?"true":"false",
            "closer"=>$closebtn?"true":"false",
            "closeHover"=>$closebtnOnHover?"true":"false",
            "stickHover"=>$stickbtnOnHover?"true":"false"
        ]);
    }

}