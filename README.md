# MySQL DBCompare

This commandline script will generate a structure diff of two input files and echo it to SDOUT.

It uses the "Database structure synchronizer" class from [phpclasses.org](http://www.phpclasses.org/package/4615-PHP-Compare-MySQL-databases-to-synchronize-structures.html) with a simple commandline interface. 

**Usage:**

```bash

# simple (no arguments)
$ ./dbcompare.php

# alternative (source files supplied as arguments)
$ ./dbcompare.php path/to/source.sql path/to/destination.sql

```

An example test script can be executed with:
```bash
$ ./dbcompare.php test/dev.sql test/live.sql
```

Which results in:
```sql
#
# Structure difference between 'dev.sql' and 'live.sql'
#
# Generated: Tue, 14 Aug 2012 14:54:35 +1200
#
# START DIFF;

CREATE TABLE IF NOT EXISTS `bar` (
  `bar_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `foo_id` int(11) unsigned NOT NULL,
  `default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`bar_id`),
  KEY `foo_id` (`foo_id`),
  CONSTRAINT `bar_ibfk_1` FOREIGN KEY (`foo_id`) REFERENCES `foo` (`foo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

ALTER TABLE `foo` MODIFY `name` varchar(256) NOT NULL

ALTER TABLE `foo` MODIFY `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

# END DIFF;
```

This script assumes your php runtime is located at `/usr/bin/php`, if it's not you'll need to change it at the top of the file. You could copy the script into `/usr/local/bin` and rename it to `dbcompare` to make usage simpler.

