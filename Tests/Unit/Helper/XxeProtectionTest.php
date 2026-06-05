<?php
namespace EWW\Dpf\Tests\Unit\Helper;

use EWW\Dpf\Helper\XPath;
use PHPUnit\Framework\TestCase;

class XxeProtectionTest extends TestCase
{
    private function xxeFilePayload(): string
    {
        return '<?xml version="1.0"?>' .
            '<!DOCTYPE root [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>' .
            '<root>&xxe;</root>';
    }

    private function xxeNetworkPayload(): string
    {
        return '<?xml version="1.0"?>' .
            '<!DOCTYPE root [<!ENTITY xxe SYSTEM "http://169.254.169.254/latest/meta-data/">]>' .
            '<root>&xxe;</root>';
    }

    public function testLoadXmlBlocksFileEntityEvenWithLibxmlNoent()
    {
        // Simulate worst case: entity loader re-enabled by caller + LIBXML_NOENT passed.
        // The helper must still block entity expansion via libxml_disable_entity_loader(true).
        $prevState = libxml_disable_entity_loader(false);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        XPath::loadXml($dom, $this->xxeFilePayload(), LIBXML_NOENT);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        libxml_disable_entity_loader($prevState);

        $root = $dom->getElementsByTagName('root')->item(0);
        $content = $root ? $root->nodeValue : '';

        // /etc/passwd starts with "root:" — if entity was resolved, this would be present
        self::assertStringNotContainsString('root:', $content,
            'loadXml must not expand external file entities even when LIBXML_NOENT is set');
    }

    public function testLoadXmlBlocksNetworkEntityEvenWithLibxmlNoent()
    {
        $prevState = libxml_disable_entity_loader(false);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        XPath::loadXml($dom, $this->xxeNetworkPayload(), LIBXML_NOENT);
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        libxml_disable_entity_loader($prevState);

        $root = $dom->getElementsByTagName('root')->item(0);
        $content = $root ? $root->nodeValue : '';

        self::assertStringNotContainsString('ami-', $content,
            'loadXml must not make network requests for external entities');
    }

    public function testLoadSimpleXmlBlocksFileEntity()
    {
        $prevState = libxml_disable_entity_loader(false);

        libxml_use_internal_errors(true);
        $result = XPath::loadSimpleXml($this->xxeFilePayload());
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        libxml_disable_entity_loader($prevState);

        $content = $result ? (string) $result : '';

        self::assertStringNotContainsString('root:', $content,
            'loadSimpleXml must not expand external file entities');
    }

    public function testValidXmlLoadsCorrectly()
    {
        $validXml = '<?xml version="1.0"?><mets:mets xmlns:mets="http://www.loc.gov/METS/"></mets:mets>';

        $dom = new \DOMDocument();
        $result = XPath::loadXml($dom, $validXml);

        self::assertTrue((bool) $result, 'loadXml must successfully load valid XML');
        self::assertSame('mets:mets', $dom->documentElement->tagName);
    }
}
