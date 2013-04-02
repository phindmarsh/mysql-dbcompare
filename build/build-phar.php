#!/usr/bin/env php
<?php

define("APP_ROOT", realpath(__DIR__ . '/../'));
define("BUILD_DIR", APP_ROOT . '/build');
define("SRC_DIR", APP_ROOT . '/src');

define("PHAR_NAME", 'mysql-compare.phar');

if(!class_exists('Phar')) exit("Phar class is required");
if(!Phar::canWrite()) exit("phar.readonly must be set to 1\n");

$p = new Phar(PHAR_NAME, 0, PHAR_NAME);

// create transaction - nothing is written to newphar.phar
// until stopBuffering() is called, although temporary storage is needed
$p->startBuffering();
// add all files in /path/to/project, saving in the phar with the prefix "project"
$p->buildFromIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(SRC_DIR)), APP_ROOT);

$p['src/test/dev.sql'] = file_get_contents(APP_ROOT . '/src/test/dev.sql');
$p['src/test/live.sql'] = file_get_contents(APP_ROOT . '/src/test/live.sql');
//$p->setMetadata(array('bootstrap' => SRC_DIR . '/dbcompare.php'));

$defaultStub = $p->createDefaultStub('src/dbcompare.php');
$stub = "#!/usr/bin/env php \n".$defaultStub;
$p->setStub($stub);

// save the phar archive to disk
$p->stopBuffering();

if(file_exists(BUILD_DIR . '/' . PHAR_NAME)){
	chmod(BUILD_DIR . '/' . PHAR_NAME, 0755);
}