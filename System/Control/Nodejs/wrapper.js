try{
    const process = require('process');
    const { exec } = require('child_process');
    const fs = require('fs');
    var args = process.argv.slice(2);
    var NewArgs = {};

    for(var i = 0 ; i < args.length - 1 ; i+=2){
        try{
            NewArgs[args[i]] = JSON.parse(args[i+1]);
        }
        catch(ee){
            NewArgs[args[i]] = unescape(args[i+1]);
        }
    }


    if( NewArgs["__ROOT__"] ){
        global.__ROOT__ = NewArgs["__ROOT__"];
        delete NewArgs["__ROOT__"];
    }

    if( NewArgs["__APP__"] ){
        global.__APP__ = NewArgs["__APP__"];
        delete NewArgs["__APP__"];
    }

    if( NewArgs["__SOURCE__"] ){
        global.__SOURCE__ = NewArgs["__SOURCE__"];
        delete NewArgs["__SOURCE__"];
    }

    if( NewArgs["__HOST__"] ){
        global.__HOST__ = NewArgs["__HOST__"];
        delete NewArgs["__HOST__"];
    }


    if( NewArgs["__HOSTWOS__"] ){
        global.__HOSTWOS__ = NewArgs["__HOSTWOS__"];
        delete NewArgs["__HOSTWOS__"];
    }

    if( NewArgs["__NODEJS__WRAPPER__"] ){
        global.__NODEJS__WRAPPER__ = NewArgs["__NODEJS__WRAPPER__"];
        delete NewArgs["__NODEJS__WRAPPER__"];
    }

    if( NewArgs["__UID__"] ){
        global.__UID__ = NewArgs["__UID__"];
        delete NewArgs["__UID__"];
    }

    if( NewArgs["__STDER__"] ){
        global.__STDER__ = NewArgs["__STDER__"];
        delete NewArgs["__STDER__"];
    }

    if( NewArgs["__STDOUT__"] ){
        global.__STDOUT__ = NewArgs["__STDOUT__"];
        delete NewArgs["__STDOUT__"];
    }

    


    global.log = function(str){
        var current = new Date();
        if(process.platform == "win32"){
            fs.appendFile(__STDOUT__, current.toUTCString() + " | " + str + "\n", function (err) {});
        }
        else{
            process.stdout.write(str)
        }
        
        return str;
    }

    global.error = function(str){
        var current = new Date();
        if(process.platform == "win32"){
            fs.appendFile(__STDER__, current.toUTCString() + " | " + str + "\n", function (err) {});
        }
        else{
            process.stderr.write(str)
        }
        
        return str;
    }

    if(fs.existsSync(__SOURCE__ + "/app.js")){
        const current = new Date();
        fs.writeFile(__NODEJS__WRAPPER__ + "/instance.running", current.toUTCString(), function (err) {
            if (err) {
                console.log(error("the instance.running doesn't created"));
            }
        });

        require(__SOURCE__ + "/app.js");
    }


    fs.watch(__NODEJS__WRAPPER__ + "/Config.json", (eventType, filename) => {
      if( !fs.existsSync(__NODEJS__WRAPPER__ + "/Config.json") ){
            process.exit(1);
        }
    });

    setInterval(() => {
        exec('forever columns --no-colors set id pid uid && forever list --no-colors', (err, stdout, stderr) => {
            var t = stdout.trim().split(/[\n]/);
            t = t.filter((a)=>a.indexOf(__UID__) > -1);
            t = t[t.length-1].trim();
            t = t.split(/[ \s]+/);
            var pid = t[t.length-2].trim();
            var uid = t[t.length-1].trim();
    
            if(process.ppid != pid && uid == __UID__){
                process.exit(1);
            }
        });
    }, 3000);

}
catch(e){
    error(e);
    fs.unlinkSync(__NODEJS__WRAPPER__ + "/Config.json");
    process.exit(1);
}