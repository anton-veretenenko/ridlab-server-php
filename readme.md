# Install libevent extension
## php < 6
    $ sudo apt install libevent-dev
    $ sudo pecl install libevent-beta

## php > 6, libevent < 2
    $ sudo apt install php-dev libevent1-dev re2c
    $ git clone https://github.com/expressif/pecl-event-libevent
    $ cd pecl-event-libevent
    $ sudo phpize
    $ sudo ./configure
    $ make
    $ sudo make install
    $ echo "extension=libevent.so" > libevent.ini
    $ sudo mv libevent.ini /etc/php/7.2/mods-available/
    $ sudo ln -s /etc/php/7.2/mods-available/libevent.ini /etc/php/7.2/cli/conf.d/20-libevent.ini

# Run
    $ git clone https://github.com/anton-veretenenko/ridlab-server-php
    $ cd ridlab-server-php
	$ php ridlab-server.php

# Description
Server will open 8181 port on localhost(127.0.0.1) and accept GET requests. It will serve files from **files** directory in the root of **ridlab-server.php** file directory. Example files included.