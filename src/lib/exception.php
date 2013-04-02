<?php

set_exception_handler(function(Exception $e){
	echo "\033[0;31m";
	echo "An error occurred: \n";
	echo "  " . $e->getMessage();
	echo "\033[0m\n";

	exit(1);
});