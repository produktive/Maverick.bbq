# Maverick.bbq

Display charts and live cook data from your barbecue on the web. This program requires a specific digital thermometer: The [Maverick ET-732](https://a.co/d/69JztoX). This also requires some specific hardware. You can use whatever microcomputer you like, this was developed on a [Raspberry Pi](https://www.raspberrypi.com/) Zero W and a [433 MHz radio frequency receiver](https://a.co/d/5NI6uH5) module.
## Features

 - Charts
 - Cook stats
 - Email alerts
 - Push alerts (with [Pushover](https://pushover.net) account)

## Preview

![Preview](https://i.imgur.com/ARQeb9k.png)![Preview](https://i.imgur.com/gwGJIyh.png)![Preview](https://i.imgur.com/DwpoN1P.png)
![Preview](https://i.imgur.com/DhPqIZW.png)
## Installation
Software:  
Web server: nginx  
Database: - sqlite3  
Interface - php7-fpm  
433mhz sniffer/parser - C

Hardware:  
[Raspberry Pi](https://www.raspberrypi.com/)
[433 MHz radio frequency receiver](https://a.co/d/5NI6uH5)

GPIO Pinout (physical pin numbers):  
2 (5v) - to 5v pin on receiver  
34 (GND) - to GND on receiver  
31 (BCM15) - to DATA on receiver

Install steps as of January 7, 2024:

1.  `sudo apt install git`
2.  `git clone [https://github.com/produktive/Maverick.bbq](https://github.com/produktive/Maverick.bbq)`
3.  Install PIGPIO (see [http://abyz.me.uk/rpi/pigpio/download.html](http://abyz.me.uk/rpi/pigpio/download.html))  
    c. `wget abyz.me.uk/rpi/pigpio/pigpio.zip`
    d. `unzip pigpio.zip`  
    e. `cd PIGPIO`  
    f. `make`  
    g. `sudo make install`
4.  `sudo apt install nginx libsqlite3-dev sqlite3 php-fpm php-curl`
5.  Configure for nginx: either paste from `bbq/maverick.bbq` or simply: 
`sudo cp ~/Maverick.bbq/bbq/maverick.bbq /etc/nginx/sites-available/default`
7.  Copy maverick html files to nginx web root  
    a. `cd ~/Maverick.bbq/html`
    b. `sudo cp -r * /var/www/html`
8.  Create the database  
    a. `cd ~/Maverick.bbq/bbq`
    b. `sudo sqlite3 -init ./db.script the.db`
    c. `.fullschema` to verify the db  
    d. `.quit` to exit
9.  Build the maverick executable  
    a. `sudo gcc -o maverick maverick.c -lpigpio -lsqlite3`
10.  Enable nginx user www-data to execute and kill maverick executable  
    a. `sudo visudo`  
    b. Add `www-data ALL=(ALL) NOPASSWD: /var/www/bbq/maverick.sh, /bin/kill` as last line
11.  Set ownership/permissions on /var/www/html and /var/www/bbq directory and contents  
    a. sudo chown -R $USER:www-data /var/www/bbq
    b. sudo chown -R $USER:www-data /var/www/html
12. Enter IP/hostname of your Raspberry Pi in a web browser. Login with default password `password` and change it after login.

## Notes
Only Gmail has been tested with the email alerts since I don't have accounts with the other providers. If you use a different provider and it doesn't work, create a pull request and I will investigate it.

Contributions and improvements are welcome.
