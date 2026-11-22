<?php
$file = $argv[1];
$s = file_get_contents($file);
$s = preg_replace_callback('/\\\\x([0-9a-fA-F]{2})|\\\\([0-7]{1,3})/', function ($m) {
    if (isset($m[1]) && $m[1] !== '') return chr(hexdec($m[1]));
    if (isset($m[2]) && $m[2] !== '') return chr(octdec($m[2]));
    return $m[0];
}, $s);
echo $s;
