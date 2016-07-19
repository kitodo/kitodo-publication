<?php

/*
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

class JavaScript extends Lexer
{
    use Tokens\JavaScript;

    /**
     * Parse a JavaScript file.
     *
     * @param Output $out
     * @param string $in
     */
    public function parse(Input $in, Output $out)
    {
        $out->startLine();
        $out->startToken('source js javascript');

        // Begin parsing:
        Lexer::loop($in, $out, $this->tokens());

        $out->endToken();
        $out->endLine();
    }
}
