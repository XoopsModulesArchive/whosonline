# phpMyAdmin MySQL-Dump
# version 2.2.2
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# --------------------------------------------------------

#
# Table structure for table `lastseen`
#

CREATE TABLE lastseen (
    uid      INT(5) UNSIGNED     NOT NULL DEFAULT '0',
    username VARCHAR(25)         NOT NULL DEFAULT '',
    time     INT(10)             NOT NULL DEFAULT '0',
    ip       VARCHAR(15)         NOT NULL DEFAULT '',
    online   TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    KEY username (username),
    KEY time (time),
    KEY uid (uid),
    KEY ip (ip),
    KEY online (online)
);

