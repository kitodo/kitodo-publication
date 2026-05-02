<?php
namespace EWW\Dpf\Tests\Unit\Controller;

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

use EWW\Dpf\Helper\SlubInfoHelper;
use PHPUnit\Framework\TestCase;

class GetFileControllerTest extends TestCase
{
    // --- fixtures -----------------------------------------------------------

    /**
     * Minimal SLUB-INFO with one downloadable and one non-downloadable attachment.
     * Uses synthetic IDs — no real document data.
     */
    private function slubInfoTwoAttachments(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<info xmlns:slub="http://slub-dresden.de/">
  <slub:attachments>
    <slub:attachment ref="DS-ALPHA" isDownloadable="yes"/>
    <slub:attachment ref="DS-BETA"  isDownloadable="no"/>
  </slub:attachments>
</info>
XML;
    }

    /** SLUB-INFO with no attachments at all. */
    private function slubInfoNoAttachments(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<info xmlns:slub="http://slub-dresden.de/">
  <slub:attachments/>
</info>
XML;
    }

    /** SLUB-INFO with several attachments, all marked downloadable. */
    private function slubInfoAllDownloadable(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<info xmlns:slub="http://slub-dresden.de/">
  <slub:attachments>
    <slub:attachment ref="DS-001" isDownloadable="yes"/>
    <slub:attachment ref="DS-002" isDownloadable="yes"/>
    <slub:attachment ref="DS-003" isDownloadable="yes"/>
  </slub:attachments>
</info>
XML;
    }

    /** SLUB-INFO where the isDownloadable attribute is missing entirely. */
    private function slubInfoMissingAttribute(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<info xmlns:slub="http://slub-dresden.de/">
  <slub:attachments>
    <slub:attachment ref="DS-GAMMA"/>
  </slub:attachments>
</info>
XML;
    }

    // --- tests --------------------------------------------------------------

    public function testDownloadableAttachmentReturnsTrue(): void
    {
        $this->assertTrue(SlubInfoHelper::isDownloadable($this->slubInfoTwoAttachments(), 'DS-ALPHA'));
    }

    public function testNonDownloadableAttachmentReturnsFalse(): void
    {
        $this->assertFalse(SlubInfoHelper::isDownloadable($this->slubInfoTwoAttachments(), 'DS-BETA'));
    }

    public function testUnknownDsidReturnsFalse(): void
    {
        $this->assertFalse(SlubInfoHelper::isDownloadable($this->slubInfoTwoAttachments(), 'DS-UNKNOWN'));
    }

    public function testNoAttachmentsReturnsFalse(): void
    {
        $this->assertFalse(SlubInfoHelper::isDownloadable($this->slubInfoNoAttachments(), 'DS-ALPHA'));
    }

    public function testAllDownloadableReturnsTrue(): void
    {
        $this->assertTrue(SlubInfoHelper::isDownloadable($this->slubInfoAllDownloadable(), 'DS-002'));
    }

    public function testMissingIsDownloadableAttributeReturnsFalse(): void
    {
        $this->assertFalse(SlubInfoHelper::isDownloadable($this->slubInfoMissingAttribute(), 'DS-GAMMA'));
    }

    public function testInvalidXmlThrowsException(): void
    {
        $this->expectException(\Exception::class);
        SlubInfoHelper::isDownloadable('not valid xml <<<', 'DS-ALPHA');
    }

    public function testDsidIsCaseSensitive(): void
    {
        // XPath @ref match is exact — "ds-alpha" must not match "DS-ALPHA"
        $this->assertFalse(SlubInfoHelper::isDownloadable($this->slubInfoTwoAttachments(), 'ds-alpha'));
    }

    public function testXPathInjectionPayloadReturnsFalse(): void
    {
        // Injection attempt: ' or "1"="1 would match all downloadable attachments
        // if $dsid were interpolated into the XPath query. Must return false.
        $this->assertFalse(SlubInfoHelper::isDownloadable($this->slubInfoTwoAttachments(), '" or "1"="1'));
    }

    // --- isValidPid tests ---------------------------------------------------

    /** @dataProvider validPidProvider */
    public function testIsValidPidAcceptsValidFormats(string $pid): void
    {
        $this->assertTrue(SlubInfoHelper::isValidPid($pid));
    }

    public function validPidProvider(): array
    {
        return [
            'numeric localId'         => ['repo-x:99999'],
            'alpha localId'           => ['repo-x:abcdef'],
            'mixed localId'           => ['repo-x:abc123'],
            'hyphen in namespace'     => ['repo-ns:123'],
            'hyphen in localId'       => ['repo-x:abc-123'],
            'underscore in localId'   => ['repo-x:abc_123'],
            'dot in localId'          => ['repo-x:abc.123'],
            'long namespace'          => ['longnamespace:1'],
        ];
    }

    /** @dataProvider invalidPidProvider */
    public function testIsValidPidRejectsInvalidFormats(string $pid): void
    {
        $this->assertFalse(SlubInfoHelper::isValidPid($pid));
    }

    public function invalidPidProvider(): array
    {
        return [
            'no colon'                => ['repoonly'],
            'empty string'            => [''],
            'starts with digit'       => ['1repo:123'],
            'path traversal'          => ['repo:123/../../etc'],
            'encoded slash'           => ['repo:123%2F..%2F..'],
            'space in localId'        => ['repo:123 456'],
            'double colon'            => ['repo::123'],
            'trailing colon'          => ['repo:'],
            'leading colon'           => [':123'],
            'injection chars'         => ['repo:123" or "1"="1'],
            'newline'                 => ["repo:123\n456"],
            'null byte'               => ["repo:123\x00456"],
        ];
    }

    // --- isValidDsid tests --------------------------------------------------

    /** @dataProvider validDsidProvider */
    public function testIsValidDsidAcceptsValidFormats(string $dsid): void
    {
        $this->assertTrue(SlubInfoHelper::isValidDsid($dsid));
    }

    public function validDsidProvider(): array
    {
        return [
            'uppercase with hyphen'   => ['ATT-0'],
            'all caps'                => ['SLUB-INFO'],
            'all caps no hyphen'      => ['MODS'],
            'mixed case'              => ['MyDatastream'],
            'with digits'             => ['ATT-12'],
            'with dot'                => ['DS.1'],
            'with underscore'         => ['DS_MAIN'],
            'single char'             => ['X'],
        ];
    }

    /** @dataProvider invalidDsidProvider */
    public function testIsValidDsidRejectsInvalidFormats(string $dsid): void
    {
        $this->assertFalse(SlubInfoHelper::isValidDsid($dsid));
    }

    public function invalidDsidProvider(): array
    {
        return [
            'empty string'            => [''],
            'path traversal'          => ['../etc/passwd'],
            'encoded slash'           => ['ATT%2F0'],
            'space'                   => ['ATT 0'],
            'injection chars'         => ['" or "1"="1'],
            'slash'                   => ['ATT/0'],
            'null byte'               => ["ATT\x00"],
            'colon'                   => ['ATT:0'],
        ];
    }
}
