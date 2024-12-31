<p align="center">
	<img src="banner.png" />	
</p>
<p>
Cooker is the easy to use frontend resource compiler by <a href="https://github.com/genericmilk">genericmilk</a> that is designed to sit tightly within Laravel and offer a robust, fast and beginner friendly starting block for building your web applications from simple landing pages to full web apps.
</p>
<p>
  Cooker aims to be a great middleground for those who don't want to get bogged-down with all of the baggade of node and NPM but offers similar functionality.
</p>

<ul>
  <li>
    Getting started
    <ul>
      <li>
        <a href="#what-is-cooker-and-why-should-i-use-it">What is Cooker and why should I use it?</a>
      </li>
      <li>
        <a href="#installing-cooker">Installing Cooker</a>
      </li>
    </ul>
  </li>
  <li>
    Configuration
    <ul>
      <li>
        <a href="#setting-up-ovens">Setting up Ovens</a>
      </li>
      <li>
        <a href="#installing-packages">Installing packages</a>
      </li>
      <li>
        <a href="#compiling-resources">Compiling resources</a>
      </li>
    </ul>
  </li>
  <li>
    Features and helpers
    <ul>
      <li>
        <a href="#the-cooker-helper">The @cooker helper</a>
      </li>
      <li>
        <a href="#cooker-toolbelt">Cooker Toolbelt</a>
      </li>
      <li>
        <a href="#speedy-cook">SpeedyCook</a>
      </li>
      <li>
        <a href="#development-and-production-mode">Development and Production mode</a>
      </li>      
    </ul>
  </li>
  <li>
    Extending Cooker
    <ul>
      <li>
        <a href="#getting-started-with-preloads">Getting started with Preloads</a>
      </li>
      <li>
        <a href="#building-your-own-oven">Building your own oven</a>
      </li>
      <li>
        <a href="#cooker-object-oriented-javascript">Cooker object-oriented javascript</a>
      </li>
    </ul>
  </li>
  <li>
    Upgrading Cooker
    <ul>
      <li>
        <a href="https://github.com/genericmilk/cooker/wiki/Upgrading-to-Cooker-7-from-Cooker-6">Upgrading from Cooker 6 - 7</a>
      </li>
      <li>
        <a href="#">Upgrading from Cooker 5 - 6</a>
      </li>
      <li>
        <a href="https://github.com/genericmilk/cooker/wiki/Upgrading-to-Cooker-5-from-Cooker-4">Upgrading from Cooker 4 - 5</a>
      </li>
      <li>
        <a href="#">Upgrading from Cooker 1.x / 2.x / 3.x - 4</a>
      </li>      
    </ul>
  </li>
</ul>

***

## What is Cooker and why should I use it?

Cooker is a composer package that reimpliments what Node and NPM have to offer but in a much tighter integration to Laravel and a lot more beginner friendly. Cooker allows you to really quickly and easily takes smaller files, such as `.less`, `.scss`, `.js` etc and compiles them into bigger files such as `css` and `js`, full to the brim of all your apps' code and with intelligent loading depending on the page you are on.

You might be used to other platforms that do this sort of thing such as Webpack etc.

Files that are non standard, such as LESS and SCSS need compiling to CSS in order for the browser to understand them, but by including all of what makes a frontend into one compiler, makes for a rapidly better development experience.

Cooker aims to replace bigger services such as NPM, Webpack and Laravel mix by dramatically lowering the bar for entry and providing a frontend resource system that is uniquely Laravel, without the baggage of a Node based compiler.

Cooker is infinitely customisable and really easy to deploy and use. It is also super lightweight with heavy file compression and caching techniques speeding up your application.

## Installing Cooker

Before you install Cooker, please make a note of the following "gotchas" as Cooker's installer will replace some Laravel directories in order to install:

1. Please back up everything you have in your /resources/js and /resources/sass folders. Cooker will replace these folders as it is taking over the running of your application's frontend.
2. Please ensure you are using at least PHP 8.3 and Laravel 10 as these are the pre-requisites for Cooker

Once you have completed the above list and you're sure you are ready to proceed, copy and paste the two commands into a terminal whilst in the root of your Laravel application:
```
composer require genericmilk/cooker
php artisan cooker:install
```
This will install Cooker as well as its dependencies and install Cooker's configuration files.

Once Cooker is installed you can utilise the `@cooker` directive in your projects

***

## Setting up Ovens

When you ran `php artisan cooker:init` for the first time, It published a `cooker.php` file to your Laravel application's `/config` directory.

This file (hereafter referred to as "Cooker's configuration file") is where you can adjust how Cooker runs as well as specifying "ovens" which Cooker uses to build files. 

Cooker uses ovens to build a resulting file. We call this combination and parsing effort "Cooking". Each oven listed in your configuration outputs one file. Different ovens denote different types of files be it Javascript, LESS, SASS, Styl etc.

For example; you may have a Javascript file for billing and another script for a dropdown menu. You would want to combine these scripts to both be available on the output so that both the billing and dropdown scripts are loaded together by the browser.

Each oven processes the output files when the page is loaded automatically. If the source files have not changed, a cached version will be presented instad.

Cooker processes the file by following the files in the `oven.components.parse` array in the config file. 

Input files are loaded in specified order from the `parse` array with each file being parsed depending on the file output MIME type referenced in `oven.file` value in the config.

Once the file has been built, Cooker will detect if the application is running in production automatically and compress the output for faster loading. 

Files that are referenced in the `oven.components.parse` array are local to the `/resources/[input file extension]` folder in your application, so for example if your `oven.file` was `app.less`, Cooker will start loading files from `/resources/less/`.

eg:
```
  'file' => 'app.less',

  'components' => [

    'parse' => [
        'colors.less',
        'fonts.less',
        'home.less',
        'about.less',
        ...
    ],

  ]

```
* Cooked files are cached to `.cooker/cache` folder with the name of the file being referenced in `oven.file`. (Default app.less or app.js etc)

## The @cooker helper
Cooker comes with a great blade directive you can use in your views. 

It will return a HTML element pointing the browser to a custom cooker installed route with caching information attached. 

When the application is running in `app.debug=false` the output of the file will be compressed to allow for faster load times and vice-versa for `app.debug=true` to allow for easier debugging

To use the helper simply include it like so:
```
@cooker('app.less')
@cooker('app.js')
```
You can substitute `app.less` and `app.js` for the the value specified in `oven.file` in the Cooker config

## Installing packages
Cooker can use the JSDelivr NPM repository to really quickly get files into your project. Right now we only support downloading the most up-to-date version of this environment with the default file being referenced. And whilst we will compress it for you on production, this may not be a specific production build.

You will also need to add the following to your `.gitignore` file:
```
cooker_packages
```
The cooker_packages directory will fill up with your scripts as you specify them. Next, ensure the new configuration options are available in your `config/cooker.php` file near the top (Before the ovens):
```
'packageManager' => [
    'packagesList' => env('COOKER_PACKAGE_JSON_LOCATION', base_path('cooker.json')),
    'packagesPath' => env('COOKER_PACKAGE_PATH', base_path('cooker_packages')),
    'packageManager' => env('COOKER_PACKAGE_MANAGER', 'jsdelivr'),
],
```
To get started with an install of jQuery, run the following command on an installed version of cooker:
```
php artisan cooker:get jquery
```
Please substitute `jquery` as needed for different packages. Cooker will then download jQuery from NPM and deploy it in the `cooker_packages` folder. You will be then asked to run a cook job to mix jQuery into your application. 

***

## Cooker Toolbelt
By default, Cooker includes a small Javascript file that is loaded and locked on page boot. The object is located at `window.cookerToolbelt` and contains the following tools at this point in time:
* `cookerToolbelt.version` returns the current toolbelt version
* `cookerToolbelt.isProd` returns a boolean of if the javascript file was built using Production mode
* `cookerToolbelt.autoRunIntelliPath` returns a boolean of if intelliPath can run
* `cookerToolbelt.cookerVersion` returns the current cooker version
* `cookerToolbelt.namespace` returns a string of the javascript namespace as defined in the oven

## Development and Production mode
Cooker will automatically compress both `css` and `js` depending on the value of `config('app.debug')`. You can override this setting by running `php artisan cook` with `--dev` and `--prod` switches respectively

***

## Cooker object-oriented javascript
Cooker gives a really nice way to organise and build javascript files to compile into one cooked file. All the javascript files utilise an object oriented approach which makes it super easy to componentise and traverse larger files.

Files are cooked using the first specified javascript file in the job as the base object. This should be a variable containing a javascript object with the name of the variable being set to what is specified in the configuration for cooker. For example a cooker application with the namespace of `app` needs to have the following structure
```
var app = {  
};
```
Your base object should contain at least one `boot()` function. This will be called as `<namespace>.boot()` on application initialisation. This can then be used in such a way like below:

```
var app = {
  hey: 'Hello world',
  boot(){
    alert(this.hey);
  }
};
```
In this example `app.boot();` is called on document ready and an alert will fire containing the string stored in `app.hey` which has been set to "Hello world". Pretty cool right?!

You can extend the `app` object in other scripts referenced in the cooker's job under `input`.

To extend your script simply create a sub-object to the top level namespaced object by specifying it as such:
```
app.anotherObject = {
  boot(){
    alert('Hello from other file');
  }
};
```
You can then call this script from the main object at the start of your cook as such;
```
var app = {
  boot(){
    app.anotherObject.boot();
  }
};
```
This'll fire an alert with `Hello from other file` as the function is executed inside `app.anotherObject.boot()`

## Requirements for using Cooker
Cooker is happiest on:
* Laravel 10
* PHP >=8.3