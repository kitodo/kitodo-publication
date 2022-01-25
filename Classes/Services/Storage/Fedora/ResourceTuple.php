<?php

namespace EWW\Dpf\Services\Storage\Fedora;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 *
 */

class ResourceTuple
{
    protected $namespaces = [
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'fedora' => 'http://fedora.info/definitions/v4/repository#',
        'ldp' =>  'http://www.w3.org/ns/ldp#',
        'dc' =>  'http://purl.org/dc/elements/1.1/',
        'kp' => 'https://www.kitodo.org/kitodo-publication/'
    ];

    /**
     * @var string
     */
    protected $rdfJson;

    /**
     * @var array
     */
    protected $modifiedValues = [];

    /**
     * @param string $rdf RDF json data
     * @return ResourceTuple
     */
    public static function create(string $rdf) : ResourceTuple
    {
        $resourceTuple = new ResourceTuple();
        $resourceTuple->rdfJson = $rdf;
        return $resourceTuple;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getValue(string $key) : ?string
    {
        $rdf = json_decode($this->rdfJson, true);

        list($prefix,$name) = array_map('trim', explode(':', $key));
        $uri = $this->namespaces[$prefix].$name;
        if (array_key_exists($uri, $rdf[0])) {
            if (array_key_exists(0, $rdf[0][$uri])) {
                if (array_key_exists('@value', $rdf[0][$uri][0])) {
                    return $rdf[0][$uri][0]['@value'];
                }
            }
        }
        return null;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setValue(string $key, string $value)
    {
        $rdf = json_decode($this->rdfJson, true);

        list($prefix,$name) = array_map('trim', explode(':', $key));

        if ($prefix && $name && array_key_exists($prefix, $this->namespaces)) {
            $uri = $this->namespaces[$prefix].$name;

            if (array_key_exists($uri, $rdf[0])) {
                if (array_key_exists(0, $rdf[0][$uri])) {
                    if (array_key_exists('@value', $rdf[0][$uri][0])) {
                        $rdf[0][$uri][0]['@value'] = $value;
                    }
                } else {
                $rdf[0][$uri][0] = ['@value' => $value];
                }
            } else {
                $rdf[0][$uri] = [0 => ['@value' => $value]];
            }

            $this->modifiedValues[$key] = [
                'prefix' => $prefix,
                'namespace' => $this->namespaces[$prefix],
                'uri' => $this->namespaces[$prefix] . $name,
                'value' => $value,
            ];

            $this->rdfJson = json_encode($rdf);
        }
    }

    /**
     * @return array
     */
    public function getModifiedValues() : array
    {
        return $this->modifiedValues;
    }
}
