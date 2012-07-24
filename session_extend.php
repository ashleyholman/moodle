<?php

require_once(dirname(__FILE__) . '/config.php');

$sesskey = optional_param('sesskey', '', PARAM_RAW);

$res = array();

if ($USER->sesskey === $sesskey) {
  $res['result'] = 'success';
} else {
  $res['result'] = 'failed';
}

print json_encode($res);

?>
