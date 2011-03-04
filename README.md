Mollusc is a browser for your Oyster pay as you go history. You can run it on your server or localhost.

# Required

* MySQL
* PHP 5.3

# Installation

1. Copy this directory somewhere on your machine where Apache can reach it.
   Some people use `/var/www` or something like that
1. Edit app/conf/local.conf.php with your details.
2. Rename it to the result of running `hostname` on your machine followed by `.conf.php`.
   e.g. if your system hostname is `rhubarb` the file needs to be called `rhubarb.conf.php`.
3. Edit `apache-vhost.conf`, changing all occurrences of `/path/to/install` to the path where you copied this directory. Set your `ServerName`. Make it password protected if you like.
4. Run `script/initdb.php` and answer the questions as prompted to setup the database.
5. Run `script/fetch.php` to import your journey history. You’ll probably want to put this in cron to run once a day or something.
6. Put `Include /path/to/install/apache-vhost.conf` somewhere in your Apache config and restart
7. Point your DNS at your webserver (or /etc/hosts file if you’re running it locally).
8. Enjoy!
