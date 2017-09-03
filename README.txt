DEPENDENCIES: npm, libzmq, composer

Ok first things first. You should install ext-zmq for php.

$ pecl install ext-zmq

OR

Windows has some binaries you can download and put in your php bin directory yourself.
(http://pecl.php.net/package/zmq) the Links say "DLL". I used 1.1.3, and I placed
php_zmq.dll into C:/wamp64/bin/php/php7.0.10/ext
and
libzmq.dll and libsodium.dll into C:/wamp64/bin/php/php7.0.10/

Make sure the extension is loaded by php.ini, then install source.

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

Now the program should load in a browser. The only console error I saw was that manifest.json was not found. I think there is a better way to set up the vhost config so that I don't need to use php::read_file to serve the main html page.

The idea behind this is to have get/post controllers for the top level objects (dormatories) of which there should be two. (These controllers are not working right now, and Silex is only allowing GET / for some reason). The front end is listening to a websocket that will post updates every time one gets made, so that if someone else alters the page you should see the update as soon as possible.

I also wanted to have a dropdown of students that could be chosen for each bedroom space. But since my Silex controllers are giving me issues, I cannot write the reactjs code.

Thank you for the fun project, and opportunity.
