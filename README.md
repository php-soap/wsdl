# WSDL

This package provides tools and helpers for dealing with WSDLs.


## Installation

```bash
composer require php-soap/wsdl
```

## WSDL Loader

A WSDL loader is able to load the contents of a WSDL file.

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
