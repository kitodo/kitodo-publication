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
use nbsp\bitter\Output;

trait PHP
{
    use HTML {
        HTML::tokens as htmlTokens;
    }

    function tokens()
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
                Lexer::MATCH => [
                    '(\\\|::)?[a-z_][a-z0-9_]*',
                    '<\?php|\?>',
                ],
                Lexer::CALL  => function ($in, $out, $token) {
                    $data = token_get_all('<?php ' . $token);

                    // Detect PHP keywords:
                    switch ($data[1][0]) {
                        case T_NS_SEPARATOR:
                        case T_DOUBLE_COLON:
                        case T_STRING:
                            break;
                        default:
                            $out->writeToken($token, 'word predefined');
                            return;
                    }

                    // Apply custom classes:
                    Lexer::choose($in, $out, $token, [
                        [
                            Lexer::MATCH => '%\b(true|false|null)\b%i',
                            Lexer::WRAP  => 'word predefined',
                        ],
                        [
                            Lexer::BEFORE => '%\b(const)\s+$%i',
                            Lexer::WRAP   => 'word constant define',
                        ],
                        [
                            Lexer::BEFORE => '%\b(namespace)\s+$%i',
                            Lexer::WRAP   => 'type namespace define',
                        ],
                        [
                            Lexer::BEFORE => '%\b(use|as)\s+$%i',
                            Lexer::WRAP   => 'type namespace',
                        ],
                        [
                            Lexer::BEFORE => '%\b(class)\s+$%i',
                            Lexer::WRAP   => 'type class define',
                        ],
                        [
                            Lexer::BEFORE => '%\b(new|instanceof|implements|extends)\s+$%i',
                            Lexer::WRAP   => 'type class',
                        ],
                        [
                            Lexer::MATCH  => '%^\\\%',
                            Lexer::BEFORE => '%(new|instanceof|implements|extends)\s+\S+$%',
                            Lexer::AFTER  => '%^\s*\(%',
                            Lexer::WRAP   => 'type class',
                        ],
                        [
                            Lexer::MATCH => '%^\\\%',
                            Lexer::AFTER => '%^(?!\s*[(])%',
                            Lexer::WRAP  => 'type namespace',
                        ],
                        [
                            Lexer::AFTER => '%^\\\%',
                            Lexer::WRAP  => 'type namespace',
                        ],
                        [
                            Lexer::BEFORE => '%[\(,]\s*$%',
                            Lexer::AFTER  => '%^\s*\$%',
                            Lexer::WRAP   => 'type class',
                        ],
                        [
                            Lexer::AFTER => '%^::%',
                            Lexer::WRAP  => 'type class',
                        ],
                        [
                            Lexer::BEFORE => '%\b(function)\s+$%i',
                            Lexer::AFTER  => '%^\s*\(%',
                            Lexer::WRAP   => 'word method define',
                        ],
                        [
                            Lexer::AFTER => '%^\s*\(%',
                            Lexer::WRAP  => 'word method',
                        ],
                        [
                            Lexer::WRAP => 'word constant',
                        ],
                    ]);
                },
            ],

            'variable'     => [
                Lexer::MATCH => '(\$|->|::[$])[a-z_][a-z0-9_]*',
                Lexer::CALL  => function ($in, $out, $token) {
                    Lexer::choose($in, $out, $token, [
                        [
                            Lexer::AFTER => '%^\s*[(]%',
                            Lexer::WRAP  => 'word method',
                        ],
                        [
                            Lexer::MATCH => '%^(->)%',
                            Lexer::WRAP  => 'word property',
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
    function commentLine()
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
    function commentBlock()
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

            // Code:
            'code'    => [
                Lexer::MATCH => '(<code>|`).*?(</code>|`)',
                Lexer::CALL  => function ($in, $out, $token) {
                    $lines    = preg_split("/\r\n|\n|\r/", $token);
                    $prefixes = $codes = [];

                    // Split the lines into code and prefix:
                    foreach ($lines as $index => $line) {
                        $bits = preg_split(
                            '/(^\s*[*])/', $line, 2,
                            PREG_SPLIT_DELIM_CAPTURE
                        );

                        if (count($bits) == 3) {
                            list($null, $prefix, $code) = $bits;

                            $prefixes[] = $prefix;
                            $codes[]    = $code;
                        } else {
                            $prefixes[] = '';
                            $codes[]    = $line;
                        }
                    }

                    // Highlight codes:
                    $tmpIn = new Input();
                    $tmpIn->openString(implode("\n", $codes));
                    $tmpOut = new Output();
                    $tmpOut->openMemory();

                    Lexer::loop($tmpIn, $tmpOut, Lexer::extend($this->tokens(), [
                        'codeBegin' => [
                            Lexer::MATCH => '^(<code>|`)',
                            Lexer::WRAP  => 'word begin',
                        ],

                        'codeEnd'   => [
                            Lexer::MATCH => '(</code>|`)$',
                            Lexer::WRAP  => 'word end',
                        ],
                    ]));

                    // Rebuild code:
                    $parse = new Input();
                    $parse->openString($tmpOut->outputMemory());
                    $last = $parse->after('%^%');
                    $code = '';
                    $open = [];

                    while ($parse->valid()) {
                        $token = $parse->after('%[\n]|<.*?>|[^<\n]+%');

                        if ($token == null) {
                            break;
                        }

                        $parse->move($token);

                        // New line:
                        if ($token == "\n") {
                            foreach ($open as $value) {
                                $code .= '</span>';
                            }

                            $code .= "\n";

                            foreach ($open as $value) {
                                $code .= $value;
                            }
                        }

                        // Open tag:
                        else if ($token->test('%<span%')) {
                            $code .= $token;
                            $open[] = (string) $token;
                        }

                        // Close tag:
                        else if ($token->test('%</span%')) {
                            $code .= $token;
                            array_pop($open);
                        }

                        // Actual code:
                        else {
                            $code .= $token;
                        }
                    }

                    // Split code into lines and add prefixes:
                    $codes = explode("\n", $code);
                    $lines = [];

                    foreach ($codes as $index => $code) {
                        $prefix = explode('*', $prefixes[$index], 2);

                        if (count($prefix) == 2) {
                            $lines[] = sprintf(
                                '<span class="divider">%s*</span>%s%s',
                                $prefix[0], $prefix[1], $codes[$index]
                            );
                        } else {
                            $lines[] = $prefixes[$index] . $codes[$index];
                        }
                    }

                    $out->writeRaw(implode("\n", $lines));
                }
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
            ]
        ];
    }

    /**
     * Double string tokens.
     *
     * @return array
     */
    function stringDouble()
    {
        return Lexer::extend($this->variable(), [
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
        ]);
    }

    /**
     * Single string tokens.
     *
     * @return array
     */
    function stringSingle()
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

    /**
     * Variables.
     *
     * @return array
     */
    function variable()
    {
        return [
            // Variables:
            'variable' => [
                Lexer::MATCH => '(
											[$][a-z_][a-z0-9_]*
											|[{]
												[$][a-z_][a-z0-9_]*
												(->[a-z_][a-z0-9_]*)*
											[}]
										)',
                Lexer::WRAP  => 'word variable',
            ],
        ];
    }
}
