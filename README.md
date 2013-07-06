# SiteLog-slave

This is the source code for the SiteLog (https://github.com/emotionLoop/SiteLog) slave. It's meant to be executed every minute by a cron (in a different server from the "master" SiteLog), picking any services that failed and double-checking if they've really failed or if it was some networking issue.

All you have to setup is in config.inc.php and in helper.inc.php (just the Mandrill API Key).

It needs to be able to connect to the "master" SiteLog Database.
