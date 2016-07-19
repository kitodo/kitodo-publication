<?php

use nbsp\bitter\Input;
use nbsp\bitter\Lexers\XSLT;
use nbsp\bitter\Output;

require_once '../vendor/autoload.php';

$limit  = 5;
$times  = array();
$output = '';

if (isset($_GET['limit'])) {
    $limit = (integer) $_GET['limit'];

    if ($limit > 100) {
        $limit = 100;
    }

    if ($limit < 1) {
        $limit = 1;
    }

}

set_time_limit($limit * 2);

while (true) {
    $start = microtime(true);

    $xslt = new XSLT();
    $in   = new Input();
    $in->openUri('../assets/example.xsl');
    $out = new Output();
    $out->openMemory();

    $xslt->parse($in, $out);

    $output = $out->outputMemory();

    $times[] = microtime(true) - $start;

    if (array_sum($times) >= $limit) {
        array_shift($times);
        break;
    }
}

if (headers_sent()) {
    exit;
}

?>
<!DOCTYPE html>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<title>Bitter Examples</title>
<link rel="stylesheet" type="text/css" href="../assets/theme.css" />
<p><?php

if (empty($times)) {
    echo 'Your server failed to process the sample within the set timelimit.';
} else {
    printf(
        'Highlighted %d times in %d seconds with an average of %f seconds per execution using %fMB of memory at peak.',
        count($times), $limit,
        (array_sum($times) / count($times)),
        ((xdebug_peak_memory_usage() / 1024) / 1024)
    );
}

?></p>
<pre><?php

echo $output;

?></pre>