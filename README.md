# varo dashboard

This is the source code for varo DNS WebUI; this works with Mutual (API which sits on the master PowerDNS/MySQL replication server) to manipulate records. The WebUI is a frontend interface to manage records, domains, and users. The WebUI sends API calls to [Mutual](https://github.com/varodomains/mutual) to modify DNS records.

You will need to set up PowerDNS with a MySQL replicated backend (each server will have its own database replicated from the master). The WebUI is ideally hosted on one server while the PowerDNS servers sit separately.

It is essential to lock down MySQL and have the servers communicate over a VPN/Backplane network (such as ZeroTier).

All of the tables are listed in etc/tables.sql

The TODO list for setting up Dashboard is:
* Setup your web server and PHP (we run PHP 8.2)
* `cd etc && cp config.sample.php config.php && vim config.php`
* Add `*/1 * * * * /usr/bin/php /path/to/varo/etc/cron.php >/dev/null 2>&1` to crontab

We highly recommend using the scripts created by Nathan Woodburn ([here](https://github.com/Nathanwoodburn/HNS-server/tree/main/varo)) rather than trying to set this up manually.

## License
[![CC BY-NC-SA](https://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
