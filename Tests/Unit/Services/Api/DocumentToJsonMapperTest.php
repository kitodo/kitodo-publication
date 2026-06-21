<?php
namespace EWW\Dpf\Tests\Unit\Services\Api;

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
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use EWW\Dpf\Services\Api\DocumentToJsonMapper;

class DocumentToJsonMapperTest extends UnitTestCase
{
    /**
     * @var DocumentToJsonMapper
     */
    protected $mapper;

    /**
     * @var \ReflectionMethod
     */
    protected $crawl;

    /**
     * @var \ReflectionProperty
     */
    protected $xpathProp;

    /**
     * @var \ReflectionProperty
     */
    protected $repeatableIndexProp;

    protected function setUp()
    {
        parent::setUp();

        $this->mapper = $this->getMockBuilder(DocumentToJsonMapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->crawl = new \ReflectionMethod(DocumentToJsonMapper::class, 'crawl');
        $this->crawl->setAccessible(true);

        $this->xpathProp = new \ReflectionProperty(DocumentToJsonMapper::class, 'xpath');
        $this->xpathProp->setAccessible(true);

        $this->repeatableIndexProp = new \ReflectionProperty(DocumentToJsonMapper::class, 'repeatableIndex');
        $this->repeatableIndexProp->setAccessible(true);
    }

    /**
     * Create a DOMXPath from an XML snippet.
     */
    private function buildXpath(string $xml): \DOMXPath
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        return new \DOMXPath($dom);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1983
     *
     * A mapping field declared as an array (list syntax with numeric key 0)
     * must always yield a JSON array — even when there is only one matching node.
     * Previously, single nodes were returned as scalars/objects.
     */
    public function crawl_returns_array_for_single_value_list_field()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <keyword>OpenAccess</keyword>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));

        // JSON mapping: keywords is declared as a list (array with numeric key 0)
        $mapping = [
            'keywords' => [
                0 => ['_mapping' => '//keyword']
            ]
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('keywords', $result);
        $this->assertInternalType('array', $result['keywords'], 'List field with single node must return an array');
        $this->assertCount(1, $result['keywords']);
        $this->assertSame('OpenAccess', $result['keywords'][0]);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1983
     */
    public function crawl_returns_array_for_multiple_value_list_field()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <keyword>OpenAccess</keyword>
  <keyword>PeerReview</keyword>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));

        $mapping = [
            'keywords' => [
                0 => ['_mapping' => '//keyword']
            ]
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('keywords', $result);
        $this->assertInternalType('array', $result['keywords']);
        $this->assertCount(2, $result['keywords']);
        $this->assertSame('OpenAccess', $result['keywords'][0]);
        $this->assertSame('PeerReview', $result['keywords'][1]);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1983
     *
     * Same consistency requirement for compound (object) list fields.
     */
    public function crawl_returns_array_for_single_compound_node_list_field()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <author>
    <name>Smith, John</name>
    <orcid>0000-0001-2345-6789</orcid>
  </author>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));

        $mapping = [
            'authors' => [
                0 => [
                    '_mapping'  => '//author',
                    'name'      => ['_mapping' => 'name'],
                    'orcid'     => ['_mapping' => 'orcid'],
                ]
            ]
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('authors', $result);
        $this->assertInternalType('array', $result['authors'], 'List field with single compound node must return an array');
        $this->assertCount(1, $result['authors']);
        $this->assertSame('Smith, John', $result['authors'][0]['name']);
        $this->assertSame('0000-0001-2345-6789', $result['authors'][0]['orcid']);
    }

    /**
     * @test
     * Non-list fields (no array syntax) must still return scalars for single nodes.
     */
    public function crawl_returns_scalar_for_non_list_field_with_single_node()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <title>My Paper</title>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));

        $mapping = [
            'title' => ['_mapping' => '//title']
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('title', $result);
        $this->assertInternalType('string', $result['title']);
        $this->assertSame('My Paper', $result['title']);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1983
     *
     * When a field key is in the repeatableIndex (derived from the dpf field model),
     * a single matching scalar node must still be wrapped in an array —
     * without requiring [{}] syntax in fis_mapping.
     */
    public function crawl_returns_array_for_model_repeatable_scalar_field_with_single_node()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <keyword>OpenAccess</keyword>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));
        $this->repeatableIndexProp->setValue($this->mapper, ['keywords' => true]);

        // Object syntax (no [0 => ...] wrapping) — cardinality comes from model index
        $mapping = [
            'keywords' => ['_mapping' => '//keyword']
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('keywords', $result);
        $this->assertInternalType('array', $result['keywords'], 'Model-repeatable field with single node must return an array');
        $this->assertCount(1, $result['keywords']);
        $this->assertSame('OpenAccess', $result['keywords'][0]);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1983
     *
     * Same for compound (object) fields: a group key in repeatableIndex with
     * a single matching node must yield an array of objects.
     */
    public function crawl_returns_array_for_model_repeatable_compound_field_with_single_node()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <author>
    <name>Smith, John</name>
    <orcid>0000-0001-2345-6789</orcid>
  </author>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));
        $this->repeatableIndexProp->setValue($this->mapper, ['authors' => true]);

        // Object syntax (no [0 => ...]) — cardinality from model index
        $mapping = [
            'authors' => [
                '_mapping' => '//author',
                'name'     => ['_mapping' => 'name'],
                'orcid'    => ['_mapping' => 'orcid'],
            ]
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('authors', $result);
        $this->assertInternalType('array', $result['authors'], 'Model-repeatable compound field with single node must return an array');
        $this->assertCount(1, $result['authors']);
        $this->assertSame('Smith, John', $result['authors'][0]['name']);
        $this->assertSame('0000-0001-2345-6789', $result['authors'][0]['orcid']);
    }

    /**
     * @test
     * A field not in repeatableIndex and without [{}] syntax must still return scalar.
     */
    public function crawl_returns_scalar_when_not_in_index_and_no_list_syntax()
    {
        $xml = '<?xml version="1.0"?>
<root>
  <title>My Paper</title>
</root>';

        $this->xpathProp->setValue($this->mapper, $this->buildXpath($xml));
        $this->repeatableIndexProp->setValue($this->mapper, []);

        $mapping = [
            'title' => ['_mapping' => '//title']
        ];

        $result = $this->crawl->invoke($this->mapper, $mapping);

        $this->assertArrayHasKey('title', $result);
        $this->assertInternalType('string', $result['title']);
        $this->assertSame('My Paper', $result['title']);
    }
}
