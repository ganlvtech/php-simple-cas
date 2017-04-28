# php-simple-cas

A very simple CAS client. Only used to get user.

## Installation

```bash
composer require ganlvtech/php-simple-cas
```

## Usage

```php
<?php
use PhpSimpleCas\PhpCas;

require './vendor/autoload.php';

$phpCas = new PhpCas('https://cas.xjtu.edu.cn/');

if (isset($_GET['logout'])) {
    $phpCas->logout();
}

if (isset($_GET['login'])) {
    $phpCas->forceAuthentication();
}

$auth = $phpCas->checkAuthentication();
if ($auth) {
    echo $phpCas->getUser();
} else {
    echo 'Guest mode';
}
```

## How to test

Add your service domain to `hosts`
```text
127.0.0.1 service.example.com
``` 

## LICENSE

    The MIT License (MIT)
    
    Copyright (c) 2017 Ganlv
    
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
