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
use EWW\Dpf\Services\Api\InvalidJson;
use EWW\Dpf\Services\Api\JsonToDocumentMapper;
use EWW\Dpf\Domain\Model\InputOptionList;
use EWW\Dpf\Domain\Model\MetadataObject;

class JsonToDocumentMapperTest extends UnitTestCase
{
    /**
     * @var JsonToDocumentMapper
     */
    protected $mapper;

    /**
     * @var \ReflectionMethod
     */
    protected $checkMetadata;

    protected function setUp()
    {
        parent::setUp();

        $this->mapper = $this->getMockBuilder(JsonToDocumentMapper::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->checkMetadata = new \ReflectionMethod(JsonToDocumentMapper::class, 'checkMetadata');
        $this->checkMetadata->setAccessible(true);
    }

    /**
     * Build a MetadataObject mock with a configured InputOptionList.
     *
     * @param string $valueList Pipe-separated option values
     * @param string $labelList Pipe-separated option labels
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function buildSelectMetadataObject(string $valueList, string $labelList)
    {
        $optionList = $this->getMockBuilder(InputOptionList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValueList', 'getValueLabelList'])
            ->getMock();
        $optionList->method('getValueList')->willReturn($valueList);
        $optionList->method('getValueLabelList')->willReturn($labelList);

        $metadataObject = $this->getMockBuilder(MetadataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInputField', 'getInputOptionList'])
            ->getMock();
        $metadataObject->method('getInputField')->willReturn(MetadataObject::select);
        $metadataObject->method('getInputOptionList')->willReturn($optionList);

        return $metadataObject;
    }

    /**
     * Build a MetadataObject mock with no InputOptionList (plain text field).
     */
    private function buildTextMetadataObject()
    {
        $metadataObject = $this->getMockBuilder(MetadataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInputField', 'getInputOptionList'])
            ->getMock();
        $metadataObject->method('getInputField')->willReturn(MetadataObject::input);
        $metadataObject->method('getInputOptionList')->willReturn(null);

        return $metadataObject;
    }

    /**
     * Inject a mock MetadataObjectRepository into the mapper.
     * Returns the mock for further configuration.
     */
    private function injectMetadataObjectRepository(array $uidToObjectMap)
    {
        $repo = $this->getMockBuilder(\EWW\Dpf\Domain\Repository\MetadataObjectRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByUid'])
            ->getMock();

        $repo->method('findByUid')->willReturnCallback(function ($uid) use ($uidToObjectMap) {
            return $uidToObjectMap[$uid] ?? null;
        });

        $repoProp = new \ReflectionProperty(JsonToDocumentMapper::class, 'metadataObjectRepository');
        $repoProp->setAccessible(true);
        $repoProp->setValue($this->mapper, $repo);

        return $repo;
    }

    /**
     * Build a minimal $metaData array as produced by getMetadataFromJson().
     */
    private function buildMetaData(int $metadataObjectUid, string $value): array
    {
        return [
            [
                'jsonGroupName' => 'testGroup',
                'metadataGroup' => 1,
                'items' => [
                    [
                        '_index'   => null,
                        '_action'  => null,
                        'objects'  => [
                            [
                                'jsonObjectName'  => 'testField',
                                'metadataObject'  => $metadataObjectUid,
                                'items'           => [
                                    [
                                        '_value'  => $value,
                                        '_index'  => 0,
                                        '_action' => 'add',
                                    ]
                                ],
                            ]
                        ],
                    ]
                ],
            ]
        ];
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1975
     *
     * When a select field receives a value that is not in its configured
     * option list, checkMetadata() must throw InvalidJson so the API can
     * return a meaningful error response (422-style) instead of silently
     * accepting the invalid value.
     */
    public function checkMetadata_throws_for_invalid_option_value()
    {
        $metadataObject = $this->buildSelectMetadataObject('yes|no', 'Yes|No');
        $this->injectMetadataObjectRepository([42 => $metadataObject]);

        $metaData = $this->buildMetaData(42, 'INVALID_VALUE');

        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessageRegExp('/INVALID_VALUE/');

        $this->checkMetadata->invoke($this->mapper, $metaData, false, false);
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1975
     */
    public function checkMetadata_does_not_throw_for_valid_option_value()
    {
        $metadataObject = $this->buildSelectMetadataObject('yes|no', 'Yes|No');
        $this->injectMetadataObjectRepository([42 => $metadataObject]);

        $metaData = $this->buildMetaData(42, 'yes');

        $exception = null;
        try {
            $this->checkMetadata->invoke($this->mapper, $metaData, false, false);
        } catch (InvalidJson $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'No InvalidJson should be thrown for a valid option value');
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1975
     */
    public function checkMetadata_does_not_throw_for_text_field_with_any_value()
    {
        $metadataObject = $this->buildTextMetadataObject();
        $this->injectMetadataObjectRepository([99 => $metadataObject]);

        $metaData = $this->buildMetaData(99, 'Any free text value');

        $exception = null;
        try {
            $this->checkMetadata->invoke($this->mapper, $metaData, false, false);
        } catch (InvalidJson $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'Free-text fields must never trigger option validation');
    }

    /**
     * @test
     * @see https://git.slub-dresden.de/kitodo-publication/issues/-/issues/1975
     */
    public function checkMetadata_does_not_throw_for_empty_optional_value()
    {
        $metadataObject = $this->buildSelectMetadataObject('yes|no', 'Yes|No');
        $this->injectMetadataObjectRepository([42 => $metadataObject]);

        $metaData = $this->buildMetaData(42, '');

        $exception = null;
        try {
            $this->checkMetadata->invoke($this->mapper, $metaData, false, false);
        } catch (InvalidJson $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'Empty value on optional select field must be accepted');
    }
}
