<?php

echo "PHP: ".PHP_VERSION."<br>";
echo "SAPI: ".php_sapi_name()."<br>";

echo "Extension loaded: ";
var_dump(extension_loaded('curl'));

echo "<br>";

echo "Function exists: ";
var_dump(function_exists('curl_init'));