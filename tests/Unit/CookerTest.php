<?php

namespace Genericmilk\Cooker\Tests\Unit;

use Genericmilk\Cooker\Build\Manifest;
use Genericmilk\Cooker\Build\Recipe;
use Genericmilk\Cooker\Packages\CookerJson;
use Genericmilk\Cooker\Toolbox\Platform;
use Genericmilk\Cooker\Toolbox\Toolbox;

test('Recipe detects script and style outputs from extension', function () {
    $js  = new Recipe('app.js',  'resources/js/app.js',  '/abs/resources/js/app.js');
    $css = new Recipe('app.css', 'resources/css/app.css', '/abs/resources/css/app.css');

    expect($js->isScript())->toBeTrue();
    expect($js->isStyle())->toBeFalse();
    expect($css->isStyle())->toBeTrue();
    expect($css->isScript())->toBeFalse();
});

test('Manifest persists and reads entries', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'cooker').'.json';
    @unlink($tmp);

    $m = new Manifest($tmp);
    $m->set('app.js', 'app-abc123.js');
    $m->save();

    $reread = new Manifest($tmp);
    expect($reread->get('app.js'))->toBe('app-abc123.js');
    expect($reread->get('missing.js'))->toBeNull();

    @unlink($tmp);
});

test('CookerJson tracks packages and stacks', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'cooker').'.json';
    @unlink($tmp);

    $j = new CookerJson($tmp);
    $j->setPackage('react', '18.2.0');
    $j->addStack('react');
    $j->save();

    $reread = new CookerJson($tmp);
    expect($reread->packages())->toMatchArray(['react' => '18.2.0']);
    expect($reread->stacks())->toContain('react');

    $reread->removePackage('react');
    $reread->removeStack('react');
    $reread->save();

    $third = new CookerJson($tmp);
    expect($third->packages())->toBeEmpty();
    expect($third->stacks())->toBeEmpty();

    @unlink($tmp);
});

test('Platform reports os and arch', function () {
    expect(Platform::os())->toBeIn(['darwin', 'linux', 'win32']);
    expect(Platform::arch())->toBeIn(['arm64', 'x64']);
});

test('Toolbox builds esbuild and tailwind helpers', function () {
    $tb = new Toolbox(sys_get_temp_dir(), [
        'path'     => 'cooker-test-bin',
        'esbuild'  => '0.24.2',
        'tailwind' => '4.0.0',
    ]);
    expect($tb->esbuild()->path())->toContain('cooker-test-bin');
    expect($tb->tailwind()->path())->toContain('cooker-test-bin');
});
