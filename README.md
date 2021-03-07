# 👨‍🍳 Cooker 4
## By Genericmilk

Cooker is a lightweight composer package that allows you to quickly build resources for the frontend of your application from languages that need to be parsed to whether you want to build your application using a bunch of smaller files. We call this parsing and combination effort "Cooking". 

Resources that are cooked will be placed in the `/public/build` folder where all resources will be rendered. If the app is running in production mode the resources will be compressed and minified too! As if by magic.

### So why Cooker? Why not NPM, Webpack? Laravel Mix?

Simple really! Trust me when I say I've tried all of them and the issue that can come from a rammed `node_modules` folder and issues with weird syntax and deploys can really raise the bar for entry. I set out to make a compilation system for my Laravel projects that was super approachable and integrated far more tightly with Laravel itself. The resulting package was Cooker. Cooker does everything you can want it to do and more by offering prebuilt renderers but allowing it to be extended with your own formats super easily.

Even if Cooker runs into an issue it'll show you what's specifically gone wrong in an easy to address format, it won't spew hot code messes over the screen and it's engineered to not kill the run if you have multiple jobs to do, allowing you to identify and fix issues.

Plus all code sent via cooker is automatically compressed and minified when running in `app.debug=false`!

Sounds good? Please do give it a try and offer feedback! I want to create the NPM that's lightyears more friendly for developers (Tall ask I know but hey!)

Cooker is used actively on https://quuu.co as well as https://socialchief.com and https://revively.co and we at Quuu trust it with our lives and livelyhoods (Our app is the reason we're in business!)

### Installing cooker

To install Cooker, run at the root of your Laravel Project the following
```
composer require genericmilk/cooker
php artisan cooker:setup
```

This will install the required supporting packages as well as cooker itself and it will also publish the artisan command `cooker:cook`.

*Please note that installing will overwrite the `sass` and `js` folders and everything that is inside them. So make sure you save any work back first because things will get deleted!*

### Configuring cooker
When you installed cooker it added a new configuration file called `cooker.php` to your laravel application's `config` directory.

It is within this gile that you can specify cooker's _ovens_ and how they should work to cook your build files.

Cooker works by defining ovens to process the files. Ovens can have multiple ingredients but only one output. For example you may have a script for billing and a script for a dropdown menu. You would want to combine these scripts to both be available on the output. 

Each oven processes the output files by doing the following
* Adds a timestamp to the head of the file for quick identification of when the last job ran. You can switch this off by setting `stamped` to `false` in the config file
* Cooker will then look for any global *libraries* automatically and in filename order from the `resources/<ovenDir>/libraries` folder where `<ovenDir>` is defined by the oven running. If you're using a built in oven supplied by Cooker this will be the lowercase of the oven name. If you are using your own consult *Building your own Oven* below. Libraries are loaded before anything else so it's a great idea to put frameworks in here such as jQuery or Vue etc. This is because the browser will need these loaded first and foremost before any app code is processed. Libraries are not compressed on build and as such minified versions are reccomended so that they are also production ready.
* Cooker will then download and attach any *preloads* specified. Preloads are the same as *libraries* but do not have to exist within one location and can be either a remote or local uri. Preloads are specified in the `preload` array in the configuration and can be a direct url or a full path to the resource file. Preloads are not compressed on build and as such minified versions are reccomended so that they are also production ready.

* Input files are then loaded in specified order from the job `input` array. These files are parsed using the oven loaded and are minified in production automatically. For example if you were using the `Genericmilk\Cooker\Ovens\Less` oven, you could reference files like below so colours and fonts load first. The base directory for reference is that which is supplied by the oven (eg: /resources/less/*)
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
{{cooker_resource('app.css')}}
{{cooker_resource('app.js')}}
```
You can substitute `app.css` and `app.js` for the cooked filename.

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
    alert(app.hey);
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

### Upgrading from Cooker 3.x.x
There are a few things you need to do to upgrade. Most of the work is done in the config file. It's most likely the best idea to backup your `cooker.php` file and translate over your custom changes as the newer version of the config file references ovens rather than pre-labeled jobs.

It's also worth noting that `frameworks` has been deprecated in replacement to `preloads`

### Upgrading from Cooker 2.x.x or 1.x.x
Boy howdy what an upgrade I have for you folkes coming from an older build of Cooker! Unfortunately there is some work involved to get started.

The first thing to do is to trash and reinstall your configuration file as the build scripts have changed for this version.
```
php artisan vendor:publish --provider="Genericmilk\Cooker\ServiceProvider"
```
Next, head to the new configuration file and create jobs as needed following the examples and the documentation to convert your application.

You will also need to convert all instances of the `Boot()` function to use the new lowercase `boot()` variant as scripts will call the new lowercase instead.

Finally, you can verify all has worked by running
`php artisan cooker:cook`

### Requirements for using Cooker
Cooker is happiest on:
* Laravel 8
* PHP >=7.3.0

### That's just the beginnin', folkes!
I am so so happy that so many of you are using Cooker and love it. Cooker recently got selected for GitHub's Arctic Code Vault project and it makes me so happy that Cooker will be there for the next 1,000 years! I am always improving things though, Got more neat feaures to help you cook even better files with greater control. Thanks for your support and reach out if you have a suggestion or need help!
