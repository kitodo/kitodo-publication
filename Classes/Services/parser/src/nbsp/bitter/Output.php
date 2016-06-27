<?php

/*
 * A simple, fast yet effective syntax highlighter for PHP.
 *
 * @author    Rowan Lewis <rl@nbsp.io>
 * @package nbsp\bitter
 */

namespace nbsp\bitter;

use XMLWriter;

/**
 * An XML document.
 */
class Output
{
    /**
     * Array of nested classes.
     */
    protected $classStack;

    /**
     * Array of temporary information
     * @var array
     */
    public $tempStack;

    /**
     * Array of open tags
     * @var array
     */
    public $openTokens;

    /**
     * Form value for this xpath
     * @var string;
     */
    public $formValue;

    /**
     * Parent writer object.
     *
     * @var        XMLWriter
     */
    public $writer;

    /**
     * Number of lines processed.
     */
    protected $lineNumber;

    public function __construct()
    {
        $this->writer     = new XMLWriter();
        $this->classStack = [];
        $this->lineNumber = 0;
    }

    public function setFormValue($value)
    {
        //
        $this->formValue = $value;
    }

    /**
     * Called when a line of text is finished.
     */
    public function endLine()
    {
        $this->writer->endElement();
        $this->lineNumber++;
    }

    /**
     * Called when a token is finished.
     */
    public function endToken()
    {
        $this->writer->endElement();
        array_pop($this->classStack);
        array_pop($this->openTokens);
    }

    /**
     * Returns the total number of lines written.
     *
     * @return    integer
     */
    public function getLineCount()
    {
        return $this->lineNumber;
    }

    /**
     * Open memory for writing.
     */
    public function openMemory()
    {
        return $this->writer->openMemory();
    }

    /**
     * Return the data stored in memory.
     */
    public function outputMemory()
    {
        return $this->writer->outputMemory();
    }

    /**
     * Called when a line of text is started.
     */
    public function startLine()
    {
        $this->writer->startElement('span');
        $this->writer->writeAttribute('class', 'line');
        $this->writer->writeAttribute('data-line', $this->lineNumber);
    }

    /**
     * Called when a token is started.
     *
     * @param    string    $class
     */
    public function startToken($class)
    {
        $this->writer->startElement($class);
        //$this->writer->writeAttribute('class', $class);
        $this->classStack[] = $class;
        $this->openTokens[] = $class;
    }

    /**
     * Write a token.
     *
     * @param    string    $data
     * @param    string    $class
     */
    public function writeToken($data, $class)
    {
        print_r($data);
        print_r($class);
        #$this->startToken($class);

        $bits = preg_split('%(\r\n|\n|\r)%', $data, 0, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($bits as $bit) {
            if ($bit === "\r\n" || $bit === "\n" || $bit === "\r") {
                foreach ($this->classStack as $class) {
                    $this->writer->endElement();
                }

                $this->writer->text($bit);
                $this->endLine();
                $this->startLine();

                foreach ($this->classStack as $class) {
                    $this->writer->startElement('span');
                    $this->writer->writeAttribute('class', $class);
                }
            } else {
                // print_r($bit);
                // print_r($this->classStack);
                // print_r($this->openTokens);
                // print_r("<br>");
                $this->writer->text($bit);
            }
        }

        #$this->endToken();
    }

    /**
     * Write raw text.
     *
     * @param    string    $data
     */
    public function writeRaw($data)
    {
        $bits = preg_split('%(\r\n|\n|\r)%', $data, 0, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($bits as $bit) {
            if ($bit === "\r\n" || $bit === "\n" || $bit === "\r") {
                foreach ($this->classStack as $class) {
                    $this->writer->endElement();
                }

                $this->writer->text($bit);
                $this->endLine();
                $this->startLine();

                foreach ($this->classStack as $class) {
                    $this->writer->startElement('span');
                    $this->writer->writeAttribute('class', $class);
                }
            } else {

                $parts = explode('/', trim($bit, '/'));

                if (!empty($parts) && !empty($parts[0])) {
                    foreach ($parts as $key => $value) {
                        $this->startToken($value);
                    }
                }
                #$this->startToken($bit);
                #$this->writer->writeRaw($bit);
            }
        }
    }
}
