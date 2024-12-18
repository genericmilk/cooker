import cooker from '@/cooker'

class Application {
    constructor() {
        this.name = 'Cooker';
        console.log(this.name + ' is running' );
        console.log(cooker.version);
    }
};