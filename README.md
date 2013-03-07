PHP Debug Tools
===============

A collection of simple tools for inline debugging.

- debug($var)
    pretty-print a variable
    escapes HTML output

- dump($var)
    pretty-print a variable and halt execution
    escapes HTML output

- dd($var1, $var2, $var3, ...)
    pretty print multiple variables
    escapes HTML output

- desc($obj)
    describe and object, and show its methods

- jpr($javascript)
    pretty print a javascript string

INSTALLATION
------------

Install the script

    # install src/php-debug-tools.php /usr/share/php/php-debug-tools.php

Add the following line to your php.ini

    auto_prepend_file = "php-debug-tools.php"

Restart apache

    # /etc/init.d/apache2 restart



