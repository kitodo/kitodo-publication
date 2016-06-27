<?php

/*
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author    Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter\Tokens;

use nbsp\bitter\Input;
use nbsp\bitter\Lexer;

trait HTML
{
    /**
     * HTML tokens.
     *
     * @return array
     */
    public function tokens()
    {
        return [
            'entity'      => [
                Lexer::MATCH => '&([#][0-9]{1,4}|[#]x[a-f0-9]{1,4}|[a-z\-]+);',
                Lexer::WRAP  => 'word entity',
            ],
            'comment'     => [
                Lexer::MATCH => '<!--',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('comment');

                    $begin = $token;
                    $out->writeToken($begin, 'comment begin');

                    $end = Lexer::loop($in, $out, [
                        'end' => [
                            Lexer::MATCH => '-->?',
                            Lexer::CALL  => Lexer::STOP,
                        ],
                    ]);
                    $out->writeToken($end, 'comment end');

                    // Calculate final position:
                    if ($end) {
                        $token->position = $end->position - (
                            strlen($begin) - strlen($end)
                        );
                    }

                    $out->endToken();
                },
            ],
            'doctype'     => [
                Lexer::MATCH => '<!(?!--)',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('comment');

                    $begin = $token;
                    $out->writeToken($begin, 'comment begin');

                    $end = Lexer::loop($in, $out, [
                        'end' => [
                            Lexer::MATCH => '>',
                            Lexer::CALL  => Lexer::STOP,
                        ],
                    ]);
                    $out->writeToken($end, 'comment end');

                    // Calculate final position:
                    if ($end) {
                        $token->position = $end->position - (
                            strlen($begin) - strlen($end)
                        );
                    }

                    $out->endToken();
                },
            ],
            'instruction' => [
                Lexer::MATCH => '<[?]xml.*?[?]>',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('type instruction');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->instruction());

                    $out->endToken();
                },
            ],
            'element'     => [
                Lexer::MATCH => '</?[a-z_\-][a-z0-9_\-\.:]*.*?/?>',
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('type element');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->element());

                    $out->endToken();
                },
            ],
        ];
    }

    /**
     * HTML attributes.
     *
     * @return array
     */
    public function attribute()
    {
        return [
            'attribute' => [
                Lexer::MATCH => '[a-z_\-][a-z0-9_\-\.:]*\s*=?\s*',
                Lexer::WRAP  => 'type attribute',
            ],

            'string'    => [
                Lexer::MATCH => [
                    "'.*?'",
                    '".*?"',
                ],
                Lexer::CALL  => function ($in, $out, $token) {
                    $out->startToken('value string');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->string());

                    $out->endToken();
                },
            ],
        ];
    }

    /**
     * HTML elements.
     *
     * @return array
     */
    public function element()
    {
        return Lexer::extend($this->attribute(), [
            'begin' => [
                Lexer::MATCH => '^</?',
                Lexer::WRAP  => 'begin',
            ],

            'name'  => [
                Lexer::MATCH => '^[a-z_\-][a-z0-9_\-\.:]*',
                Lexer::WRAP  => 'name',
            ],

            'end'   => [
                Lexer::MATCH => '/?>$',
                Lexer::WRAP  => 'end',
            ],
        ]);
    }

    public function entity()
    {
        return [
            'entity' => [
                Lexer::MATCH => '&([#][0-9]{1,4}|[#]x[a-f0-9]{1,4}|[a-z\-]+);',
                Lexer::WRAP  => 'word entity',
            ],
        ];
    }

    /**
     * Tokens for parsing XML processing instructions.
     *
     * @return array
     */
    public function instruction()
    {
        return Lexer::extend($this->attribute(), [
            'begin' => [
                Lexer::MATCH => '^<[?]xml',
                Lexer::WRAP  => 'begin',
            ],

            'end'   => [
                Lexer::MATCH => '/?>$',
                Lexer::WRAP  => 'end',
            ],
        ]);
    }

    public function string()
    {
        return Lexer::extend($this->entity(), [
            // Empty:
            'empty' => [
                Lexer::MATCH => '^["\']["\']$',
                Lexer::WRAP  => 'empty',
            ],

            // Begin:
            'begin' => [
                Lexer::MATCH => '^["\']',
                Lexer::WRAP  => 'begin',
            ],

            // End:
            'end'   => [
                Lexer::MATCH => '["\']$',
                Lexer::WRAP  => 'end',
            ],
        ]);
    }
}
