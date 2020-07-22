<?php

namespace System\Controller\TemplateEngine;

use System\Security\Crypt;

class AttributeWrapper{
    private $__name;
    private $__value;
    private $__raw;

    public function __construct($name,$val,$raw)
    {
        $this->__name = $name;
        $this->__value = $val;
        $this->__raw = $raw;
    }

    public function name(){
        return $this->__name;
    }

    public function val($set=null){
        if( $set !== null ){
            $this->__value = $set;
        }
        return $this->__value;
    }

    public function raw(){
        return $this->__raw;
    }
}

class Evaluator{
    protected $tree;
    protected $len;
    protected $ref;
    function __construct($tree,&$ref){
        $this->tree = $tree;
        $this->len = count($tree);
        $this->ref = &$ref;
    }

    public function eval($tree=null,$parent=""){
        $len = 0;
        if( !is_array($tree) ){
            $tree = $this->tree;
            $len = $this->len;
        }
        else{
            $len = count($tree);
        }

        $output = "";

        for($i=0;$i<$len;$i++){
            if( is_string($tree[$i]) ){
                $output .= $tree[$i];
            }
            else{
                switch($tree[$i]["type"]){
                    case "@":
                        $output .= $this->eval([$tree[$i]["value"]],"@");
                        break;
                    case "@!":
                        $output .= $this->eval([$tree[$i]["value"]],"@!");
                        break;
                    case "@@":
                        $output .= $this->eval([$tree[$i]["value"]],"@@");
                        break;
                    case "!":
                        $output .= $this->eval([$tree[$i]["value"]],"!");
                        break;
                    case "bracket":
                        $tempOut = $this->eval($tree[$i]["value"],"");
                        if( $parent == "@@" ){
                            $output .= "htmlentities({$tempOut})";
                        }
                        else if( $parent == "@!" ){
                            $output .= $tempOut;
                        }
                        else if( $parent == "@" ){
                            $output .= "<?php echo htmlentities({$tempOut}); ?>";
                        }
                        else if( $parent == "!" ){
                            $output .= "<?php {$tempOut}; ?>";
                        }
                        else{
                            $output .= "<?php echo {$tempOut}; ?>";
                        }
                        break;
                    case "inline":

                        $param = (($tree[$i]["param"] !== null)?$this->eval($tree[$i]["param"],""):"");
                        $cl = $tree[$i]["callback"];
                        if( !is_callable($cl) ){
                            $cl = RiverCompiler::getInlineFn($tree[$i]["name"]);
                        }

                        if( $parent == "@@" ){
                            $output .= call_user_func_array($cl,[&$this->ref,$param,1,$tree[$i]["name"]]);
                        }
                        else{
                            $output .= call_user_func_array($cl,[&$this->ref,$param,0,$tree[$i]["name"]]);
                        }
                        break;
                    case "block":
                        $param = (($tree[$i]["param"] !== null)?$this->eval($tree[$i]["param"],""):"");
                        $cl = $tree[$i]["callback"];
                        if( !is_callable($cl) ){
                            $cl = RiverCompiler::getBlockFn($tree[$i]["name"]);
                        }

                        if( $parent == "@@" ){
                            $output .= call_user_func_array($cl,[&$this->ref,$param,$this->eval($tree[$i]["content"],""),1,$tree[$i]["name"]]);
                        }
                        else{
                            $output .= call_user_func_array($cl,[&$this->ref,$param,$this->eval($tree[$i]["content"],""),0,$tree[$i]["name"]]);
                        }
                        break;
                    case "attribute":
                        $val = (($tree[$i]["value"] !== null)?$this->eval($tree[$i]["value"],""):"");

                        $cl = $tree[$i]["callback"];
                        if( !is_callable($cl) ){
                            $cl = RiverCompiler::getAttributeFn($tree[$i]["name"]);
                        }


                        $temp = call_user_func_array($cl,[&$this->ref,$val,&$tree[$i]["name"]]);
                        if( $tree[$i]["eval"] ){
                            $t = Crypt::eval("return ".$temp.";");
                            if( $t !== null ){
                                $temp = $t;
                            }
                        }
                        if($parent == "tag"){
                            if( is_string($output) ){
                                $output = [];
                            }
                            $raw = $tree[$i]["value"];
                            if( is_array($tree[$i]["value"]) ){
                                $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($tree[$i]["value"]));
                                $raw = "";
                                foreach($it as $v) {
                                    $raw .= $v;
                                }
                            }
                            $output[$tree[$i]["name"]] = new AttributeWrapper($tree[$i]["name"],$temp,$raw);
                        }
                        else{
                            $output .= $tree[$i]["name"]."=\"".str_replace("\"","\\'",addslashes($temp))."\"";
                        }
                        break;
                    case "inlineTag":
                        $cl = $tree[$i]["callback"];
                        if( !is_callable($cl) ){
                            $cl = RiverCompiler::getTagInlineFn($tree[$i]["name"]);
                        }

                        $attributes = (($tree[$i]["attributes"] !== null)?$this->eval($tree[$i]["attributes"],"tag"):"");
                        $output .= call_user_func_array($cl ,[&$this->ref,$attributes,$tree[$i]["name"]]);
                        break;
                    case "blockTag":
                        $cl = $tree[$i]["callback"];
                        if( !is_callable($cl) ){
                            $cl = RiverCompiler::getTagBlockFn($tree[$i]["name"]);
                        }

                        $attributes = (($tree[$i]["attributes"] !== null)?$this->eval($tree[$i]["attributes"],"tag"):"");
                        $content = (($tree[$i]["content"] !== null)?$this->eval($tree[$i]["content"],""):"");
                        $output .= call_user_func_array($cl ,[&$this->ref,$attributes,$content,$tree[$i]["name"]]);
                        break;
                }
            }
        }
        return $output;
    }
}