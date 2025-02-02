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

⚠️ *Before you install Cooker, please make a note of the following "gotchas"*

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

When you ran `php artisan cooker:install`, It published a `cooker.php` file to your Laravel application's `/config` directory.

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

## Cooker Routes

Javascript files add an additional array to the oven components array called `routes`. In `routes` you configure which classes are loaded based on which page of the application is hit.

The default routeset is as follows:
```
    'file' => 'app.js',

    'components' => [
        'parse' => [
            'app.js'
        ],
        'routes' => [
            [
                'path' => '*',
                'class' => 'Application',
            ]
        ]
    ]
```
Here, the class `Application` is created when any page is hit. Cooker can use wildcards or absolute path matching to make this happen. You can also have multiple instances of the same route for different classes, eg:
```
  'routes' => [
      [
          'path' => '*',
          'class' => 'Application',
      ],
      [
          'path' => 'billing/show',
          'class' => 'Billing',
      ], 
      [
          'path' => '*',
          'class' => 'Menu',
      ],            
      [
          'path' => 'about-us/*',
          'class' => 'About',
      ],                  
  ]
```
So in this example; `new Application()` is called on any page along with `new Menu()` where `new Billing()` is called on `site.com/billing/show` only and `new About()` is called on `site.com/about-us` and any sub-page.

For Cooker routes to work, You need to import the `cooker-routes` package into your javascript like so:
```
import cookerRoutes from 'cooker-routes';
```
You must also make the class available to the window object by adding the following at the bottom of the file
```
window.Application = Application; // Substitute Application to the class in your script.
```

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
Cooker uses the ESM run NPM framework to really quickly get code into your project. Because of this framework you don't need to install anything, Just import the package from its NPM package name.

To install jQuery into your script, Add the import as follows:
```
import jquery from 'jquery';
```

Packages you install from Cooker are imported as `import name from 'name'` which will auto-complete on render to the local instance of the file.

If you want to import your own javascript files into your application, residing in the `/resources/js/imports` folder, Use the following syntax:
```
import myscript from '@/myscript.js'
```

***

## The cooker-toolbelt import
By default, Cooker includes a small Javascript file that can be imported into your script.

Cooker toolbelt is a read-only object with the following information and features available 
* `cookerToolbelt.name` returns "Cooker Toolbelt"
* `cookerToolbelt.version` returns the toolbelt version
* `cookerToolbelt.description` returns "The assistant for the Cooker framework"
* `cookerToolbelt.isDebug` returns a boolean of whether or not Laravel is running in debug mode
* `cookerToolbelt.console` A console replacement that only displays messages if Laravel is in debug mode. You can use it by appending `cookerToolbelt.` to the front of your `console` statements (ie `cookerToolbelt.console.log('Hello world')`)

You can import cooker-toolbelt into your application by adding the following import statement to the top of your application script
```
import cookerToolbelt from 'cooker-toolbelt';
```

## Development and Production mode
Cooker will automatically compress both `css` and `js` files depending on the value of `config('app.debug')`. You can override this setting by changing the `options.alwaysCompress` value. The quickest way to achieve this is by setting a `COOKER_ALWAYS_COMPRESS` value to `true` in your application env file

## Cache
Cooker will automatically save resources by building a file hash tree, only generating fresh resources if they have changed. If you want to override this setting, change the `options.disableCache` value. The quickest way to achieve this is by setting a `COOKER_DISABLE_CACHE` value to `true` in your application env file

## Requirements for using Cooker
Cooker 8 is happiest on:
* Laravel 10
* PHP >=8.3
