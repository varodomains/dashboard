# varo dashboard

This is the source code for varo DNS WebUI; this works with Mutual (API which sits on the master PowerDNS/MySQL replication server) to manipulate records. The WebUI is a frontend interface to manage records, domains, and users. The WebUI sends API calls to Mutual to modify DNS records.

(The documentation is a WIP, please bear with us, we are just trying to make the source available)

You will need to set up PowerDNS with a MySQL replicated backend (each server will have its own database replicated from the master). The WebUI is ideally hosted on one server while the PowerDNS servers sit separately.

It is essential to lock down MySQL and have the servers communicate over a VPN/Backplane network (such as ZeroTier).

All of the tables are listed in etc/tables.sql

The TODO list for setting up Dashboard is:
* Setup your web server and PHP (we run PHP 7.4)
* `cd etc && cp config.sample.php config.php && vim config.php`

## License
[![CC BY-NC-SA](https://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
