const cookerToolbelt = {
    version: '1.0.0',
    isProd: __isProd__,
    autoRunIntelliPath: __autoRunIntelliPath__,
    cookerVersion: "__cookerVersion__",
    namespace: "__namespace__",
    boot(){
        var self = this;
        self.alertUpgradeGuide();
        if(self.autoRunIntelliPath){
            self.intelliPath();
        }
    },
    alertUpgradeGuide(){
        // count the number of meta[name=missing tags]
        let missingTags = document.querySelectorAll('meta[name=missing]');
        if(missingTags.length > 0){
            // add the failure warning to the top of the page
            let warning = document.createElement('div');

            warning.innerHTML = '<div style="background-color:#f8d7da;color:#721c24;padding:1rem;margin-bottom:1rem;border:1px solid #f5c6cb;border-radius:.25rem;">Cooker has been upgraded to version '+this.cookerVersion+', but you are using '+missingTags.length+' outdated embed '+(missingTags.length==1 ? "tag" : "tags") +' in your blade templates. Please see the upgrade guide to remove this message.</div>';
            document.body.insertBefore(warning, document.body.firstChild);
        }
    },
    intelliPath(){
        var parent = this;
        let rootScript = __namespace__;
        
        // filter rootScript to objects only
        let rootScriptObjects = Object.keys(rootScript).filter(function(key) {
            return typeof rootScript[key] === 'object';
        });


        // go through rootScriptObjects and check for a _cookerPaths property
        rootScriptObjects.forEach(function(rootElement){

            // look for objects in this element
            let subObjects = Object.keys(rootScript[rootElement]).filter(function(key) {
                return typeof rootScript[rootElement][key] === 'object';
            });

            subObjects.forEach(function(element){
                if(element=='_cookerPath'){

                    let paths = rootScript[rootElement][element];
                    let currentPagePath = window.location.pathname;
    
                    // go round each of the paths in a loop
                    paths.forEach(function(path){
                        // does this path have a wildcard (*) in it?
                        if(path.includes('*')){
                            // everything before * is a static path
                            let staticPath = path.split('*')[0];
                            // does the current page path start with the static path?
                            if(currentPagePath.startsWith(staticPath)){
                                try{
                                    rootScript[rootElement].boot();
                                }catch(e){
                                    console.error('Cooker: A _cookerPath was found, but the boot method could not be called. Please check your script features at least a boot method. (1)');
                                }
    
                            }
                        }else{
                            // doing a straight comparison
                            if(currentPagePath == path){
                                try{
                                    rootScript[rootElement].boot();
                                }catch(e){
                                    console.error('Cooker: A _cookerPath was found, but the boot method could not be called. Please check your script features at least a boot method. (2)');
                                }
                            }
                        }
    
                    });
                    
                }
            });

            

        });
    }
};
Object.freeze(cookerToolbelt); /*read only*/
document.addEventListener('DOMContentLoaded', function() {
    cookerToolbelt.boot();
});
