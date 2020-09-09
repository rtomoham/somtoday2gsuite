<?php
require_once('Somtoday2GSuite.php');

if (!date_default_timezone_set(getenv('TZ'))) {
 date_default_timezone_set('Europe/Amsterdam');
};

if ($argc > 1) {
  if ('test' == $argv[1]) {
    echo("TEST: 'test' argument provided, so running test.\n\n");
    $somtoday2gsuite = new Somtoday2GSuite(true);
  } else {
    $somtoday2gsuite = new Somtoday2GSuite();
  }
}
$somtoday2gsuite->refresh();
?>
