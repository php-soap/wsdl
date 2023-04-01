# WSDL

This package provides tools and helpers for dealing with WSDLs.

# Want to help out? 💚

- [Become a Sponsor](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#sponsor)
- [Let us do your implementation](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#let-us-do-your-implementation)
- [Contribute](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#contribute)
- [Help maintain these packages](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#maintain)

Want more information about the future of this project? Check out this list of the [next big projects](https://github.com/php-soap/.github/blob/main/PROJECTS.md) we'll be working on.

# Installation

```bash
composer require php-soap/wsdl
```

## WSDL Loader

A WSDL loader is able to load the contents of a WSDL file.

### Psr18Loader

For loading WSDL's, you might want to use a PSR-18 client to do the hard HTTP work.
You'll have to include the [php-soap/psr18-transport](https://github.com/php-soap/psr18-transport/#psr18loader) in order to use this loader:

```sh
composer require php-soap/psr18-transport
```

```php
use Http\Client\Common\PluginClient;
use Soap\Psr18Transport\Wsdl\Psr18Loader;

$loader = Psr18Loader::createForClient(
    $wsdlClient = new PluginClient(
        $psr18Client,
        ...$middleware
    )
);

$contents = $loader('http://some.wsdl');
```


### StreamWrapperLoader

Loads the content of a WSDL by using any of the enabled [stream wrappers](https://www.php.net/manual/en/wrappers.php).
It can either be a file, http, ...

```php
use Soap\Wsdl\Loader\StreamWrapperLoader;

$loader = new StreamWrapperLoader(
    stream_context_create([
        'http' => [
            'method' => 'GET',
            'header'=>"User-Agent: my loader\r\n",
        ],        
    ])
);
$contents = $loader($wsdl);
```

### FlatteningLoader

This loader can be used if your WSDL file contains WSDL or XSD imports.
It will any other loader internally to load all the parts.
The result of this loader is a completely flattened WSDL file which you can e.g. cache on your local filesystem.

```php
use Soap\Wsdl\Loader\FlatteningLoader;
use Soap\Wsdl\Loader\StreamWrapperLoader;

$loader = new FlatteningLoader(new StreamWrapperLoader());

$contents = $loader($wsdl);
```

### CallbackLoader

This loader can be used if you want to have more control about how to load a WSDL.
It can be used to decorate another loader, add debug statements, apply custom loading logic, ...

```php
use Soap\Wsdl\Loader\CallbackLoader;

$loader = new CallbackLoader(static function (string $location) use ($loader, $style): string {
    $style->write('> Loading '.$location . '...');

    $result =  $loader($location);
    $style->writeln(' DONE!');

    return $result;
})

$contents = $loader($wsdl);
```

## WSDL CLI Tools

```
wsdl-tools 1.0.0

Available commands:
  completion  Dump the shell completion script
  flatten     Flatten a remote or local WSDL file into 1 file that contains all includes.
  help        Display help for a command
  list        List commands
  validate    Run validations a (flattened) WSDL file.
```

### Flattening

```
./bin/wsdl flatten 'https://your/?wsdl' out.wsdl
```

This command will download the provided WSDL location.
If any imports are detected, it will download these as well.
The final result is stored in a single WSDL file.

### Validating

```
./bin/wsdl validate out.wsdl
```

This command performs some basic validations on the provided WSDL file.
If your WSDL contains any imports, you'll have to flatten the WSDL into a single file first.

### Extensions

By installing additional packages from `php-soap`, additional commands will be added to the WSDL tools:

* [wsdl-reader](https://github.com/php-soap/wsdl-reader): Will install inspect commands that will give you a human-readable version of all information inside your WSDL. 

### Custom WSDL Loader

By default, all CLI tools use the StreamWrapperLoader.
All CLI tools have a `--loader=file.php` option that can be used to apply a custom WSDL loader.
This can be handy if your WSDL is located behind authentication or if you want to get control over the HTTP level.

Example custom PHP loader:

```php
<?php

use Soap\Wsdl\Loader\StreamWrapperLoader;

return new StreamWrapperLoader(
    stream_context_create([
        'http' => [
            'method' => 'GET',
            'header'=> sprintf('Authorization: Basic %s', base64_encode('username:password')),
        ],        
    ])
);
```

## WSDL Validators

This package contains some tools you can use to validate the format of WSDL files.
It uses the power of [veewee/xml DOM Validators](https://github.com/veewee/xml/blob/master/docs/dom.md#validators) internally.

### SchemaSyntaxValidator

Validates all defined schemas and returns a list of all issues.

```php
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\Wsdl\Xml\Validator;
use VeeWee\Xml\Dom\Document;

$wsdl = Document::fromXmlString((new StreamWrapperLoader())($file));

echo "Validating Schemas".PHP_EOL;
$issues = $wsdl->validate(new Validator\SchemaSyntaxValidator());
echo ($issues->count() ? $issues->toString() : '🟢 ALL GOOD').PHP_EOL;
```

### WsdlSyntaxValidator

Validates the WSDL file and returns a list of all issues.

```php
use Soap\Wsdl\Loader\StreamWrapperLoader;
use Soap\Wsdl\Xml\Validator;
use VeeWee\Xml\Dom\Document;

$wsdl = Document::fromXmlString((new StreamWrapperLoader())($file));

echo "Validating WSDL:".PHP_EOL;
$issues = $wsdl->validate(new Validator\WsdlSyntaxValidator());
echo ($issues->count() ? $issues->toString() : '🟢 ALL GOOD').PHP_EOL;
