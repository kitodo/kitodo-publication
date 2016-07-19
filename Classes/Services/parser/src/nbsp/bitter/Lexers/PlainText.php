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

class PlainText extends Lexer
{
    /**
     * Parse a plain text file.
     *
     * @param Output $out
     * @param string $in
     */
    public function parse(Input $in, Output $out)
    {
        $out->startLine();

        // Begin parsing:
        Lexer::loop($in, $out, [
            'all' => [
                Lexer::MATCH => '.+',
                Lexer::WRAP  => 'text',
            ],
        ]);

        $out->endLine();
    }
}
