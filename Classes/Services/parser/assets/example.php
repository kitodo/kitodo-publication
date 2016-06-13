<?php

/**
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author  Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter;

use Exception;

/**
 * Default limit for how long `after()` is.
 */
const LIMIT_AFTER = 1024;

/**
 * Default limit for how long `before()` is.
 */
const LIMIT_BEFORE = 256;

/**
 * Represents a chunk of source code.
 */
class Input
{
    /**
     * The template data.
     */
    protected $data;

    /**
     * The position of `after()`.
     */
    public $after;

    /**
     * Cached data after the current position.
     */
    public $dataAfter;

    /**
     * The position of `before()`.
     */
    public $before;

    /**
     * Cached data before the current position.
     */
    public $dataBefore;

    /**
     * Total length of data.
     */
    public $length;

    /**
     * Open a URI.
     *
     * @param   string  $uri
     */
    public function openUri($uri)
    {
        if (file_exists($uri) === false) {
            throw new Exception(sprintf(
                "File '%s' does not exist.", $uri
            ));
        }

        $this->openString(file_get_contents($uri));
    }

    /**
     * Open a string.
     *
     * @param   string  $input
     */
    public function openString($data)
    {
        $this->data   = $data;
        $this->after  = 0;
        $this->before = 0;
        $this->length = strlen($data);
    }

    /**
     * Return the data as a string.
     */
    public function __toString()
    {
        return (string) $this->data;
    }

    /**
     * Perform a forwards search of the data and return an Token
     * representing the expression matched, and its position.
     *
     * @param   string  $expression Return matches of regex
     * @param   boolean $test       Perform simple test instead
     * @param   integer $limit      Maximum number of characters
     *
     * @return  Token on success null on failure
     */
    public function after($expression = null, $test = false, $limit = LIMIT_AFTER)
    {
        if (isset($this->dataAfter) && $limit == LIMIT_AFTER) {
            $after = $this->dataAfter;
        } else {
            $after           = substr($this->data, $this->after, $this->after + $limit);
            $this->dataAfter = $after;
        }

        if ($test === false) {
            if ($expression === null) {
                return new Token(substr($this->data, $this->after), $this->after);
            }

            preg_match($expression, $after, $match, PREG_OFFSET_CAPTURE);

            if (!isset($match[0][0]) || !isset($match[0][1])) {
                return null;
            }

            return new Token($match[0][0], $match[0][1] + $this->after);
        } else {
            if ($expression === null) {
                return $this->after < $this->length;
            }

            return (boolean) preg_match($expression, $after);
        }
    }

    /**
     * Perform a backwards search of the data and return an Token
     * representing the expression matched, and its position.
     *
     * @param   string  $expression Return matches of regex
     * @param   boolean $test       Perform simple test instead
     * @param   integer $limit      Maximum number of characters
     *
     * @return  Token on success null on failure
     */
    public function before($expression = null, $test = false, $limit = LIMIT_BEFORE)
    {
        if (
            isset($this->dataBefore)
            && $limit == strlen($this->dataBefore)
        ) {
            $before = $this->dataBefore;
        } else {
            $limit            = max(0, $this->before - $limit);
            $before           = substr($this->data, $limit, $this->before - $limit);
            $this->dataBefore = $before;
        }

        if ($test === false) {
            if ($expression === null) {
                return new Token($before, $this->before);
            }

            preg_match($expression, $before, $match, PREG_OFFSET_CAPTURE);

            if (!isset($match[0][0]) || !isset($match[0][1])) {
                return null;
            }

            return new Token($match[0][0], $match[0][1] + $limit);
        } else {
            if ($expression === null) {
                return $this->before > 0;
            }

            return (boolean) preg_match($expression, $before);
        }
    }

    /**
     * Move the internal cursor to the position of a token.
     *
     * @param   Token   $token
     *
     * @return  array
     */
    public function move($token)
    {
        $this->before = $token->position;
        $this->after  = $token->position + strlen($token->value);

        unset($this->dataAfter, $this->dataBefore);
    }

    public function valid()
    {
        return $this->after < $this->length;
    }
}
