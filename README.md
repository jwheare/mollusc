Mollusc is a browser for your Oyster pay as you go history. You can run it on your server or localhost.

You’ll get a bar chart and table breakdown of how your balance changed each month. Top ups are highlighted in green, fares above £3 in red. The £10 auto top-up threshold is shown as a red line.

Oyster only make 8 weeks data available at a time, but Mollusc will keep old data forever once you start importing it.

![Screenshot](https://raw.github.com/jwheare/mollusc/master/screenshot.png)

# Required

* Linux/Mac OS X
* Apache (2 probably?) with the rewrite module enabled
* MySQL (5 probably?)
* PHP 5.3

# Installation

1. Copy this directory somewhere on your machine where Apache can reach it.
   Some people use `/var/www` or something like that
1. Edit `app/conf/local.conf.php` with your details.
2. Rename it to the result of running `hostname` on your machine followed by `.conf.php`.
   e.g. if your system hostname is `rhubarb` the file needs to be called `rhubarb.conf.php`.
3. Edit `apache-vhost.conf`, changing all occurrences of `/path/to/install` to the path where you copied this directory. Set your `ServerName`. Make it password protected if you like.
4. Run `script/initdb.php` and answer the questions as prompted to setup the database.
5. Run `script/fetch.php` to import your journey history. You’ll probably want to put this in cron to run once a day or something.
6. Put `Include /path/to/install/apache-vhost.conf` somewhere appropriate to your OS Apache config and restart Apache.
7. Point your DNS at your webserver (or `/etc/hosts` file if you’re running it locally).
8. Enjoy!

# Configuration

To change the red fare warning threshold, edit the `FARE_WARNING` constant in `app/conf/conf.php`. It’s in pence.
