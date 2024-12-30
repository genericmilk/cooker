class cookerRoutes{
    constructor(){
        this.routes = [];

    
        for(let i = 0; i < this.routes.length; i++){
            let path = this.routes[i].path;
            let className = this.routes[i].class;

            console.log(className,window[className]);

            // is the path just a wildcard? if so we can just run the class
            if(path === '*'){
                new window[className];
            }else{
                // does the path contain a wildcard?
                if(path.includes('*')){
                    // remove the wildcard and partial match the path
                    let pathParts = path.split('*');
                    let pathStart = pathParts[0];
                    let pathEnd = pathParts[1];

                    if(window.location.pathname.startsWith(pathStart) && window.location.pathname.endsWith(pathEnd)){
                        new window[className];
                    }
                }else{
                    // no wildcard, just match the path
                    if(path === window.location.pathname){
                        new window[className];
                    }
                }
            }
        }

    };

};

new cookerRoutes();
export default cookerRoutes;