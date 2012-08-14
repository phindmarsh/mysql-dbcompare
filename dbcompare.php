#!/usr/bin/php
<?php

include_once 'dbStruct.php';

$differ = new dbStructUpdater();

if(isset($argv[1]) && file_exists($argv[1]))
	$source = $argv[1];
else
	$source = readfilepath("Enter the source (dev) mysqldump:\n");
	
if(isset($argv[2]) && file_exists($argv[2]))
	$destination = $argv[2];
else
	$destination = readfilepath("Enter the destination (live) mysqldump:\n");

$source_sql = file_get_contents($source);
$dest_sql = file_get_contents($destination);

$diffs = $differ->getUpdates($dest_sql, $source_sql);
$generated = date('r');

echo "
#
# Structure difference between '$source' and '$destination'
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

?>