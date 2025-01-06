class cookerRoutes{
    constructor(){
        let self = this;
        this.routes = [];
        console.log('cookerRoutes');
        // wait for the DOM to load
        document.addEventListener('DOMContentLoaded', function(){
            self.activateStartupClasses();
        });
    };
    classesToBoot(){
        let classesToBoot = [];
        // go round each of this.routes and check the key matches window.location.pathname - wildcards are allowed
        for(let i = 0; i < this.routes.length; i++){

            let path = this.routes[i].path;
            let className = this.routes[i].class;
            

            // is the path just a wildcard? if so we can just run the class
            if(path === '*'){
                classesToBoot.push(className);
            }else{
                // does the path contain a wildcard?
                if(path.includes('*')){
                    // remove the wildcard and partial match the path
                    let pathParts = path.split('*');
                    let pathStart = pathParts[0];
                    let pathEnd = pathParts[1];

                    if(window.location.pathname.startsWith(pathStart) && window.location.pathname.endsWith(pathEnd)){
                        classesToBoot.push(className);
                    }
                }else{
                    // no wildcard, just match the path
                    if(path === window.location.pathname){
                        classesToBoot.push(className);
                    }
                }
            }
        }

        return classesToBoot;

    };
    activateStartupClasses(){
        let classesToBoot = this.classesToBoot();
        for(let i = 0; i < classesToBoot.length; i++){
            let classToBoot = classesToBoot[i];
            new window[classToBoot]();
        }

    }
};

new cookerRoutes();
export default cookerRoutes;