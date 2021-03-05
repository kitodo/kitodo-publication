<?php
namespace EWW\Dpf\ViewHelpers\Format;

// backport of TYPO3 8.7!

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Wrapper for PHPs json_encode function.
 *
 * = Examples =
 *
 * <code title="encoding a view variable">
 * {someArray -> f:format.json()}
 * </code>
 * <output>
 * ["array","values"]
 * // depending on the value of {someArray}
 * </output>
 *
 * <code title="associative array">
 * {f:format.json(value: {foo: 'bar', bar: 'baz'})}
 * </code>
 * <output>
 * {"foo":"bar","bar":"baz"}
 * </output>
 *
 * <code title="non-associative array with forced object">
 * {f:format.json(value: {0: 'bar', 1: 'baz'}, forceObject: true)}
 * </code>
 * <output>
 * {"0":"bar","1":"baz"}
 * </output>
 *
 */
class JsonViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('value', 'array', '', true);
        $this->registerArgument('forceObject', 'boolean', '', false, false);
    }

    /**
     * Applies json_encode() on the specified value.
     *
     * Outputs content with its JSON representation. To prevent issues in HTML context, occurrences
     * of greater-than or less-than characters are converted to their hexadecimal representations.
     *
     * If $forceObject is TRUE a JSON object is outputted even if the value is a non-associative array
     * Example: array('foo', 'bar') as input will not be ["foo","bar"] but {"0":"foo","1":"bar"}
     *
     * @see http://www.php.net/manual/en/function.json-encode.php
     * @return string
     */
    public function render()
    {
        $value = $this->arguments['value'];
        $forceObject = $this->arguments['forceObject'];

        $options = JSON_HEX_TAG;
        if ($forceObject !== false) {
            $options = $options | JSON_FORCE_OBJECT;
        }
        return json_encode($value, $options);
    }
}
