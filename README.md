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
* Modify your client-side config (/path/to/mpr/client/config.json)
* Enjoy!

(Optional)
* mkdir ~/bin
* echo '/usr/bin/php /path/to/mpr/client/mpr.run.php $*' > ~/bin/mpr
* chmod 0755 ~/bin/mpr

Usage
===

* mpr init - to initialize repository in current directory
* mpr search <query> - to search packages
* mpr install <package_name> - to install package and dependencies
* mpr remove <package_name> - to remove package (Package dependencies would not be removed!)

Example
===
```

[greevex@ironman new (master)]$ ls -la
total 8
drwxrwxr-x. 2 greevex greevex 4096 Sep 18 11:50 .
drwxrwxr-x. 3 greevex greevex 4096 Sep 17 17:15 ..
[greevex@ironman new (master)]$ mpr init
[mpr] Initializing mpr repository...
[mpr] Repository was initialized! Now you can install packages!
[greevex@ironman new (master)]$ mpr search lib
Receiving http://mpr.sdstream.ru/manifest-global.mpr.json.gz...OK!
============================== | ========== | ============================================================
                        -NAME- |  -VERSION- |                                                -DESCRIPTION-
------------------------------ | ---------- | ------------------------------------------------------------
               restapi_service |        2.0 |                             RestAPI service (server) for SDS
          twitter_streamclient |        3.0 |      Client for Twitter Streaming API based on Phirehose lib
===== Total: 2 =============== | ========== | ============================================================
---
[greevex@ironman new (master)]$ mpr install restapi_service
Receiving http://mpr.sdstream.ru/manifest-global.mpr.json.gz...OK!
Searching package restapi_service...
Receiving http://mpr.sdstream.ru/manifest-global.mpr.json.gz...OK!
Searching package restapi_service...
=== Warning! ===
This package has dependencies: grunge
Package would not work without installed dependencies.
If you don't want to install dependencies you can not install this package!
Do you want to install all dependencies? [y/n]: y
bool(true)
string(1) "y"
Installing dependencies...
Checking grunge...
Receiving http://mpr.sdstream.ru/manifest-global.mpr.json.gz...OK!
Searching package grunge...
Receiving http://mpr.sdstream.ru/manifest-global.mpr.json.gz...OK!
Searching package grunge...
Receiving http://mpr.sdstream.ru/grunge/grunge.phar...OK!
Connection opened!
Downloading content... [1903504 bytes]
Content downloaded to /opt/greevex/mpr/tests/new/grunge.phar!
Installed!
Receiving http://mpr.sdstream.ru/restapi_service/restapi_service.phar...OK!
Connection opened!
Downloading content... [39260 bytes]
Content downloaded to /opt/greevex/mpr/tests/new/libs/restapi_service.phar!
Installed!
[greevex@ironman new (master)]$ ls -la
total 1872
drwxrwxr-x. 3 greevex greevex    4096 Sep 18 11:51 .
drwxrwxr-x. 3 greevex greevex    4096 Sep 17 17:15 ..
-rw-rw-r--. 1 greevex greevex 1903504 Sep 18 11:51 grunge.phar
drwxrwxr-x. 2 greevex greevex    4096 Sep 18 11:51 libs
-rw-rw-r--. 1 greevex greevex       0 Sep 18 11:50 .mprroot
[greevex@ironman new (master)]$ ls -la libs
total 48
drwxrwxr-x. 2 greevex greevex  4096 Sep 18 11:51 .
drwxrwxr-x. 3 greevex greevex  4096 Sep 18 11:51 ..
-rw-rw-r--. 1 greevex greevex 39260 Sep 18 11:51 restapi_service.phar

```