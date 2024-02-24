var app = {
    _cookerPath: [
        '/',
        '/about',
        '/about/team',
        '/about/team/*',
    ],
    message: 'Cooker is running!',
    boot(){
        console.log(this.message);
    }
};