<?php

/**
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author    Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter\Lexers;

use nbsp\bitter\Input;
use nbsp\bitter\Lexer;
use nbsp\bitter\Output;
use nbsp\bitter\Tokens;

class XPath extends Lexer
{
    use Tokens\XPath;

    /**
     * Parse an XPath string.
     *
     * @param Output $out
     * @param string $in
     */
    public function parse(Input $in, Output $out)
    {
        #$out->startLine();
        #$out->startToken('mods:mods');

        // Begin parsing:
        Lexer::loop($in, $out, $this->tokens());

        $out->endToken();
        while ($out->writer->endElement()) {

        }
        #$out->endLine();
    }
}
