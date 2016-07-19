<?php

/*
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author    Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter;

use Closure;

abstract class Lexer
{
    /**
     * Match after a Token.
     */
    const AFTER = 'after';

    /**
     * Match before a Token.
     */
    const BEFORE = 'before';

    /**
     * Call a Closure or listen for STOP.
     */
    const CALL = 'call';

    /**
     * Match a Token, or anywhere in Input.
     */
    const MATCH = 'match';

    /**
     * Stop the current parser.
     */
    const STOP = 'stop';

    /**
     * Wrap the Token in a span element.
     */
    const WRAP = 'wrap';

    /**
     * Choose between multiple matches.
     *
     * @param    Input    $in
     * @param    Output    $out
     * @param    Token    $token
     * @param    array    $choices
     */
    protected static function choose(Input $in, Output $out, Token $token, array $choices)
    {
        foreach ($choices as $option) {
            $success = true;

            // Condition:
            if (isset($option[Lexer::MATCH])) {
                $success = $token->test($option[Lexer::MATCH]);
            }

            // After condition:
            if ($success && isset($option[Lexer::AFTER])) {
                $success = $in->after($option[Lexer::AFTER], true);
            }

            // Before condition:
            if ($success && isset($option[Lexer::BEFORE])) {
                $success = $in->before($option[Lexer::BEFORE], true);
            }

            if ($success) {
                if (isset($option[Lexer::CALL])) {
                    $callback = $option[Lexer::CALL];
                    $callback($in, $out, $token);
                }

                // Wrap the match with a class:
                else if (isset($option[Lexer::WRAP])) {
                    $out->writeToken($token, $option[Lexer::WRAP]);
                }

                break;
            }
        }
    }

    /**
     * Combine multiple templates into one.
     *
     * @param    array    $a
     * @param    array    $b
     * @param    array    ...
     *
     * @return    array
     */
    protected static function extend(array $a, array $b)
    {
        $arrays = func_get_args();
        $merged = [];

        while ($arrays) {
            $array = array_pop($arrays);

            if (!$array) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_string($key)) {
                    if (
                        is_array($value)
                        && array_key_exists($key, $merged)
                        && is_array($merged[$key])
                    ) {
                        $merged[$key] = Lexer::extend($merged[$key], $value);
                    } else {
                        $merged[$key] = $value;
                    }
                } else {
                    $merged[] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Parse input with an array of tokens.
     *
     * @param    Input    $in
     * @param    Output    $out
     * @param    array    $templates
     */
    protected static function loop(Input $in, Output $out, array $templates)
    {
        $last       = $in->after('%^%');
        $expression = '';

        // Prepare templates and build expression:
        foreach ($templates as $name => $template) {
            if (is_array($template[Lexer::MATCH])) {
                $template[Lexer::MATCH] = implode('|', $template[Lexer::MATCH]);
            }

            $expression .= $expression
            ? '|' : '%';

            $expression .= sprintf(
                '(?<%s>%s)',
                $name, $template[Lexer::MATCH]
            );

            unset($template[Lexer::MATCH]);

            $templates[$name] = $template;
        }

        $expression .= '%imsx';

        // Parse input using expression:
        while ($in->valid()) {
            // Find next token:
            preg_match($expression, $in->after(), $match, PREG_OFFSET_CAPTURE);

            // Find the token and its template:
            foreach ($match as $key => $value) {
                if (is_integer($key)) {
                    continue;
                }

                if ($value[1] == -1) {
                    continue;
                }

                if (isset($templates[$key]) === false) {
                    continue;
                }

                $template = $templates[$key];
                $token    = new Token($value[0], $value[1] + $in->after);

                break;
            }

            if (isset($token) === false) {
                break;
            }

            $in->move($token);

            // Output skipped text:
            $out->writeRaw(htmlentities($in->before(
                null, false, $token->position
                 - ($last->position + strlen($last))
            )));

            // Callback:
            if (isset($template[Lexer::CALL])) {
                // Stop parsing:
                if ($template[Lexer::CALL] === Lexer::STOP) {
                    return $token;
                }

                // Trigger a callback:
                else if ($template[Lexer::CALL] instanceof Closure) {
                    $callback = $template[Lexer::CALL];
                    $callback($in, $out, $token);
                }
            }

            // Wrap the token:
            else if (isset($template[Lexer::WRAP])) {
                $out->writeToken($token, $template[Lexer::WRAP]);
            }

            $last = $token;
            unset($token);
        }

        // Input left on stack:
        $token = $in->after();
        $in->move($token);
        $out->writeRaw(htmlentities($token, ENT_QUOTES));
    }

    /**
     * Parse an XPath string.
     *
     * @param Output $out
     * @param string $in
     */
    abstract public function parse(Input $in, Output $out);
}
