<?php

//$loader = 
require_once __DIR__ . '/vendor/autoload.php';
// $loader->addPsr4( "Websyspro\\Package\\", __DIR__ . "/src/" );

use Websyspro\Package\UpdateComposer;
new UpdateComposer( __DIR__ );