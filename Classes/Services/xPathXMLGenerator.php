<?php
namespace EWW\Dpf\Services;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use nbsp\bitter\Input;
use nbsp\bitter\Lexers\XPath;
use nbsp\bitter\Output;

require_once 'parser/vendor/autoload.php';

/**
 * xPathXMLGenerator
 */
class xPathXMLGenerator
{

    public function parse($xPath)
    {
        $xpath = new XPath();
        $in    = new Input();
        $out   = new Output();

        $out->openMemory();

        // Parsing xPath
        $in->openString($xPath);
        $xpath->parse($in, $out);

        $output = $out->outputMemory();

        return $output;
    }

}
