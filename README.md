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

### To setup javascript
Place a new `build.json` in `resources/js` with the following structure
```
[
    "app.js",
    "Folder/OtherScript.js",
    "Folder/OtherOtherScript.js"
]
```

It's ideal to put an app.js first. This should contain the following example to get started
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

### To cook
To run, simply execute
```
$ php artisan build:res
```
