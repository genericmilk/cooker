class cookerRoutes{
    constructor(){
        this.routes = [];

        console.log(this.routes);

        // this.routes contains key which is the route and value which is the class to be instantiated

        const currentPath = window.location.pathname;

        this.routes.forEach(route => {
            const routePattern = new RegExp('^' + route.replace(/\*/g, '.*') + '$');
            if (routePattern.test(currentPath)) {
            // Instantiate the class associated with the route. The class is the value of this.routes
            const newRoute = this.routes[route];

            new route.class();
            }
        });



    }
};

new cookerRoutes();

export default cookerRoutes;