# 👨‍🍳 Cooker 3
## By Genericmilk

Cooker is a lightweight framework that allows you to quickly build Less and Javascript files for a Laravel 7/8 Application

Resources that are built will be placed in the `/public/build` folder where all resources will be rendered. If the app is running in production mode the resources will be compressed and minified too! As if by magic.

### Installing cooker

To install Cooker, run at the root of your Laravel Project
```
$ composer require genericmilk/cooker
```
This will install the required supporting packages as well as cooker itself and it will also publish the artisan command `build:res`.

When you use cooker for the first time it will overwrite the `sass` and `js` folders and everything that is inside them. So make sure you save any work back first because things will get deleted!

### Running cooker (and installing if it is your first bake!)

The best way to get started with cooker is to run the cook command which will automatically do everything involved for setup as well as creating dummy files for getting up and running fast as well as publishing the configuration needed for telling Cooker what to do.

To run cooker simply run the following artisan command from your project root.
```
php artisan build:res
```
This will build all configured files into their counterpart "cooked" files within `/public/build/` as well as updating any frameworks that are out of date.

### Configuring cooker
When you run cooker for the first time it'll add a new configuration file called `cooker.php` to your laravel application's `config` directory.

It is within this folder that you can specify how cooker should work to create your build files.

Cooker works in the following order on both Less files and Javascript files.
* Downloads and attaches any "framework" specified. This is specified in the `frameworks` array in the configuration, a full list of which frameworks can be included below. Framework that are loaded from frameworks array are not compressed on build and as such minified versions will be downloaded.
* Looks for any global libraries automatically and in filename order from the `resources/less/libraries` or `resources/less/libraries` folder. Libraries are the same as frameworks but are stored locally. Just like frameworks these libraries are not compressed on build so it is a good idea to ensure that minified production assets are added to this folder
* Loads any build specific library from the job `libraries` array. These are the exact same as global libraries but can be specified from another build target per cook job. (For example you may want Wakenbake; another Genericmilk plugin only on the javascript file which is cooked for users who are logged out etc)
* Input files are then loaded in specified order from the job `input` array. These files are parsed using the less / javascript parser and are minified in production automatically.
* The cooked file is published to the `public/build` folder under the name specified in the jobs' `output` string. (Default app.css or app.js etc)
 
The first port of call is to configure Cooker as you transition to using it or before you start your project. That way things are in a great position for you to escalate and build upon cooker tasks (aka Cooker *jobs*) as you go. 

Here are the parameters and what they accept!

*namespace* is the first parameter which accepts a string. This tells cooker where to initialise your Cooker object-oriented javascript which you can learn about below. It's a good idea to pick a one-word namespace and stick with it as changing it will stop your javascript files from initialising. So for example if the app I was building was a game, I could call the namespace `coolgame` and subsequently every function and string would stem from `coolgame` e.g. `coolgame.boot();, coolgame.object.player.name;` etc

*build_stamps* contains an array with 2 values inside, one for css and one for javascript. When you build files, cooker will place a build notice at the top of the rendered file indicating when the file was last built etc. You can switch this off for each type of file if you do not want this by setting the flag to `false`

*frameworks* contains a simple array of frameworks you want cooker to download and use. You can get the list of these and more information about frameworks below.

*less/js* these are where the cooking magic happens! these contain arrays seperated by commas which you can specify things you want cooker to build. So if you need more than one css or javascript file, simply copy and paste the example provided and seperate with a comma between the square brackets of the array.

Inside of these job arrays you can specify the following:

*libraries* are files you do not want to be parsed/processed and included before main application code. File paths start in the `resources/<type>/` folder and each file referenced will be loaded and inserted.

*input* contains the files in order you wish to be processed by the parser. For less builds this will parse less syntax into css and javascript will utilise the Cooker object-oriented javascript system. Both arrays will be compressed after processing when running in production so it is a good idea for your application code to live in here.

*output* is a string of the name of the file that is going to be built for example `app.css`, `app.js` or even something custom such as `mygame.js`

*silent* is by default set to false. This will result in cooker progress being outputted to the command-line. If you would prefer cooker to only display error messages and nothing else, switch this to `true`.


### Frameworks
Frameworks are a fantastic way of getting common frameworks such as vue.js, jQuery or Tailwind into your application super quickly and compressed into the same build file. Right now this is super limited but we'll be rolling out way more options down the line for valid frameworks!

Right now you can specify the following frameworks in the `frameworks` array within the cooker configuration in order to add them to your build files. Cooker knows which type of files to add them to and they are included globally (Specifying `bootstrapcss` will add Bootstrap 4 to all css files built using cooker)

Here are the frameworks we support:
* `vue` (Installs Vue.js into all js files)
* `jquery` (Installs jQuery into all js files)
* `swal2` (Installs Sweetalert2 into all js files)
* `bootstrap-css` (Installs Bootstrap into all css files)
* `bootstrap-js` (Installs Bootstrap into all js files)
* `tailwind` (Installs Tailwind into all css files)

Frameworks are downloaded and stored in the laravel application's cache for 1 month. These files will get redownloaded at the end of this period or when you use the `php artisan cache:clear` command and next cook.

### The Cooker helper
Cooker comes with a great controller function you can use in blade files or in controllers! It will return a string pointing the browser to the path of the cooked file along with a string to help the browser with the cache. When the application is running in `APP_DEBUG=false` a unix timestamp will be added to the end of the url that is requested. When the opposite is in effect a MD5 hash of the built file will be specified instead.

To use the helper simply include it like so:
```
<link href="{{Genericmilk\Cooker\Cooker::helper('app.css')}}" rel="stylesheet">
<script src="{{Genericmilk\Cooker\Cooker::helper('app.js')}}"></script>
```
You can substitute `app.css` and `app.js` for the cooked filename.

If you need to use it in a controller, simply do the following:
```
use Genericmilk\Cooker;

return Cooker::helper('app.js');
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
If your Laravel application is running in `APP_DEBUG=true` mode, any cooked files will retain their original formatting. If you are running in `APP_DEBUG=false` mode then all scripts except for javascript and css libraries will be minified to reduce load times

### Upgrading from Cooker 2.x.x or 1.x.x
Boy howdy what an upgrade I have for you folkes coming from an older build of Cooker! Unfortunately there is some work involved to get started.

The first thing to do is to trash and reinstall your configuration file as the build scripts have changed for this version.
```
php artisan vendor:publish --provider="Genericmilk\Cooker\ServiceProvider"
```
Next, head to the new configuration file and create jobs as needed following the examples and the documentation to convert your application.

You will also need to convert all instances of the `Boot()` function to use the new lowercase `boot()` variant as scripts will call the new lowercase instead.

Finally, you can verify all has worked by running
`php artisan build:res`

### Requirements for using Cooker
Cooker is happiest on:
* Laravel 7/8
* PHP >=7.3.0

### That's just the beginnin', folkes!
I am so so happy that so many of you are using Cooker and love it. Cooker recently got selected for GitHub's Arctic Code Vault project and it makes me so happy that Cooker will be there for the next 1,000 years! I am always improving things though, Got more neat feaures to help you cook even better files with greater control. Thanks for your support and reach out if you have a suggestion or need help!