DEPENDENCIES: php, apache, mysql, npm, libzmq, composer

Note: Or there is also a Docker environment, see at the bottom of this file.

Ok first things first. You should install ext-zmq for php.

$ pecl install ext-zmq

OR

Windows has some binaries you can download and put in your php bin directory yourself.
(http://pecl.php.net/package/zmq) the Links say "DLL". I used 1.1.3, and I placed
php_zmq.dll into C:/wamp64/bin/php/php7.0.10/ext
and
libzmq.dll and libsodium.dll into C:/wamp64/bin/php/php7.0.10/

Make sure the extension is loaded by php.ini, then install source.

Install environment variables

$ cp dev.env
$ cp www/js/housing-app/dev.env www/js/housing-app/.env

$ composer install

Then you will want to get the database started.

$ cd application
$ php console.php migrations:migrate

That should put all the tables in place.

We will alter the virtualhost file for apache so that static files can be served seperately, and a websocket is proxied. Add the following virtualhost definition, with c:/sample replaced by the file's location.

Make sure mod_proxy and mod_proxy_wstunnel are enabled for apache.

<VirtualHost *:80>

	ServerName sample

	ProxyPass /websocket "ws://127.0.0.1:25569"
	ProxyPassReverse /websocket "ws://127.0.0.1:25569"

	Alias /static "c:/sample/www/build/static/"  
	<Directory "c:/sample/www/build/static/">
		Options +Indexes  
		AllowOverride None  
		Order allow,deny
		Allow from all
	</Directory>

        DocumentRoot "c:/sample"
	<Directory  "c:/sample/">
		Options +Indexes +Includes +FollowSymLinks +MultiViews
		AllowOverride All
		Require local
		DirectoryIndex www/index.php
	</Directory>
</VirtualHost>


Restart apache.

$ apachectl restart

Next you will build the front end app.
NOTE: This step is only necessary after making changes inside housing-app. Current build is tracked.

$ cd www/js/housing-app
$ npm install
$ npm run build
$ cp -rf build ../..

Finally you should start a websocket for the front end to listen to, and the back end to publish to.

$ cd application
$ php websocket.php

The idea behind this is to have get/post controllers for the top level objects (dormatories) of which there should be two. I also wanted to have a dropdown of students that could be chosen for each bedroom space. This is a great use case for react, and I am saving this project so that I can work on reactjs elements.

Database changes should be persisted from the top down, so if someone changes the dormatory object and saves it, each bedroom and student will also change. The parts that remain of the assignment are; to make sure 50% of dorms are filled when a user clicks save, and validation to make sure there is only one gender per suite.

Thank you for the fun project, and opportunity.

## Docker installation

You can use docker and docker-compose to install and run the project:

``` bash
git clone git@github.com:bflick/silex-sample.git
cd silex-sample/

make
```

Then you have access to:

- http://0.0.0.0:8483/ : The project, web interface
- http://0.0.0.0:8481/ : PHPMyAdmin (user: root / pass: root)
- http://0.0.0.0:8480/students : The RestApi
