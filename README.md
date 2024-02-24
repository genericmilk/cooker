<p align="center">
	<img src="cooker-full.png" width="200" />	
</p>
<h1 align="center">
	Cooker v7.0.0
</h1>
<p>
Cooker is the easy to use frontend compiler by <a href="https://github.com/genericmilk">genericmilk</a> that is designed to sit tightly within Laravel and offer a robust, fast and beginner friendly starting block for building your web applications
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
        <a href="#">Installing packages</a>
      </li>
      <li>
        <a href="#">Compiling resources</a>
      </li>
    </ul>
  </li>
  <li>
    Features and helpers
    <ul>
      <li>
        <a href="#">The @cooker helper</a>
      </li>
      <li>
        <a href="#">Cooker Toolbelt</a>
      </li>
      <li>
        <a href="#">SpeedyCook</a>
      </li>
      <li>
        <a href="#">Development and Production mode</a>
      </li>      
    </ul>
  </li>
  <li>
    Extending Cooker
    <ul>
      <li>
        <a href="#">Writing Cooker Object-oriented javascript</a>
      </li>
      <li>
        <a href="#">Custom Ovens</a>
      </li>
    </ul>
  </li>
  <li>
    Upgrading Cooker
    <ul>
      <li>
        <a href="#">Upgrading from Cooker 6 - 7</a>
      </li>
      <li>
        <a href="#">Upgrading from Cooker 5 - 6</a>
      </li>
      <li>
        <a href="#">Upgrading from Cooker 4 - 5</a>
      </li>
      <li>
        <a href="#">Upgrading from Cooker 1.x / 2.x / 3.x - 4</a>
      </li>      
    </ul>
  </li>
</ul>

***

## What is Cooker and why should I use it?

Cooker is a composer package that really quickly and easily takes smaller files, such as `.less`, `.scss`, `.js` etc and compiles them into bigger files such as `app.css` and `app.js`, full to the brim of all your apps' code and with intelligent loading depending on the page you are on.

You might be used to other platforms that do this sort of thing such as Webpack etc.

Files that are non standard, such as LESS and SCSS need compiling to CSS in order for the browser to understand them, but by including all of what makes a frontend into one compiler, makes for a rapidly better development experience.

Cooker aims to replace bigger services such as NPM, Webpack and Laravel mix by dramatically lowering the bar for entry and providing a frontend resource system that is uniquely Laravel, without the baggage of a Node based compiler.

Cooker is infinitely customisable and really easy to deploy and use. It is also super lightweight with heavy file compression and caching techniques speeding up your application.

## Installing Cooker

Before you install Cooker, please make a note of the following "gotchas" as Cooker's installer will replace some Laravel directories in order to install:

1. Please back up everything you have in your /resources/js and /resources/sass folders. Cooker will replace these folders as it is taking over the running of your application's frontend.
2. Please ensure you are using at least PHP 8.2 and Laravel 10 as these are the pre-requisites for Cooker

Once you have completed the above list and you're sure you are ready to proceed, copy and paste the two commands into a terminal whilst in the root of your Laravel application:
```
composer require genericmilk/cooker
php artisan cooker:init
```
This will install Cooker as well as its dependencies and install Cooker's configuration files and walk you through getting your environment setup.

If you are taking on a project that is written in Cooker, consult <a href="#">"Compiling resources"</a> to build the application files.

***

## Setting up Ovens

When you ran `php artisan cooker:init` for the first time, It published a `cooker.php` file to your Laravel application's `/config` directory.

This file (hereafter referred to as "Cooker's configuration file") is where you can adjust how Cooker runs as well as specifying "ovens" which Cooker uses to build files. 

Cooker uses ovens to build a resulting javascript or css file. We call this combination and parsing effort "Cooking".

Each oven listed in your configuration outputs one file. Different ovens denote different types of files be it Javascript, LESS, SASS, Styl etc.

For example; you may have a Javascript file for billing and another script for a dropdown menu. You would want to combine these scripts to both be available on the output so that both the billing and dropdown scripts are loaded together by the browser.

Each oven processes the output files by doing the following
1. Adds a timestamp to the head of the file for quick identification of when the last job ran. You can switch this off by setting `stamped` to `false` in the config file
2. Obtains frameworks if the filetype is `js`
3. Obtain any packages in cooker.json using the NPM network. To consult adding new packages to your project or to learn more, please consult [adding packages to cooker](#adding-packages-to-cooker)
4. Obtain any *preloads* specified for the oven. Preloads are specified in the `preload` array in the configuration and can be a direct url or a full path to the resource file. Preloads can be offered either as a string to load on both production and local versions of your build or an array to specify the differences between the two. Preloads are not compressed on build, so it is reccomend you use a minified version in production environments. To learn more about Preloads, please consult [getting started with Preloads](#getting-started-with-preloads)
5. Obtain any *libraries* automatically and in filename order from the `resources/<ovenDir>/libraries` folder where `<ovenDir>` is defined by the oven running. If you're using a built in oven supplied by Cooker this will be the lowercase of the oven name. If you are using your own consult [Building your own Oven](#building-your-own-oven). Libraries are loaded after preloads so it's a great idea to put singular scripts in this folder to get loaded before your AppCode. Libraries are not compressed on build and as such minified versions are reccomended so that they are also production ready.
6. Create a new output buffer ready to compile the code
7. If configured, Cooker will add `cookerToolbelt` to the file if the filetype is `js` and the configuration for `toolbelt` is set to `true` in the oven.
8. Cooker will finally process your Appcode. Input files are then loaded in specified order from the job `input` array. These files are parsed using the oven loaded and are minified in production automatically. For example if you were using the `Genericmilk\Cooker\Ovens\Less` oven, you could reference files like below so colours and fonts load first. The base directory for reference is that which is supplied by the oven (eg: /resources/less/*)
```
  'input' => [
      'colors.less',
      'fonts.less',
      'home.less',
      'about.less',
      ...
  ],
```
* The cooked file is published to the `public/build` folder under the name specified in the jobs' `output` string. (Default app.css or app.js etc)

## Installing packages
Cooker can use a NPM repository such as `unpkg` or `jsdelivr` to really quickly get files into your project. Right now we only support downloading the most up-to-date version of this environment with the default file being referenced. And whilst we will compress it for you on production, this may not be a specific production build.

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
php artisan cooker:install jquery
```
Please substitute `jquery` as needed for different packages. Cooker will then download jQuery from NPM and deploy it in the `cooker_packages` folder. You will be then asked to run a cook job to mix jQuery into your application. 

## Compiling resources
Cooker compiles resource when the `php artisan cook` command is run. This can be hooked into a save action such as the "Run on Save" by pucelle for Visual Studio Code or you can use the `php artisan cooker:watch` function to watch for file changes to detect when to run the function.

***

## The @cooker helper
Cooker comes with a great controller function you can use in blade files or in controllers! It will return a HTML element pointing the browser to the path of the cooked file along with a string to help the browser with the cache. When the application is running in `app.debug=false` a unix timestamp will be added to the end of the url that is requested. When the opposite is in effect a MD5 hash of the built file will be specified instead.

To use the helper simply include it like so:
```
@cooker('app.css')
@cooker('app.js')
```
You can substitute `app.css` and `app.js` for the cooked filename.

## Cooker Toolbelt
By default, Cooker includes a small Javascript file that is loaded and locked on page boot. The object is located at `window.cookerToolbelt` and contains the following tools at this point in time:
* `cookerToolbelt.version` returns the current toolbelt version
* `cookerToolbelt.isProd` returns a boolean of if the javascript file was built using Production mode
* `cookerToolbelt.autoRunIntelliPath` returns a boolean of if intelliPath can run
* `cookerToolbelt.cookerVersion` returns the current cooker version
* `cookerToolbelt.namespace` returns a string of the javascript namespace as defined in the oven

### Speedy Cook
Cooker can quickly build large libraries based on what needs to be changed. For example if you have 10 ovens but only made changes to a file in Oven 4, Oven 4 will be built whilst the others are skipped. We call this process Speedy Cooking. You can turn it off if you'd like to by heading to `config/cooker.php` and setting the `canSpeedyCook` boolean to `false`

### Getting started with Preloads
Preloads offer a great way of getting files from remote URLs or local URLs into your project. This could be useful if you had a file on a CDN you wanted to import to your project without the risk of depending on a remote URL going down and pulling your site with you. To get started head to `config/cooker.php` and consult the Oven array of your choosing.

Next, check the `preload` array. This will by default have an example of how to extend the preloads for this oven. To get started you can decide to load a file for both production and development mode or to distinguish between the two. This is handy if there is a production script you'd like to run but an uncompressed developer one you'd like to use locally to aide debugging.

If you'd like to distinguish between the two. Add a new array to the `preload` array specifying a `dev` and `prod` key to denote which file to load for which platform. For example:
```
[
    'dev' => 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',
    'prod' => 'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css'
]
```
If you don't want to distinguish between the two platforms (ie if you are happy to run a production ready asset locally), Simply specify a string with the target:
```
'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',
```
It's worth noting as well, that these values can be remote url's or local files. Simply alter as nessecary

### Building your own Oven
You can extend Cooker to process any input you give it! It could be something to meet your own needs more than the default Less or Scss compiler offers, Or if you want to do something that isn't supported out of the box, maybe something such as Styl etc you can do that by creating your own ovens. 

Ovens are simply controllers that process the given input files from the job array handed to it. Simply create a controller with a `public static function` of `cook` which accepts a `$job` parameter to get started.

You will also need to define what `$format` cooker is outputting to (This is either `css` or `js`) and what `$directory` cooker should look in for files to load.

Then all you need to do is `foreach` round each `$job['input']` and process them, that's literally it! Go nuts!

Follow this example to get going!

```
class Styl extends Controller
{
	public $format = 'css';
	public $directory = 'styl';
    
	public static function cook($job){
      $p = new fancyParser(); // Could be anything you want or use here!   
      foreach($job['input'] as $input){
          $p->parseFile(resource_path($this->directory.'/'.$input)); // process this specific input file
      }
      return $p->getCss(); // return the rendered content
  }
  public static function compress($input){
    // Compress the script if we are running in production mode
    return $input;
  }
}
```

### Cooker object-oriented javascript
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

### Cooked file compression
If your Laravel application is running in `config.debug=true` mode, any cooked files will retain their original formatting. If you are running in `config.debug=false` mode then all scripts except for javascript and css libraries will be minified to reduce load times

### Requirements for using Cooker
Cooker is happiest on:
* Laravel 10
* PHP >=8.2.0
