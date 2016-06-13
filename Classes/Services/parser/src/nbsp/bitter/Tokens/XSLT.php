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

trait XSLT
{
    use HTML {
        HTML::attribute as htmlAttribute;
        HTML::element as htmlElement;
        HTML::string as htmlString;
        HTML::tokens as htmlTokens;
    }

    /**
     * XSL tokens.
     *
     * @return array
     */
    function tokens()
    {
        return Lexer::extend($this->htmlTokens(), [
            'element' => [
                Lexer::CALL => function ($in, $out, $token) {
                    $out->startToken('type element');

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->element());

                    $out->endToken();
                },
            ],
        ]);
    }

    function attribute()
    {
        return Lexer::extend($this->htmlAttribute(), [
            'string' => [
                Lexer::CALL => function ($in, $out, $token) {
                    Lexer::choose($in, $out, $token, [
                        [
                            Lexer::BEFORE => '%\b(match|select|test)=$%i',
                            Lexer::CALL   => function ($in, $out) {
                                $out->startToken('value string xpath');
                            },
                        ],
                        [
                            Lexer::CALL => function ($in, $out) {
                                $out->startToken('value string');
                            },
                        ],
                    ]);

                    $in = new Input();
                    $in->openString($token);

                    Lexer::loop($in, $out, $this->string());

                    $out->endToken();
                },
            ],
        ]);
    }

    function element()
    {
        return Lexer::extend($this->attribute(), $this->htmlElement());
    }

    function string()
    {
        return Lexer::extend($this->htmlString(), [
            'xpath' => [
                Lexer::MATCH => '[{].*?[}]',
                Lexer::WRAP  => 'value xpath',
            ],
        ]);
    }
}
