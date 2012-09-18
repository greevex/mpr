# mpr

\m/ Package Repository
===

Also may be "\m/ Phar Repository" ;)

All you need is: static web-server as repository, cron (to generate global manifest) and Phar php extension.


Requirements:
 * PHP 5.4+
 * crond
 * any web-server for repository (static, no php required!)
 * Enabled Phar extension for PHP (`phar.readonly = Off` in php.ini)

Where is it would be helpful?
===

In big projects may be. You create many not big git repositories for libraries. For each small library!
Then you can use your packages in any project. Just `mpr init && mpr install my_mega_library` :)
And also if you use mpr you protecting your project from dummies, who like to change something not in repository!
Phar archives created by mpr is gzip compressed.

Installation
===

Server-side:
* Create new web host (e.g. http://mpr.greevex.ru)
* Modify your server-side config `/path/to/mpr/server/config.json`
* Enable to cron script `* * * * * /usr/bin/php /path/to/mpr/server/check.php`

Client-side:
* Modify your client-side config `/path/to/mpr/client/config.json`
* Enjoy!

Optional for easy use
```
* mkdir ~/bin
* echo '/usr/bin/php /path/to/mpr/client/mpr.run.php $*' > ~/bin/mpr
* chmod 0755 ~/bin/mpr
```

Usage
===

* `mpr init` - to initialize repository in current directory
* `mpr search <query>` - to search packages
* `mpr update` - update package list from server (auto-update every minute anyway)
* `mpr upgrade [package_name]` - update local package(s) to new version (if exists)
* `mpr install <package_name>` - to install package and dependencies
* `mpr remove <package_name>` - to remove package (Package dependencies would not be removed!)

Real usage example
===
```

[greevex@ironman ~]$ mkdir mpr-test
[greevex@ironman ~]$ cd mpr-test/
[greevex@ironman mpr-test]$ mpr init
[mpr] Initializing mpr repository...
[mpr] Repository was initialized! Now you can install packages!
[greevex@ironman mpr-test]$ mpr search lib
Update package list...
Receiving http://mpr.greevex.ru/manifest-global.mpr.json.gz...OK!
============================== | ========== | ============================================================
                        -NAME- |  -VERSION- |                                                -DESCRIPTION-
------------------------------ | ---------- | ------------------------------------------------------------
               restapi_service |        2.0 |                             RestAPI service (server) for SDS
          twitter_streamclient |        3.0 |      Client for Twitter Streaming API based on Phirehose lib
===== Total: 2 =============== | ========== | ============================================================
---
[greevex@ironman mpr-test]$ mpr install restapi_service
Searching package restapi_service...
Loading package list from cache. 52 seconds before next update.
Checking local packages...
Installing package...
=== Warning! ===
Package would not work without installed dependencies.
If you don't want to install dependencies you can not install this package!
Dependencies: grunge
Do you want to install all dependencies? [y/n]: y
Installing dependencies...
Checking grunge...
Searching package grunge...
Checking local packages...
Installing package...
Receiving http://mpr.greevex.ru/grunge/grunge.phar...OK!
Downloading content... [1903504 bytes]
Content downloaded to /home/greevex/mpr-test/grunge.phar!
Package installed!
Receiving http://mpr.greevex.ru/restapi_service/restapi_service.phar...OK!
Downloading content... [39260 bytes]
Content downloaded to /home/greevex/mpr-test/libs/restapi_service.phar!
Installed packages: grunge, restapi_service
[greevex@ironman mpr-test]$ tree .
.
|-- grunge.phar
`-- libs
    `-- restapi_service.phar

1 directory, 2 files


```