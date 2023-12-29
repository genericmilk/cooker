let cookerToolbelt = {
    version: '1.0.0',
    isProd: __isProd__,
    cookerVersion: "__cookerVersion__",
    boot(){
        this.alertUpgradeGuide();
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
    }
};
Object.freeze(cookerToolbelt); /*read only*/
document.addEventListener('DOMContentLoaded', function() {
    cookerToolbelt.boot();
});