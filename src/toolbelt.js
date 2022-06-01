let cookerToolbelt = {
    version: '0.0.1',
    isProd:function(){
        return __isProd__;
    },
    cookerVersion:function(){
        return "__cookerVersion__";
    },
    toolbeltVersion:function(){
        return cookerToolbelt.version;
    }
};
Object.freeze(cookerToolbelt); /*read only*/