# 👨‍🍳 Cooker 6
## By Genericmilk

Cooker is a lightweight composer package aimed at sitting tightly within Laravel applications that allows you to quickly build resources for the frontend of your application from languages that need to be parsed to whether you want to build your application using a bunch of smaller files. We call this parsing and combination effort "Cooking". 

Resources that are cooked will be placed in the `/public/build` folder where all resources will be rendered. If the app is running in production mode the resources will be compressed and minified too! As if by magic.

### Installing Cooker in a new project

To install Cooker, run at the root of your Laravel Project the following
```
composer require genericmilk/cooker
php artisan cooker:init
```

This will install the required supporting files as well as cooker itself. It also installs the required files to your application for package management as well as some example files to get started

*Please note that installing will overwrite the `sass` and `js` folders inside of `/resources` as well as everything that is inside them. Please make sure you save any work back first because things will get deleted!*

### So why Cooker? Why not NPM, Webpack? Laravel Mix?

Simple really! Trust me when I say I've tried all of them and the issue that can come from a rammed `node_modules` folder and issues with weird syntax and deploys can really raise the bar for entry. I set out to make a compilation system for my Laravel projects that was super approachable and integrated far more tightly with Laravel itself. The resulting package was Cooker. Cooker does everything you can want it to do and more by offering prebuilt renderers but allowing it to be extended with your own formats super easily.

Even if Cooker runs into an issue it'll show you what's specifically gone wrong in an easy to address format, it won't spew hot code messes over the screen and it's engineered to not kill the run if you have multiple jobs to do, allowing you to identify and fix issues.

Plus all code sent via cooker is automatically compressed and minified when running in `app.debug=false`!

Sounds good? Please do give it a try and offer feedback! I want to create the NPM that's lightyears more friendly for developers (Tall ask I know but hey!)

Cooker is used actively on https://quuu.co as well as https://rasdio.co.uk and we at Quuu trust it with our lives and livelyhoods (Our app is the reason we're in business!)

### Configuring cooker
When you installed cooker it added a new configuration file called `cooker.php` to your laravel application's `config` directory.

It is within this file that you can specify cooker's _ovens_ and how they should work to cook your build files.

Cooker works by defining ovens to process the files. Ovens can have multiple ingredients but only one output. For example you may have a script for billing and a script for a dropdown menu. You would want to combine these scripts to both be available on the output. 

Each oven processes the output files by doing the following
* Adds a timestamp to the head of the file for quick identification of when the last job ran. You can switch this off by setting `stamped` to `false` in the config file
* Cooker will obtain any packages in cooker.json using the NPM network. To consult adding new packages to your project or to learn more, please consult [adding packages to cooker](#adding-packages-to-cooker)
* Cooker will download or locate and attach any *preloads* specified. Preloads are specified in the `preload` array in the configuration and can be a direct url or a full path to the resource file. Preloads can be offered either as a string to load on both production and local versions of your build or an array to specify the differences between the two. Preloads are not compressed on build, so it is reccomend you use a minified version in production environments. To learn more about Preloads, please consult [getting started with Preloads](#getting-started-with-preloads)
* Cooker will then look for any *libraries* automatically and in filename order from the `resources/<ovenDir>/libraries` folder where `<ovenDir>` is defined by the oven running. If you're using a built in oven supplied by Cooker this will be the lowercase of the oven name. If you are using your own consult [Building your own Oven](#building-your-own-oven). Libraries are loaded after preloads so it's a great idea to put singular scripts in this folder to get loaded before your AppCode. Libraries are not compressed on build and as such minified versions are reccomended so that they are also production ready.
* Cooker will finally process your AppCode. Input files are then loaded in specified order from the job `input` array. These files are parsed using the oven loaded and are minified in production automatically. For example if you were using the `Genericmilk\Cooker\Ovens\Less` oven, you could reference files like below so colours and fonts load first. The base directory for reference is that which is supplied by the oven (eg: /resources/less/*)
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

### The Cooker helper
Cooker comes with a great controller function you can use in blade files or in controllers! It will return a HTML element pointing the browser to the path of the cooked file along with a string to help the browser with the cache. When the application is running in `app.debug=false` a unix timestamp will be added to the end of the url that is requested. When the opposite is in effect a MD5 hash of the built file will be specified instead.

To use the helper simply include it like so:
```
{!! cooker_resource('app.css') !!}
{!! cooker_resource('app.js') !!}
```
You can substitute `app.css` and `app.js` for the cooked filename.

### The Cooker Toolbelt
Starting with Cooker 5, Cooker by default includes a small Javascript file that is loaded and locked on page boot. The object is located at `window.cookerToolbelt` and contains the following tools at this point in time:
* `cookerToolbelt.version` returns the current toolbelt version
* `cookerToolbelt.isProd` returns a boolean of if the javascript file was built using Production mode
* `cookerToolbelt.cookerVersion` returns the current cooker version
More options are coming soon to this toolbelt to aide development.

### Speedy Cook
Starting with Cooker 5, Cooker can now quickly build large libraries based on what needs to be changed. For example if you have 10 ovens but only made changes to a file in Oven 4, Oven 4 will be built whilst the others are skipped. We call this process Speedy Cooking. You can turn it off if you'd like to by heading to `config/cooker.php` and setting the `canSpeedyCook` boolean to `false`

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

### Adding packages to Cooker

Starting with Cooker 6, You can now use a NPM repository such as `unpkg` or `jsdelivr` to really quickly get files into your project. Right now we only support downloading the most up-to-date version of this environment with the default file being referenced. And whilst we will compress it for you on production, this may not be a specific production build. This will be updated in a 6.x update soon.

If you are upgrading from an older version of Cooker, You need to ensure you have a `cooker.json` file in the root of your project file as follows:
```
{
    "packages": {
    }
}
```
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

### Building your own Oven
Starting with Cooker 4, You can extend Cooker to process any input you give it! It could be something to meet your own needs more than the default Less or Scss compiler offers, Or if you want to do something that isn't supported out of the box, maybe something such as Styl etc you can do that by creating your own ovens. 

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
  boot: function(){
    alert(this.hey);
  }
};
```
In this example `app.boot();` is called on document ready and an alert will fire containing the string stored in `app.hey` which has been set to "Hello world". Pretty cool right?!

You can extend the `app` object in other scripts referenced in the cooker's job under `input`.

To extend your script simply create a sub-object to the top level namespaced object by specifying it as such:
```
app.anotherObject = {
  boot: function(){
    alert('Hello from other file');
  }
};
```
You can then call this script from the main object at the start of your cook as such;
```
var app = {
  boot: function(){
    app.anotherObject.boot();
  }
};
```
This'll fire an alert with `Hello from other file` as the function is executed inside `app.anotherObject.boot()`

### Cooked file compression
If your Laravel application is running in `config.debug=true` mode, any cooked files will retain their original formatting. If you are running in `config.debug=false` mode then all scripts except for javascript and css libraries will be minified to reduce load times

### Requirements for using Cooker
Cooker is happiest on:
* Laravel 9
* PHP >=8.2.0
