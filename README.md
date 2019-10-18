# 👨‍🍳 Cooker
## By Genericmilk

Cooker is a lightweight framework that allows you to quickly build LESS and Javascript files into a Laravel App. 

Resources that are built will be placed in the `/public/build` folder as `app.js` and `app.css` respectively. If the app is running in production mode the resources will be compressed and minified too! As if by magic.

### Installation

To install run
```
$ composer require genericmilk/cooker
```
This will install the requirements and it will publish the artisan command `build:res`. If you want to change the namespace used in generation, publish the config file
```
$ php artisan vendor:publish --provider="Genericmilk\Cooker\ServiceProvider"
```

### To cook
To run, simply execute
```
$ php artisan build:res
```
