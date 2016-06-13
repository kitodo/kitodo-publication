<?php

/**
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author    Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter\Tokens;

use nbsp\bitter\Input;
use nbsp\bitter\Lexer;

trait JavaScript
{
    public function tokens()
    {
        return [
            'commentLine'  => [
                Lexer::MATCH => '(//|[#]).*?$',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('comment line');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->commentLine());

                    $out->endToken();
                },
            ],

            'commentBlock' => [
                Lexer::MATCH => '/[*].*?[*]/',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('comment block');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->commentBlock());

                    $out->endToken();
                },
            ],

            'keyword'      => [
                Lexer::MATCH => '\b(with|while|volatile|void|var|typeof|try|true|transient|throws|throw|this|synchronized|switch|super|static|short|return|public|protected|private|package|null|new|native|long|interface|int|instanceof|in|import|implements|if|goto|function|for|float|finally|final|false|extends|export|enum|else|double|do|delete|default|debugger|continue|const|class|char|catch|case|byte|break|boolean|abstract)\b',
                Lexer::WRAP  => 'word predefined',
            ],

            'variable'     => [
                Lexer::MATCH => '[$a-z_][$a-z0-0_]*',
                Lexer::CALL  => function ($in, $out, $token) {
                    Lexer::choose($in, $out, $token, [
                        [
                            Lexer::AFTER => '%^\s*[(]%',
                            Lexer::WRAP  => 'word method',
                        ],
                        [
                            Lexer::BEFORE => '%[\.]$%',
                            Lexer::WRAP   => 'word property',
                        ],
                        [
                            Lexer::WRAP => 'word variable',
                        ],
                    ]);
                },
            ],

            'stringDouble' => [
                Lexer::MATCH => '"[^"\\\]*(?:\\\.[^"\\\]*)*"',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('value string double');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->stringDouble());

                    $out->endToken();
                },
            ],

            'stringSingle' => [
                Lexer::MATCH => '\'[^\'\\\]*(?:\\\.[^\'\\\]*)*\'',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('value string single');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->stringSingle());

                    $out->endToken();
                },
            ],

            'number'       => [
                Lexer::MATCH => [
                    '(?<![\w\.])([0-9]+[Ee][+-]?[0-9]+|([0-9]*\.[0-9]+|[0-9]+\.[0-9]*)([Ee][+-]?[0-9]+)?)(?![\w\.])',
                    '(?<![\w\.])[+-]?([1-9][0-9]*|0)(?![\w\.])',
                    '(?<![\w\.])[+-]?0[0-7]+(?![\w\.])',
                    '(?<![\w\.])[+-]?0x[0-9a-fA-F]+(?![\w\.])',
                ],
                Lexer::WRAP  => 'value number',
            ],
        ];
    }

    /**
     * Line comment tokens.
     *
     * @return array
     */
    public function commentLine()
    {
        return [
            // Begin:
            'begin' => [
                Lexer::MATCH => "^(//|[#])",
                Lexer::WRAP  => 'begin',
            ],
        ];
    }

    /**
     * Block comment tokens.
     *
     * @return array
     */
    public function commentBlock()
    {
        return [
            // Begin:
            'begin'   => [
                Lexer::MATCH => "^/\*(\*)?",
                Lexer::WRAP  => 'begin',
            ],

            // End:
            'end'     => [
                Lexer::MATCH => "\*/$",
                Lexer::WRAP  => 'end',
            ],

            // Divider:
            'divider' => [
                Lexer::MATCH => '^\s*[*](?!/)',
                Lexer::WRAP  => 'divider',
            ],

            // Keyword:
            'keyword' => [
                Lexer::MATCH => '@[a-z_][a-z0-9_]*',
                Lexer::WRAP  => 'word predefined',
            ],
        ];
    }

    /**
     * Double string tokens.
     *
     * @return array
     */
    public function stringDouble()
    {
        return [
            // Empty:
            'empty'  => [
                Lexer::MATCH => '^""$',
                Lexer::WRAP  => 'empty',
            ],

            // Begin:
            'begin'  => [
                Lexer::MATCH => '^"',
                Lexer::WRAP  => 'begin',
            ],

            // End:
            'end'    => [
                Lexer::MATCH => '"$',
                Lexer::WRAP  => 'end',
            ],

            // Escape:
            'escape' => [
                Lexer::MATCH => '\\\.',
                Lexer::WRAP  => 'escape',
            ],
        ];
    }

    /**
     * Single string tokens.
     *
     * @return array
     */
    public function stringSingle()
    {
        return [
            // Empty:
            'empty'  => [
                Lexer::MATCH => "^''$",
                Lexer::WRAP  => 'empty',
            ],

            // Begin:
            'begin'  => [
                Lexer::MATCH => "^'",
                Lexer::WRAP  => 'begin',
            ],

            // End:
            'end'    => [
                Lexer::MATCH => "'$",
                Lexer::WRAP  => 'end',
            ],

            // Escape:
            'escape' => [
                Lexer::MATCH => '\\\.',
                Lexer::WRAP  => 'escape',
            ],
        ];
    }
}
