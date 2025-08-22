const fs = require("node:fs");
const path=require("node:path");

const DEBUG=(...args)=>{

      if(!fs.existsSync(path.join(__dirname, 'BUILD'))){
          console.log("DEBUG Mode");
          if(args.length > 0) args[0]();
      }else {
          if(args.length > 1) args[1]();
      }

}
module.exports = {
    DEBUG
}