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

trait CSS
{
    public function tokens()
    {
        return Lexer::extend($this->comment(), [
            'block'     => [
                Lexer::MATCH => '[{]',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('type structure');

                    $begin = $token;
                    $out->writeRaw($begin);

                    $end = Lexer::loop($in, $out, $this->block());
                    $out->writeRaw($end);

                    // Calculate final position:
                    if ($end) {
                        $token->position = $end->position - (
                            strlen($begin) - strlen($end)
                        );
                    }

                    $out->endToken();
                },
            ],

            'predicate' => [
                Lexer::MATCH => '\[',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('predicate');

                    $begin = $token;
                    $out->writeToken($begin, 'begin');

                    $end = Lexer::loop($in, $out, $this->predicate());
                    $out->writeToken($end, 'end');

                    // Calculate final position:
                    if ($end) {
                        $token->position = $end->position - (
                            strlen($begin) - strlen($end)
                        );
                    }

                    $out->endToken();
                },
            ],

            'rule'      => [
                Lexer::MATCH => '@(import|media)\b',
                Lexer::WRAP  => 'word predefined',
            ],

            'selector'  => [
                Lexer::MATCH => '([.:#]|[:]{2})[a-z0-9_-]+',
                Lexer::CALL  => function ($in, $out, $token) {
                    Lexer::choose($in, $out, $token, [
                        [
                            Lexer::MATCH => '%^[.]%i',
                            Lexer::WRAP  => 'type class',
                        ],
                        [
                            Lexer::MATCH => '%^[:]{2}%i',
                            Lexer::WRAP  => 'type pseudo element',
                        ],
                        [
                            Lexer::MATCH => '%^[:]%i',
                            Lexer::WRAP  => 'type pseudo class',
                        ],
                        [
                            Lexer::MATCH => '%^[#]%i',
                            Lexer::WRAP  => 'type id',
                        ],
                    ]);
                },
            ],

            'element'   => [
                Lexer::MATCH => '[a-z0-9_-]+|[*]',
                Lexer::WRAP  => 'type element',
            ],
        ]);
    }

    /**
     * Tokens common to many areas of CSS.
     *
     * @return array
     */
    public function common()
    {
        return Lexer::extend($this->comment(), [
            'colour' => [
                Lexer::MATCH => '[#]([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b',
                Lexer::WRAP  => 'value color colour',
            ],

            'number' => [
                Lexer::MATCH => '[-+]?[0-9]+(\.[0-9]+)?((px|pt|cm|mm|in|em|ex|pc)\b|\%)?',
                Lexer::WRAP  => 'value number',
            ],

            'string' => [
                Lexer::MATCH => [
                    '".*?"',
                    "'.*?'",
                    '""',
                    "''",
                ],
                Lexer::WRAP  => 'value string',
            ],

            'method' => [
                Lexer::MATCH => '[a-z0-9_-]+\s*(?=[(])',
                Lexer::CALL  => function ($in, $out, $token) {
                    $begin = $token;
                    $out->writeToken($begin, 'word method');

                    $end = Lexer::loop($in, $out, $this->method());
                    $out->writeRaw($end);

                    // Calculate final position:
                    if ($end) {
                        $token->position = $end->position - (
                            strlen($begin) - strlen($end)
                        );
                    }

                },
            ],
        ]);
    }

    /**
     * Block comment tokens.
     *
     * @return array
     */
    public function comment()
    {
        return [
            'comment' => [
                Lexer::MATCH => '/[*].*?[*]/',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('comment block');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, [
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
                            Lexer::MATCH => '@[a-z_][a-z0-9_-]*',
                            Lexer::WRAP  => 'word predefined',
                        ],
                    ]);

                    $out->endToken();
                },
            ],
        ];
    }

    public function block()
    {
        return Lexer::extend($this->comment(), [
            'end'      => [
                Lexer::MATCH => '[}]',
                Lexer::CALL  => Lexer::STOP,
            ],

            'property' => [
                Lexer::MATCH => '\b[a-z0-9_-]+(?=:)',
                Lexer::WRAP  => 'word property',
            ],

            'value'    => [
                Lexer::MATCH => ':',
                Lexer::CALL  => function ($in, $out, $token) {
                    $begin = $token;
                    $out->writeRaw($begin);

                    $end = Lexer::loop($in, $out, $this->value());
                    $out->writeRaw($end);

                    // Calculate final position:
                    if ($end) {
                        $token->position = $end->position - (
                            strlen($begin) - strlen($end)
                        );
                    }

                },
            ],
        ]);
    }

    public function method()
    {
        return Lexer::extend($this->common(), [
            'end' => [
                Lexer::MATCH => '[)]',
                Lexer::CALL  => Lexer::STOP,
            ],
        ]);
    }

    public function value()
    {
        return Lexer::extend($this->common(), [
            'end' => [
                Lexer::MATCH => ';',
                Lexer::CALL  => Lexer::STOP,
            ],
        ]);
    }
}
