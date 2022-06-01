let cookerToolbelt = {
    isProd:function(){
        return __isProd__;
    },
    cookerVersion:function(){
        return "__cookerVersion__";
    }
};
Object.freeze(cookerToolbelt); /*read only */