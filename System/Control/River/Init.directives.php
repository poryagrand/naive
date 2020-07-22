<?php

use System\Controller\TemplateEngine\River;
use System\Controller\Route;
use System\Communicate\Debug\Console;
use System\Communicate\Session;
use System\Controller\TemplateEngine\RiverCache;
use System\Controller\TemplateEngine\RiverCompileHandleException;
use System\Security\Crypt;

River::preDefine("inline", "/^helper\.[^ \s\v\r\t\n]+/");
River::directiveBlock("helper", function ($ref, $arg, $content) {
    if (empty($arg)) {
        return Console::halt("helper directive needs arguments!");
    }

    $args = explode(",", $arg);
    if (count($args) <= 0) {
        return Console::halt("helper directive needs arguments!");
    }

    $name = $args[0];
    $rest = array_slice($args, 1);
    River::directiveInline("helper." . $name, function ($ref, $arg, $exec) use ($content, $rest, $name) {
        if (empty($arg)) {
            return Console::halt("helper." . $name . " directive needs arguments!");
        }

        $args = explode(",", $arg);
        $arguments = [];
        foreach ($rest as $key => $val) {
            $arguments[] = $val . "=" . (isset($args[$key]) ? $args[$key] : "null");
        }

        if ($exec) {
            return " (function(){ \$___ISEXECUTE___=" . ($exec ? "true" : "false") . "; " . (implode(";", $arguments)) . ";?> " . $content . "<?php ;})() ";
        }

        return "<?php (function(){ \$___ISEXECUTE___=" . ($exec ? "true" : "false") . "; " . (implode(";", $arguments)) . ";?> " . $content . "<?php ;})(); ?>";
    });
});

River::directiveInline("hret", function ($ref, $arg) {
    if (empty($arg)) {
        return Console::halt("return directive needs arguments!");
    }

    return "<?php if(\$___ISEXECUTE___){ return $arg;}else{ echo $arg;} ?>";
});

River::directiveInline("use", function ($ref, $arg) {
    if (empty($arg)) {
        return Console::halt("use directive needs arguments!");
    }

    return "<?php use $arg; ?>";
});

River::directiveInline("console.log", function ($ref, $arg) {
    return "<?php \System\Communicate\Debug\Console::log(" . $arg . "); ?>";
});
River::directiveInline("console.info", function ($ref, $arg) {
    return "<?php \System\Communicate\Debug\Console::info(" . $arg . "); ?>";
});
River::directiveInline("console.warn", function ($ref, $arg) {
    return "<?php \System\Communicate\Debug\Console::warn(" . $arg . "); ?>";
});
River::directiveInline("console.error", function ($ref, $arg) {
    return "<?php \System\Communicate\Debug\Console::error(" . $arg . "); ?>";
});

River::directiveInline("allLangs", function ($ref, $arg, $exec) {
    if (!$exec) {
        return "<?php echo json_encode(\\System\\Controller\\Language::all()); ?>";
    }
    return "\\System\\Controller\\Language::all()";
});

River::directiveInline("lang", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("lang directive needs arguments!");
    }

    if (!$exec) {
        return "<?php echo \\System\\Controller\\Language::get($arg); ?>";
    }
    return "\\System\\Controller\\Language::get($arg)";
});

River::directiveInline("clang", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("clang directive needs arguments!");
    }

    if (!$exec) {
        return "<?php echo ucwords(\\System\\Controller\\Language::get($arg)); ?>";
    }
    return "ucwords(\\System\\Controller\\Language::get($arg))";
});

River::directiveInline("langName", function ($ref, $arg, $exec) {

    if (!$exec) {
        return "<?php echo \\System\\Controller\\Language::name(); ?>";
    }
    return "\\System\\Controller\\Language::name()";
});

River::directiveInline("langIcon", function ($ref, $arg, $exec) {

    if (!$exec) {
        return "<?php echo \\System\\Controller\\Language::icon(); ?>";
    }
    return "\\System\\Controller\\Language::icon()";
});

River::directiveInline("langSlug", function ($ref, $arg, $exec) {

    if (!$exec) {
        return "<?php echo \\System\\Controller\\Language::slug(); ?>";
    }
    return "\\System\\Controller\\Language::slug()";
});

River::directiveInline("langRtl", function ($ref, $arg, $exec) {
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Language::isRtl(); ?>";
    }
    return "\\System\\Controller\\Language::isRtl()";
});

River::directiveInline("langDir", function ($ref, $arg, $exec) {
    if (!$exec) {
        return "<?php echo (\\System\\Controller\\Language::isRtl()?'rtl':'ltr'); ?>";
    }
    return "(\\System\\Controller\\Language::isRtl()?'rtl':'ltr')";
});

River::directiveInline("langFont", function ($ref, $arg, $exec) {
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Language::font(); ?>";
    }
    return "\\System\\Controller\\Language::font()";
});


River::directiveInline("csrf", function ($ref, $arg, $exec) {
    if (!$exec) {
        return "<input type=\"hidden\" value=\"<?php echo \\System\\Security\\Crypt::getCSRF(\$this->getReq()->getCSRFName()); ?>\" name=\"X-Csrf-Token\"/> ";
    }

    return "\\System\\Security\\Crypt::getCSRF(\$this->getReq()->getCSRFName());";
});



River::directiveInline("getCSRF", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("getCSRF directive needs arguments!");
    }

    $arg = trim($arg);

    if (!preg_match("/(\\\"|\\'|^\\s*PathID)/", $arg)) {
        $arg = "'" . $arg . "'";
    }

    $arg2 = Crypt::eval("return " . $arg . ";");
    if ($arg2 !== null) {
        $arg = $arg2;
    }

    if (!$exec) {
        return "<?php echo \\System\\Security\\Crypt::getOrGenerateIfNotExist(\\System\\Security\\Crypt::hash('" . ($arg) . "')); ?>";
    }

    return "\\System\\Security\\Crypt::getOrGenerateIfNotExist(\\System\\Security\\Crypt::hash('" . ($arg) . "'))";
});

River::directiveInline("id", function ($ref, $arg, $exec) {
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Route::currentID(); ?>";
    }
    return "\\System\\Controller\\Route::currentID()";
});

River::directiveInline("share", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("share directive needs arguments!");
    }
    if (!$exec) {

        return "<?php echo \\System\\Controller\\Route::current()->river()->share($arg); ?>";
    }
    return "\\System\\Controller\\Route::current()->river()->share($arg)";
});

River::directiveInline("shareEnc", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("shareEnc directive needs arguments!");
    }
    if (!$exec) {

        return "<?php echo json_encode(\\System\\Controller\\Route::current()->river()->share($arg)); ?>";
    }
    return "json_encode(\\System\\Controller\\Route::current()->river()->share($arg))";
});

River::directiveInline("set", function ($ref, $arg, $exec) {
    if (empty($arg) && count(explode(",", $arg)) <= 0) {
        return Console::halt("set directive needs arguments!");
    }
    return "<?php \\System\\Controller\\Route::current()->river()->share($arg); ?>";
});

River::directiveInline("route", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("route directive needs arguments!");
    }

    $arg = explode(",", $arg, 2);
    $route = trim($arg[0], "\"'");
    $out = '\\System\\Controller\\Route::url("' . $route . '",' . (isset($arg[1]) ? $arg[1] : "null") . ')';
    if (!$exec) {
        return "<?php echo " . $out . "; ?>";
    }
    return $out;
});

River::directiveInline("const", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("share directive needs arguments!");
    }
    $out = $ref->share(trim($arg, "\"'"));
    if (!$exec) {
        return json_encode($out);
    }
    return is_string($out) ? json_encode($out) : $out;
});


River::directiveBlock("if", function ($ref, $arg, $content) {
    if (empty($arg)) {
        return Console::halt("if directive needs arguments!");
    }
    return "<?php if ($arg) :  ?> $content <?php endif; ?>";
});

River::directiveInline("elseif", function ($ref, $arg) {
    if (empty($arg)) {
        return Console::halt("else if directive needs arguments!");
    }

    return "<?php elseif ($arg) : ?>";
});

River::directiveInline("else", function ($ref, $arg) {
    return "<?php else : ?>";
});


River::directiveBlock("while", function ($ref, $arg, $content) {
    if (empty($arg)) {
        return Console::halt("while directive needs arguments!");
    }

    return "<?php while ($arg) {  ?> $content <?php } ?>";
});

River::directiveBlock("for", function ($ref, $arg, $content) {
    if (empty($arg)) {
        return Console::halt("for directive needs arguments!");
    }

    return "<?php for ($arg) {  ?> $content <?php } ?>";
});

River::directiveBlock("foreach", function ($ref, $arg, $content) {
    if (empty($arg)) {
        return Console::halt("foreach directive needs arguments!");
    }

    return "<?php foreach ($arg) {  ?> $content <?php } ?>";
});


River::directiveInline("break", function ($ref, $arg) {
    if (!empty($arg)) {
        return "<?php if( $arg ){break;} ?>";
    }
    return "<?php break; ?>";
});

River::directiveInline("continue", function ($ref, $arg) {
    if (!empty($arg)) {
        return "<?php if( $arg ){continue;} ?>";
    }
    return "<?php continue; ?>";
});

River::directiveInline("require", function ($ref, $arg) {
    if (empty($arg)) {
        return Console::halt("require directive needs arguments!");
    }

    $args = Crypt::eval("return [" . $arg . "];");
    if ($args !== null) {
        $args = $arg;
    }

    $clone = $ref->share();
    if (count($args) > 1 && is_array($arg[1])) {
        foreach ($args[1] as $key => $val) {
            $clone[$key] = $val;
        }
    }

    $riv = new River($ref->getReq());
    $path = Route::path("view:" . str_replace(".river.php", "", $args[0]) . ".river.php");
    $riv->render($path, $clone);
    return "<?php require_once ('" . RiverCache::path($path) . "'); ?>";
});

River::directiveInline("include", function ($ref, $arg) {
    if (empty($arg)) {
        return Console::halt("include directive needs arguments!");
    }

    if (!is_array($arg)) {
        $args = Crypt::eval("return [" . $arg . "];");
        if ($args !== null) {
            $arg = $args;
        }
    }

    $clone = $ref->share();
    if (count($arg) > 1 && is_array($arg[1])) {
        foreach ($arg[1] as $key => $val) {
            $clone[$key] = $val;
        }
    }

    $riv = new River($ref->getReq());
    return $riv->render(Route::path("view:" . str_replace(".river.php", "", $arg[0]) . ".river.php"), $clone, false);
});


// requests
River::directiveInline("get", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("get directive needs arguments!");
    }
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Route::current()->req()->get($arg); ?>";
    }
    return "\\System\\Controller\\Route::current()->req()->get($arg)";
});

River::directiveInline("post", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("post directive needs arguments!");
    }
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Route::current()->req()->post($arg); ?>";
    }
    return "\\System\\Controller\\Route::current()->req()->post($arg)";
});

River::directiveInline("param", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("param directive needs arguments!");
    }
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Route::current()->req()->param($arg); ?>";
    }
    return "\\System\\Controller\\Route::current()->req()->param($arg)";
});

River::directiveInline("file", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return Console::halt("file directive needs arguments!");
    }
    if (!$exec) {
        return "<?php echo \\System\\Controller\\Route::current()->req()->file($arg); ?>";
    }
    return "\\System\\Controller\\Route::current()->req()->file($arg)";
});

River::directiveInline("genCSRF", function ($ref, $arg, $exec) {
    return "<?php \\System\\Security\\Crypt::generateCSRFToken(\\System\\Security\\Crypt::hash($arg)); ?>";
});

River::directiveInline("getUrl", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'], 2)[0];
    }

    if (!is_array($arg)) {
        $args = Crypt::eval("return [" . $arg . "];");
        if ($args !== null) {
            $arg = $args;
        }
    }
    if (count($arg) < 2) {
        $arg[1] = [];
    }

    if (!$exec) {
        return Route::url($arg[0], $arg[1]);
    }
    return '"' . Route::url($arg[0], $arg[1]) . '"';
});


River::directiveInline("url", function ($ref, $arg, $exec) {
    if (empty($arg)) {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'], 2)[0];
    }

    if (!is_array($arg)) {
        $arg = explode(",", $arg, 2);
        if (count($arg) > 1) {
            try {
                $arg[1] = Crypt::eval("return " . $arg[1] . ";");
            } catch (\Exception $e) {
                $arg[1] = [];
            }
        } else {
            $arg[1] = [];
        }
    }

    if (!$exec) {
        return Route::url($arg[0], $arg[1]);
    }
    return '"' . Route::url($arg[0], $arg[1]) . '"';
});



River::directiveInline("randStr", function ($ref, $arg, $exec) {
    return Crypt::random(Crypt::RAND_STR);
});

River::directiveInline("randInt", function ($ref, $arg, $exec) {
    return Crypt::random(Crypt::RAND_NUM);
});

River::directiveInline("randHash", function ($ref, $arg, $exec) {
    return Crypt::random(Crypt::RAND_HASH);
});

$GLOBALS["htmlConstList"] = [];
River::directiveInline("html", function ($ref, $arg, $exec) {
    $arg = explode(",", $arg, 2);
    if (count($arg) > 0) {
        $GLOBALS["htmlConstList"][trim($arg[0])] = trim($arg[1]);
    }
    return "";
});

River::directiveInline("getHTML", function ($ref, $arg, $exec) {
    $str = "";
    if (isset($GLOBALS["htmlConstList"][$arg])) {
        $str = $GLOBALS["htmlConstList"][$arg];
    }
    $str = preg_replace("/[\r\n]/", "\\n", $str);
    if ($exec) {
        return json_encode($str);
    }
    return $str;
});

River::directiveInline("session", function ($ref, $arg, $exec) {
    if (!is_array($arg)) {
        $arg = explode(",", $arg, 2);
    }


    if (is_array($arg) && count($arg) >= 1) {
        if (count($arg) == 2) {
            return "<?php \\System\\Communicate\\Session::get(" . $arg[0] . ")->value = $arg[1]; ?>";
        } else if (Session::has($arg[0])) {
            $val = ' \\System\\Security\\Crypt::unSerialize(\\System\\Communicate\\Session::get(' . $arg[0] . ')->value) ';
            if ($exec) {
                return $val;
            }
            return "<?php echo json_encode(\\System\\Security\\Crypt::unSerialize(" . $val . ")); ?>";
        }
    }

    if ($exec) {
        return "null";
    }
    return "";
});
