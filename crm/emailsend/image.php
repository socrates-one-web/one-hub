<?php
$f=(string)($_GET['f']??'');
header('Location: media.php?f='.rawurlencode(rawurldecode($f)),true,302);
exit;
