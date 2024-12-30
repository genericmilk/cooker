import cookerToolbelt from 'cooker-toolbelt';
import cookerRoutes from 'cooker-routes';

class Application{
    constructor() {
        this.name = 'Cooker';
        console.log(cookerToolbelt);
        cookerToolbelt.console.log(this.name + ' is running' );
        cookerToolbelt.console.log(cookerToolbelt.name + ' is running' );
    }
};

window.Application = Application; // Make the Application class available to be booted via cooker-routes