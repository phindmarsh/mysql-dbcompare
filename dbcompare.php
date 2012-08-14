#!/usr/bin/php
<?php

include_once 'dbStruct.php';
include_once 'Source.php';

$longopts = array('verbose', 'help');
foreach(Source::getArgList() as $arg){
    $longopts[] = "from-$arg:";
    $longopts[] = "to-$arg:";
}
$options = getopt('', $longopts);

if(isset($options['help'])) usage();

define('VERBOSE', isset($options['verbose']));

$from = Source::init('from', $options);
$to = Source::init('to', $options);

$differ = new dbStructUpdater();

$to_sql = $to->getStructure();
$from_sql = $from->getStructure();

if(VERBOSE){
    notice("*** From SQL: $from \n");
    echo $from_sql . "\n\n";
    notice('***');
    
    notice("*** To SQL: $to \n");
    echo $to_sql;
    notice('***');
}
    
$diffs = $differ->getUpdates($to_sql, $from_sql);
$generated = date('r');

echo "
#
# Structure difference between '$from' and '$to'
#
# Generated: $generated
#
# START DIFF;

";
foreach($diffs as $diff){
    echo "$diff;\n\n";
}

echo "
# END DIFF;
";

function readchoice($prompt, $choices){
    $response = "";
    while(!in_array($response, $choices, true)){
        $response = readline($prompt);
    }
    return $response;
}

function readfilepath($prompt){
    $source = trim(readline($prompt));
    if(!file_exists($source)) error("The specified source file does not exist or is not a file!\n($source)");
    return $source;
}

function error($message){
    echo "\n\033[0;31m*** ERROR!\n";
    echo "$message\033[0m\n\n";
    exit(1);
}

function notice($message){
    echo "\n\033[1;34m$message\033[0m\n";
}

function usage(){
    
    global $argv;
    
    echo "
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
  

Example usage:

# The following would diff two files on the local disk:
$ $argv[0] --from-file=test/dev.sql --to-file=test/live.sql

# This would connect to a remote server and diff against a local file
$ $argv[0]  --from-file=test/dev.sql \
            --to-ssh-server=server.example.com --to-ssh-user=root \
            --to-ssh-pubkey=path/to/id_rsa.pub --to-ssh-privkey=path/to/id_rsa \
            --to-db-name=db_live --to-db-user=root --to-db-password=password123

# Similarly, this example would use a local mysqldump against a remote server
$ $argv[0]  --from-db-name=db_dev --from-db-user=root --from-db-password=test123 \
            --to-ssh-server=server.example.com --to-ssh-user=root \
            --to-ssh-pubkey=path/to/id_rsa.pub --to-ssh-privkey=path/to/id_rsa \
            --to-db-name=db_live --to-db-user=root --to-db-password=password123


";

    exit(0);
}

?>