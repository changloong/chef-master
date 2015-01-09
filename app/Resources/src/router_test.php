<?php

if (  file_exists($_SERVER['SCRIPT_FILENAME']) ) {
    return false; // serve the requested image
} else {
    require  $_SERVER['DOCUMENT_ROOT'] . '/app_test.php' ;
}
