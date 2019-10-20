# 👨‍🍳 Cooker
## By Genericmilk

Cooker is a lightweight framework that allows you to quickly build LESS and Javascript files into a Laravel App. 

Resources that are built will be placed in the `/public/build` folder as `app.js` and `app.css` respectively. If the app is running in production mode the resources will be compressed and minified too! As if by magic.

### Installation

To install run
```
$ composer require genericmilk/cooker
```
This will install the requirements and it will publish the artisan command `build:res`.

### To cook resources
To cook, simply run the following command
```
$ php artisan build:res
```
This will compile any less and javascript files into `/public/build`. It's a good idea to add this folder to your `.gitignore` file. If a `/public/build` file does not exist it will be created when you run `build:res`

### Setting up javascript for cooking
Place a new `build.json` in `resources/js` with the following structure
```
[
    "app.js",
    "Folder/OtherScript.js",
    "Folder/OtherOtherScript.js"
]
```
It's ideal to reference app.js first. This file should contain the following example to get started
```
var App = {
  Greeting: 'Hello world',
  Boot: function(){
    alert(App.Greeting);
  }
};
```
The `App.Boot();` function will run on document ready and using the example an alert will show with the text; "Hello world". You can extend the `App` model in other scripts referenced in `build.json` by specifying them as such:
```
App.ExampleName = {
  Boot: function(){
    alert('Hello from other file');
  }
};
```
You can then call this script from the main `app.js` file as such;
```
var App = {
  Boot: function(){
    App.ExampleName.Boot();
  }
};
```
#### Changing the Javascript Namespace
By default, Cooker will attempt to run the `App.Boot();` function on document ready. If you'd prefer to use a custom name, Use the following command to publish the configuration file:
```
$ php artisan vendor:publish --provider="Genericmilk\Cooker\ServiceProvider"
```
Then in a text editor change the value to which ever you'd prefer.
```
'namespace' => 'App'
```
When you next run `php artisan build:res` it will instruct the `Boot()` function to run from the namespace of your chosing

#### Javascript Libraries
If you have custom libraries such as a slideshow plugin or jQuery, You can include them in the `resources/javascript/libraries` folder. Any scripts in this folder will be loaded in an alphabetical order, so if you'd prefer scripts to load before others, it's a good idea to name them accordingly. Scripts in the libraries folder will be added to the cooked javascript file without any compression changes before any app javascript code.

### Setting up LESS for cooking
Place a new `app.less` in `resources/less` with the following structure
```
@import "Folder/colours.less";
@import "Folder/mixins.less";
@import "styles.less";
```
You can then create these less files and folders accordingly. The LESS index file is used to collect all component files of LESS to build `/public/build/app.css`

#### CSS Libraries
If you have custom style libraries such as a slideshow or base styling from a theme etc, You can include them in the `resources/less/libraries` folder. Any stylesheets in this folder will be loaded in an alphabetical order, so if you'd prefer stylesheets to load before others, it's a good idea to name them accordingly. Stylesheets in the libraries folder will be added to the cooked css file without any compression changes before any app less styling.

### Cooked file compression
If your Laravel application is running in `APP_DEBUG=true` mode, any cooked files will retain their original formatting. If you are running in `APP_DEBUG=false` mode then all scripts except for javascript and css libraries will be minified to reduce load times

### Roadmap for the future
