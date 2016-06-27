<?php

/*
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author    Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter;

/**
 * Represents a value discovered in data, including the position and length.
 */
class Token
{
    /**
     * Position in Input where Token began.
     */
    public $position;

    /**
     * Token value.
     */
    public $value;

    /**
     * Create a new Token object.
     *
     * @param    string    $value
     * @param    integer    $position
     */
    public function __construct($value, $position)
    {
        $this->position = $position;
        $this->value    = $value;
    }

    /**
     * Return the data as a string.
     *
     * @return    string
     */
    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Test the token value with a regular expression.
     *
     * @param    string    $expression
     *
     * @return    boolean
     */
    public function test($expression)
    {
        return (boolean) preg_match($expression, $this->value);
    }
}
