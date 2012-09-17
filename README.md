# mpr

\m/ Package Repository.
===

All you need is: static web-server as repository, cron (to generate global manifest). That's all!

Requirements: PHP 5.4+, crond, any web-server (for repository)

Installation
===

Server-side:
* Create new web host (e.g. http://mpr.greevex.ru)
* Modify your server-side config (/path/to/mpr/server/config.json)
* Enable to cron script (* * * * * /usr/bin/php /path/to/mpr/server/check.php)

Client-side:
* Modify your client-side config (/path/to/mpt/client/config.json)
* Enjoy!

(Optional)
* mkdir ~/bin
* echo '/usr/bin/php /path/to/mpr/client/mpr.run.php $*' > ~/bin/mpr
* chmod 0744 ~/bin/mpr

Usage
===

* mpr init - to init repository in current directory
* mpr search <query> - to search packages
* mpr install <package_name> - to install package and dependencies
* mpr remove <package_name> - to remove package (Package dependencies would not be removed!)