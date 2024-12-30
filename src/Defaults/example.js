import cookerToolbelt from 'cooker-toolbelt';

class Application {
    constructor() {
        this.name = 'Cooker';
        console.log(cookerToolbelt);
        cookerToolbelt.console.log(this.name + ' is running' );
        cookerToolbelt.console.log(cookerToolbelt.name + ' is running' );
    }
};