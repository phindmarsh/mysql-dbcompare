# MySQL DBCompare

This commandline script will generate a structure diff of two input files and echo it to SDOUT.

It uses the "Database structure synchronizer" class from [phpclasses.org](http://www.phpclasses.org/package/4615-PHP-Compare-MySQL-databases-to-synchronize-structures.html) with a simple commandline interface. 

Connections can be made with SSH to remote servers, where `mysqldump` is executed to return the current schema structure. `mysqldump` can also be executed on the local machine the script runs on.

SSH connections require the PECL `ssh2` library and `libssh2`, see [here](http://pecl.php.net/package/ssh2)

**Usage:**

```
Usage: $ $argv[0] --from-[OPTIONS] --to-[OPTIONS]

Both `from` and `to` sources must be specified, and can be a 
combination of either `file`, `db`  or `ssh` sources.

`file` OPTIONS: 
The `file` source reads a file from the local disk

  --(from|to)-file          The filename of the SQL dump to 
                            use. Must be readable by the user
                            executing the script.

`db` OPTIONS:
The `db` source uses the mysqldump command executed on the current machine

  --(from|to)-db-name       (required) The name of the database
                            to use in the dump
  --(from|to)-db-user       The username to connect with
  --(from|to)-db-password   The password to connect with
  --(from|to)-db-host       The database host 

`ssh` OPTIONS:
The `ssh` source extends the `db` source to use mysqldump over 
an SSH connection to a remote server. Authentication must use
public keys. OPTIONS include the `db` commands above, plus:

  --(from|to)-ssh-server    (required) The server to connect to
                            A different port number can be specified
                            using colon notation.
  --(from|to)-ssh-user      (required) The username to connect as
  --(from|to)-ssh-pubkey    (required) The path to the public key
  --(from|to)-ssh-privkey   (required) The path to the private key
  
```

**Examples**:

```bash
# The following would diff two files on the local disk:
$ $argv[0] --from-file=test/dev.sql --to-file=test/live.sql

# This would connect to a remote server and diff against a local file
$ $argv[0] --from-file=test/dev.sql \
           --to-ssh-server=server.example.com --to-ssh-user=root \
           --to-ssh-pubkey=path/to/id_rsa.pub --to-ssh-privkey=path/to/id_rsa \
           --to-db-name=db_live --to-db-user=root --to-db-password=password123

# Similarly, this example would use a local mysqldump against a remote server
$ $argv[0] --from-db-name=db_dev --from-db-user=root --from-db-password=test123 \
           --to-ssh-server=server.example.com --to-ssh-user=root \
           --to-ssh-pubkey=path/to/id_rsa.pub --to-ssh-privkey=path/to/id_rsa \
           --to-db-name=db_live --to-db-user=root --to-db-password=password123
```


An example result (from two different files):
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

