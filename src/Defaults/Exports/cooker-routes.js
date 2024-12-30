class cookerRoutes{
    constructor(){
        this.routes = [];

        console.log(this.routes);

        // go round each of this.routes and check the key matches window.location.pathname - wildcards are allowed
        for(let i = 0; i < this.routes.length; i++){
            let path = this.routes[i].path;
            let className = this.routes[i].class;

            // is the path just a wildcard? if so we can just run the class
            if(path === '*'){
                new Application();
            }else{
                // does the path contain a wildcard?
                if(path.includes('*')){
                    // remove the wildcard and partial match the path
                    let pathParts = path.split('*');
                    let pathStart = pathParts[0];
                    let pathEnd = pathParts[1];

                    if(window.location.pathname.startsWith(pathStart) && window.location.pathname.endsWith(pathEnd)){
                        new Application();
                    }
                }else{
                    // no wildcard, just match the path
                    if(path === window.location.pathname){
                        new Application();
                    }
                }
            }
        }

    }
};

new cookerRoutes();

export default cookerRoutes;