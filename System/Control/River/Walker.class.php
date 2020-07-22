<?php

namespace System\Controller\TemplateEngine;
class Walker{

    protected $__inlineStorage = array();
    protected $__blockStorage = array();

    protected $__tagBlockStorage = array();
    protected $__tagInlineStorage = array();

    protected $__attributesStorage = array();

    protected $__preDefines = [];

    protected $textAsArray;
    protected $len;

    function __construct($content){
        $this->textAsArray = str_split($content);
        $this->len = count($this->textAsArray);
    }

    public function attachPreDefines($dir){
        if( !is_array($dir) ){
            return;
        }
        $this->__preDefines = $dir;
    }

    public function attachAttribute($attr){
        if( !is_array($attr) ){
            return;
        }
        $this->__attributesStorage = $attr;
    }


    public function attachInlineDirective($directives){
        if( !is_array($directives) ){
            return;
        }
        $this->__inlineStorage = $directives;
    }

    public function attachBlockDirective($directives){
        if( !is_array($directives) ){
            return;
        }
        $this->__blockStorage = $directives;
    }

    public function attachInlineTag($tags){
        if( !is_array($tags) ){
            return;
        }
        $this->__tagInlineStorage = $tags;
    }

    public function attachBlockTag($tags){
        if( !is_array($tags) ){
            return;
        }
        $this->__tagBlockStorage = $tags;
    }

    protected function isBlockTag( $name ){
        return RiverCompiler::isTagBlock($name);
        //$block = &$this->__tagBlockStorage;
        //return isset( $block[$name] );
    }

    protected function isInlineTag( $name ){
        return RiverCompiler::isTagInline($name);
        //$inline = &$this->__tagInlineStorage;
        //return isset( $inline[$name] );
    }

    protected function isBlockDirective( $name ){
        return RiverCompiler::isBlock($name);
        //$block = &$this->__blockStorage;
        //return isset( $block[$name] );
    }

    protected function isInlineDirective( $name ){
        return RiverCompiler::isInline($name);
        //$inline = &$this->__inlineStorage;
        //return isset( $inline[$name] );
    }

    protected function isAttribute($name){
        return RiverCompiler::isAttribute($name);
        //$inline = &$this->__attributesStorage;
        //return isset( $inline[$name] );
    }

    protected function get($pos){
        if( $pos < $this->len ){
            return $this->textAsArray[$pos];
        }
        return null;
    }

    protected function getRange($pos,$len){
        if( $pos+$len <= $this->len ){
            $sliced = array_slice($this->textAsArray,$pos,$len);
            return implode("",$sliced);
        }
        return null;
    }

    public function parse(){
        $text = "";
        $output = [];

        for($i=0;$i<$this->len && $i >= 0;$i++){
            switch( $this->get($i) ){
                case "s":
                    if( $this->getRange($i,7) == "server:" && $this->get($i-1) != "\\" ){
                        if(!empty($text)){
                            if( isset($output[count($output)-1]) && is_string($output[count($output)-1]) ){
                                $output[count($output)-1] = $output[count($output)-1] . $text;
                            }
                            else{
                                array_push($output,$text);
                            }
                            $text = "";
                        }
                        $temp = call_user_func_array(array($this,"tagAttributesParser"),array(&$i));

                        if( is_array($temp) ){
                            if(count($temp)>0){
                                $temp = $temp[0];
                                array_push($output,$temp);
                            }
                            $i--;
                        }
                        else if( is_string($temp) ){
                            $text .= $this->get($i) . $temp;
                        }
                        else{
                            $text.=$this->get($i);
                        }
                    }
                    else{
                        $text.=$this->get($i);
                    }
                    break;
                case "<":
                    if( $this->getRange($i,8) == "<server:" ){
                        if(!empty($text)){
                            if( isset($output[count($output)-1]) && is_string($output[count($output)-1]) ){
                                $output[count($output)-1] = $output[count($output)-1] . $text;
                            }
                            else{
                                array_push($output,$text);
                            }
                            $text = "";
                        }
                        $temp = call_user_func_array(array($this,"tagParser"),array(&$i));

                        if( is_array($temp) ){
                            array_push($output,$temp);
                        }
                        else if( is_string($temp) ){
                            $text .= $this->get($i) . $temp;
                        }
                        else{
                            $text.=$this->get($i);
                            $i++;
                            $text.=$this->get($i);
                        }
                    }
                    else{
                        $text.=$this->get($i);
                    }
                    break;
                case "@":
                    if(!empty($text)){
                        if( isset($output[count($output)-1]) && is_string($output[count($output)-1]) ){
                            $output[count($output)-1] = $output[count($output)-1] . $text;
                        }
                        else{
                            array_push($output,$text);
                        }
                        $text = "";
                    }
                    $temp = call_user_func_array(array($this,"atSignParser"),array(&$i));

                    if( is_array($temp) ){
                        array_push($output,$temp);
                    }
                    else if( is_string($temp) ){
                        $text .= $this->get($i) . $temp;
                    }
                    else{
                        $text.=$this->get($i);
                    }
                    break;
                case "!":
                    if(!empty($text)){
                        if( isset($output[count($output)-1]) && is_string($output[count($output)-1]) ){
                            $output[count($output)-1] = $output[count($output)-1] . $text;
                        }
                        else{
                            array_push($output,$text);
                        }
                        $text = "";
                    }
                    $temp = call_user_func_array(array($this,"exMarkParser"),array(&$i));
                    if( is_array($temp) ){
                        array_push($output,$temp);
                    }
                    else if( is_string($temp) ){
                        $text .= $this->get($i) . $temp;
                    }
                    else{
                        $text.=$this->get($i);
                    }
                    break;
                case "{":
                    if(!empty($text)){
                        if( isset($output[count($output)-1]) && is_string($output[count($output)-1]) ){
                            $output[count($output)-1] = $output[count($output)-1] . $text;
                        }
                        else{
                            array_push($output,$text);
                        }
                        $text = "";
                    }
                    $temp = call_user_func_array(array($this,"bracketParser"),array(&$i));
                    if( is_array($temp) ){
                        array_push($output,$temp);
                    }
                    else if( is_string($temp) ){
                        $text .= $this->get($i) . $temp;
                    }
                    else{
                        $text.=$this->get($i);
                    }
                    break;
                default:  $text.=$this->get($i);
            }
        }

        if(!empty($text)){
            if( isset($output[count($output)-1]) && is_string($output[count($output)-1]) ){
                $output[count($output)-1] = $output[count($output)-1] . $text;
            }
            else{
                array_push($output,$text);
            }
            $text = "";
        }
        return $output;
    }

    protected function tagParser( &$pos ){
        $pos+=8;
        $output = null;
        $tagName = "";
        for($i=$pos*1;$i<$this->len;$i++){
            $ord = ord( $this->get($i) );
            if( 
                ( $ord >= ord('a') && $ord <= ord('z') ) ||
                ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                ( $ord >= ord('0') && $ord <= ord('9') ) || 
                ( $ord == ord(':') )                     ||
                ( $ord == ord('_') )
             ){
                 $tagName .= $this->get($i);
            }
            else{
                break;
            }
        }

        if( $this->isInlineTag($tagName) ){
            $output = call_user_func_array(array($this,"inlineTagParser"),array(&$i,$tagName));
            if($output !== null){
                $pos=$i;
                return $output;
            }
        }
        else if( $this->isBlockTag($tagName) ){
            $output = call_user_func_array(array($this,"blockTagParser"),array(&$i,$tagName));
            if($output !== null){
                $pos=$i;
                return $output;
            }
        }
        else if( !empty($tagName) && preg_match('/^[A-Za-z0-9\_\-\.]+$/',$tagName) ){
            $prdfns = RiverCompiler::getPreDefined();
            foreach( $prdfns as $key=>$val ){
                if( preg_match($key,$tagName) ){
                    if( 
                        $val == "tag"
                    ){
                        $output = call_user_func_array(array($this,"inlineTagParser"),array(&$i,$tagName));
                        if($output !== null){
                            $pos=$i;
                            return $output;
                        }
                    }
                    else if($val == "blockTag"){
                        $output = call_user_func_array(array($this,"blockTagParser"),array(&$i,$tagName));
                        if($output !== null){
                            $pos=$i;
                            return $output;
                        }
                    }
                }
            }
        }
        //$pos-=$i;
        $pos-=8;
        return null;
    }

    protected function tagAttributesParser(&$pos){
        $open = null;
        $i = $pos*1;

        $attrs = [];

        for($i;$i<$this->len;$i++){
            $eval = false;
            while( $this->get($i)!==null && trim($this->get($i)) == "" ){
                $i++;
            }

            $ord = ord( $this->get($i) );

            if( 
                !(
                    ( $ord >= ord('a') && $ord <= ord('z') ) ||
                    ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                    ( $ord >= ord('0') && $ord <= ord('9') ) || 
                    ( $ord == ord(':') )                     ||
                    ( $ord == ord('-') )                     ||
                    ( $ord == ord('_') )
                )
             ){
                 break;
            }

            $attrname = "";
            $attrValue = "";
            
            for($i;$i<$this->len;$i++){
                
                $ord = ord( $this->get($i) );
                if( 
                    ( $ord >= ord('a') && $ord <= ord('z') ) ||
                    ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                    ( $ord >= ord('0') && $ord <= ord('9') ) || 
                    ( $ord == ord(':') )                     ||
                    ( $ord == ord('-') )                     ||
                    ( $ord == ord('_') )
                 ){
                     $attrname .= $this->get($i);
                }
                else{
                    break;
                }
            }

            while( $this->get($i)!==null && trim($this->get($i)) == "" ){
                $i++;
            }

            if( $this->get($i) == "=" ){
                $q = $i+1;
                while( $this->get($q)!==null && trim($this->get($q)) == "" ){
                    $q++;
                }

                if( $this->get($q) == "'" || $this->get($q) == "\"" || $this->getRange($q,2) == "{{" ){
                    $i = $q;
                    if( $this->getRange($i,2) == "{{" ){
                        $open= "}}";
                        $eval = true;
                        $i+=2;
                    }
                    else{
                        $i = $q;
                        $open = $this->get($i);
                        $i++;
                    }
                    
                    $countSame = 0;
                    for($i;$i<$this->len;$i++){
                        if( $eval && $this->getRange($i,2) == "{{" ){
                            $countSame++;
                            $attrValue .= "{{";
                            $i++;
                            continue;
                        }
                        if( $eval && $countSame > 0 && $this->getRange($i,2) == "}}" ){
                            $countSame--;
                            $attrValue .= "}}";
                            $i++;
                            continue;
                        }
                        if( $this->get($i-1) != "\\" && $this->getRange($i,strlen($open)) == $open && $countSame <= 0 ){
                            if($eval){
                                $i++;
                            }
                            break;
                        }
                        $attrValue .= $this->get($i);
                    }

                    if( !empty($attrValue) ){
                        $wl = new Walker($attrValue);
                        //$wl->attachBlockDirective($this->__blockStorage);
                        //$wl->attachInlineDirective($this->__inlineStorage);
                
                        //$wl->attachInlineTag($this->__tagInlineStorage);
                        //$wl->attachBlockTag($this->__tagBlockStorage);

                        //$wl->attachAttribute($this->__attributesStorage);
                        //$wl->attachPreDefines($this->__preDefines);
                        $attrValue = $wl->parse();
                    }
                    else{
                        $attrValue = null;
                    }
                }
                else{
                    return null;
                }
            }
            else{
                $attrValue = null;
                $i--;
            }

            $call = function($ref,$val){
                return $val;
            };
            if( strpos($attrname,"server:") >= 0 ){
                $newAttr = str_replace("server:","",$attrname);
                if( $this->isAttribute($newAttr) ){
                    $call = RiverCompiler::getAttributeFn($newAttr);
                    $attrname = $newAttr;
                }
                else if( !empty($newAttr) && preg_match('/^[A-Za-z0-9\_\-\.]+$/',$newAttr) ){
                    $prdfns = RiverCompiler::getPreDefined();
                    foreach( $prdfns as $key=>$val ){
                        if( preg_match($key,$newAttr) ){
                            if( 
                                $val == "attr"
                            ){
                                $call = $newAttr;
                                break;
                            }
                        }
                    }
                }
            }

            $attrs[] = [
                "type"=>"attribute",
                "name"=>$attrname,
                "value"=>$attrValue,
                "eval"=>$eval,
                "callback"=>$call
            ];

            $eval = false;
            
            $open = null;
        }

        $pos = $i;
        return $attrs;
    }

    protected function blockContentParser(&$pos,$tag){
        $content = "";
        for($i=$pos;$i<$this->len;$i++){
            if( $this->getRange($i,strlen($tag)+10) == ("</server:" . $tag . ">") ){
                $i+=strlen($tag)+10;
                break;
            }
            else{
                $content .= $this->get($i);
            }
        }

        if( !empty($content) ){
            $wl = new Walker($content);
            //$wl->attachBlockDirective($this->__blockStorage);
            //$wl->attachInlineDirective($this->__inlineStorage);
    
            //$wl->attachInlineTag($this->__tagInlineStorage);
            //$wl->attachBlockTag($this->__tagBlockStorage);
            //$wl->attachAttribute($this->__attributesStorage);
            //$wl->attachPreDefines($this->__preDefines);
            $content = $wl->parse();
        }
        else{
            $content = null;
        }
        $pos = $i;

        return $content;
    }

    protected function blockTagParser(&$pos,$tag){
        $attrs = call_user_func_array(array($this,"tagAttributesParser"),array(&$pos));
        if( $attrs === null ){
            return null;
        }

        if( $this->get($pos) == ">" ){
            $pos++;

            $content = call_user_func_array(array($this,"blockContentParser"),array(&$pos,$tag));

            $isNext = !$this->isBlockTag($tag);
            $call = null;
            if($isNext){
                $call = $tag;
            }
            else{
                $call = RiverCompiler::getTagBlockFn($tag);
            }

            return [
                "type"=>"blockTag",
                "attributes"=>$attrs,
                "name"=>$tag,
                "content"=>$content,
                "callback"=>$call
            ];
        }
        return null;
    }

    protected function inlineTagParser(&$pos,$tag){
        $attrs = call_user_func_array(array($this,"tagAttributesParser"),array(&$pos));
        if( $attrs === null ){
            return null;
        }

        //$rf = &$this->__tagInlineStorage;
        $isNext = !$this->isInlineTag($tag);//!isset($rf[$tag]);
        $call = null;
        if($isNext){
            $call = $tag;
        }
        else{
            $call = RiverCompiler::getTagInlineFn($tag);
        }

        if( $this->get($pos) == "/" && $this->get($pos+1) == ">" ){
            $pos+=2;
            return [
                "type"=>"inlineTag",
                "attributes"=>$attrs,
                "name"=>$tag,
                "callback"=>$call
            ];
        }
        else if( $this->get($pos) == ">" ){
            $pos++;
            return [
                "type"=>"inlineTag",
                "attributes"=>$attrs,
                "name"=>$tag,
                "callback"=>$call
            ];
        }
        return null;
    }

    protected function atSignParser( &$pos ){
        $pos++;
        $output = null;
        
        switch($this->get($pos)){
            case "!":
                $output = call_user_func_array(array($this,"exMarkParser"),array(&$pos));
                if($output !== null){
                    $output = [
                        "type"=>"@!",
                        "value"=>$output["value"]
                    ];
                }
                break;
            case "@":
                $pos++;
                
                if( $this->get($pos) == "{" ){
                    $output = call_user_func_array(array($this,"bracketParser"),array(&$pos));
                }
                else{
                    $output = call_user_func_array(array($this,"atSignIdentifierParser"),array(&$pos));
                }
                if( $output === null ){
                    $pos--;
                }
                else{
                    $output = [
                        "type"=>"@@",
                        "value"=>$output
                    ];
                }
                break;
            default:
                if( $this->get($pos) == "{" ){
                    $output = call_user_func_array(array($this,"bracketParser"),array(&$pos));
                    if($output !== null){
                        $output = [
                            "type"=>"@",
                            "value"=>$output
                        ];
                    }
                }
                else{
                    $output = call_user_func_array(array($this,"atSignIdentifierParser"),array(&$pos));
                }
                break;
        }
        if( $output === null ){
            $pos--;
            return null;
        }
        if( $output == "" ){
            return "";
        }

        return $output;
    }

    protected function atSignIdentifierParser( &$pos ){
        $tempPos = $pos;
        $name = "";
        $output = null;
        
        for($pos;$pos<$this->len;$pos++){
            $ord = ord($this->get($pos));

            if( 
                $pos == $tempPos &&  
                (
                    ( $ord >= ord('a') && $ord <= ord('z') ) ||
                    ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                    ( $ord == ord('-') )                     ||
                    ( $ord == ord('_') )
                )
            ){
                $name .= $this->get($pos);
            }
            else if( 
                ( $ord >= ord('a') && $ord <= ord('z') ) ||
                ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                ( $ord >= ord('0') && $ord <= ord('9') ) || 
                ( $ord == ord('-') )                     ||
                ( $ord == ord('.') )                     ||
                ( $ord == ord('_') )
            ){
                $name .= $this->get($pos);
            }
            else{
                break;
            }
        }

        if( 
            $this->isBlockDirective($name) 
        ){
            $output = call_user_func_array(array($this,"blockIdentifier"),array($name,&$pos));
        }
        else if( 
            $this->isInlineDirective($name) 
        ){
            $output = call_user_func_array(array($this,"inlineIdentifier"),array($name,&$pos));
            $pos--;
        }
        else{
            $found =false;
            if( !empty($name) && preg_match('/^[A-Za-z0-9\_\-\.]+$/',$name) ){
                $prdfns = RiverCompiler::getPreDefined();
                foreach( $prdfns as $key=>$val ){
                    if( preg_match($key,$name) ){
                        if( 
                            $val == "block"
                        ){
                            $output = call_user_func_array(array($this,"blockIdentifier"),array($name,&$pos));
                        }
                        else if($val == "inline"){
                            $output = call_user_func_array(array($this,"inlineIdentifier"),array($name,&$pos));
                            $pos--;
                        }
                        $found = true;
                        break;
                    }
                }
            }
            if(!$found){
                $output = null;
                $pos = $tempPos;
            }
            
        }
        return $output;
    }


    protected function inlineIdentifier( $name , &$pos ){
        $paran = call_user_func_array(array($this,"parantesStatement"),array(&$pos));

        //$rf = &$this->__inlineStorage;
        $isNext = !$this->isInlineDirective($name);//isset($rf[$name]);
        $cl = null;
        if($isNext){
            $cl = $name;
        }
        else{
            $cl = RiverCompiler::getInlineFn($name);
        }

        return [
            "type"=>"inline",
            "param"=>$paran,
            "name"=>$name,
            "callback"=>$cl
        ];
    }

    protected function blockIdentifier( $name , &$pos ){
        $paran = call_user_func_array(array($this,"parantesStatement"),array(&$pos));
        $i=$pos;
        $isEnd = false;
        $content = "";

        $sames = 0;
        $sameMatcher = false;
        $sameMatchName= "";

        $count = (4+strlen($name));

        $isBrac = false;
        $noContentParse = false;
        for($i=$pos;$i<$this->len;$i++){
            if( empty(trim($this->get($i))) ){
                continue;
            }
            else if( $this->get($i) == "{" && (($this->get($i+1) == "{" && $this->get($i+2) == "{")||$this->get($i+1) !== "{") ){
                $isBrac = true;
                $pos = $i+1;
                break;
            }
            else if( $this->get($i) == "!" && $this->get($i+1) == "{"  && (($this->get($i+1) == "{" && $this->get($i+2) == "{")||$this->get($i+1) !== "{") ){
                $isBrac = true;
                $noContentParse = true;
                $pos = $i+2;
                break;
            }
            else{
                break;
            }
        }

        for( $i=$pos ; $i < $this->len ; $i++ ){
            
            if( !$isBrac && ($this->len - $i) >= $count ){
                $end = $this->getRange($i,$count);
                $after = ord($this->get($i+$count));
                if( 
                    $end == ("@end".$name) && 
                    !(
                        ( $after >= ord('a') && $after <= ord('z') ) ||
                        ( $after >= ord('A') && $after <= ord('Z') ) || 
                        ( $after >= ord('0') && $after <= ord('9') ) || 
                        ( $after == ord('-') )                     ||
                        ( $after == ord('.') )                     ||
                        ( $after == ord('_') )
                    )
                ){
                    if( $sames == 0 ){
                        $isEnd = true;
                        $i+=$count;
                        break;
                    }
                    else{
                        $sames--;
                        $content .= "@";
                    }
                }
                else{
                    if( $this->get($i) == "@" ){
                        $sameMatcher = true;
                    }
                    else if($sameMatcher){
                        $ord = ord($this->get($i));
                        if( 
                            empty($sameMatchName) &&  
                            (
                                ( $ord >= ord('a') && $ord <= ord('z') ) ||
                                ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                                ( $ord == ord('-') )                     ||
                                ( $ord == ord('_') )
                            )
                        ){
                            $sameMatchName .= $this->get($i);
                        }
                        else if( 
                            ( $ord >= ord('a') && $ord <= ord('z') ) ||
                            ( $ord >= ord('A') && $ord <= ord('Z') ) || 
                            ( $ord >= ord('0') && $ord <= ord('9') ) || 
                            ( $ord == ord('-') )                     ||
                            ( $ord == ord('.') )                     ||
                            ( $ord == ord('_') )
                        ){
                            $sameMatchName .= $this->get($i);
                        }
                        else{
                            if( $sameMatchName == $name ){
                                $sames++;
                            }
                            $sameMatchName = "";
                            $sameMatcher = false;
                        }
                        
                    }
                    $content .= $this->get($i);
                }
            }
            else if($isBrac){
                if( $this->get($i) == "}" ){
                    if( $sames == 0 ){
                        $isEnd = true;
                        $i++;
                        break;
                    }
                    else{
                        $sames--;
                        $content .= "}";
                    }
                }
                else if( $this->get($i) == "{" ){
                    $sames++;
                    $content .= "{";
                }
                else{
                    $content .= $this->get($i);
                }
            }
            else{
                return null;
            }
        }

        if( !$isEnd ){
            return null;
        }

        $pos = $i-1;

        $wl = $content;

        if( !$noContentParse ){
            $wl = new Walker($content);
            //$wl->attachBlockDirective($this->__blockStorage);
            //$wl->attachInlineDirective($this->__inlineStorage);

            //$wl->attachInlineTag($this->__tagInlineStorage);
            //$wl->attachBlockTag($this->__tagBlockStorage);
            //$wl->attachAttribute($this->__attributesStorage);
            //$wl->attachPreDefines($this->__preDefines);
            $wl = $wl->parse();
        }

        //$rf = &$this->__blockStorage;
        $isNext = !$this->isBlockDirective($name);//isset($rf[$name]);
        $cl = null;
        if($isNext){
            $cl = $name;
        }
        else{
            $cl = RiverCompiler::getBlockFn($name);//$this->__blockStorage[$name];
        }

        return [
            "type"=>"block",
            "param"=>$paran,
            "name"=>$name,
            "content"=>$wl,
            "callback"=>$cl
        ];
    }

    protected function parantesStatement(&$pos){
        $open = 0;
        $i = $pos;

        while( $this->get($i) !== null && trim($this->get($i)) == "" ){
            $i++;
        }

        if( $this->get($i) != "(" ){
            return null;
        }

        $i++;
        $open++;
        $inner = "";

        for( $i;$i<$this->len && $open>0;$i++ ){
            if( $this->get($i) == ")" ){
                $open--;
                if( $open == 0 ){
                    break;
                }
            }
            else if( $this->get($i) == "(" ){
                $open++;
            }

            $inner .= $this->get($i);
        }

        if( $open != 0 ){
            return null;
        }

        $pos = $i+1;

        $wl = new Walker($inner);
        //$wl->attachBlockDirective($this->__blockStorage);
        //$wl->attachInlineDirective($this->__inlineStorage);
        //$wl->attachInlineTag($this->__tagInlineStorage);
        //$wl->attachBlockTag($this->__tagBlockStorage);
        //$wl->attachAttribute($this->__attributesStorage);
        //$wl->attachPreDefines($this->__preDefines);

        return $wl->parse();
    }

    protected function exMarkParser( &$pos ){
        $pos++;
        $output = null;
        if( $this->get($pos) == "{" ){
            $output = call_user_func_array(array($this,"bracketParser"),array(&$pos));
        }

        if( $output === null ){
            $pos--;
            return null;
        }

        if( $output == "" ){
            return "";
        }
        return [
            "type"=>"!",
            "value"=>$output
        ];
    }

    protected function bracketParser( &$pos ){
        $pos++;
        $output = null;
        $isComment = false;
        if( $this->get($pos) == "{" ){
            $pos++;
            $text = "";
            $isEnd = false;
            $i=$pos;

            if( $this->get($pos) == "-" && $this->get($pos+1) == "-" ){
                $isComment = true;
                $pos+=2;
            }

            for($i=$pos;$i<$this->len;$i++){
                if( $isComment && $this->get($i) == "-" && $this->get($i+1) == "-" && $this->get($i+2) == "}" && $this->get($i+3) == "}" ){
                    $isEnd = true;
                    $i+=4;
                    break;
                }
                else if( !$isComment && $this->get($i) == "}" && $this->get($i+1) == "}"){
                    $i+=1;
                    $isEnd = true;
                    break;
                }
                $text.=$this->get($i);
            }

            if( !$isEnd ){
                $pos--;
                $output = null;
            }
            else{
                $output = $text;
                $pos = $i;
            }
        }

        if( $output === null ){
            $pos--;
            return null;
        }

        if( $isComment ){
            return "";
        }

        $wl = new Walker($output);
        //$wl->attachBlockDirective($this->__blockStorage);
        //$wl->attachInlineDirective($this->__inlineStorage);
        //$wl->attachInlineTag($this->__tagInlineStorage);
        //$wl->attachBlockTag($this->__tagBlockStorage);
        //$wl->attachAttribute($this->__attributesStorage);
        //$wl->attachPreDefines($this->__preDefines);

        return [
            "type"=>"bracket",
            "value"=>$wl->parse()
        ];
    }
}