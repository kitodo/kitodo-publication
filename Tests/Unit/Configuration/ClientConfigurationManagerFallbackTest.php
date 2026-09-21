<?php
namespace EWW\Dpf\Tests\Unit\Configuration;

use EWW\Dpf\Configuration\ClientConfigurationManager;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * CLI indexing has no page context, so the TypoScript settings are empty there.
 */
class ClientConfigurationManagerFallbackTest extends UnitTestCase
{
    private function managerWithSettings($settings): ClientConfigurationManager
    {
        $manager = $this->getMockBuilder(ClientConfigurationManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTypoScriptSettings'])
            ->getMock();
        $manager->method('getTypoScriptSettings')->willReturn($settings);
        return $manager;
    }

    /**
     * @test
     */
    public function peerReviewValuesFallBackToDefaultsWithoutTypoScript()
    {
        $values = $this->managerWithSettings([])->getPeerReviewValues();
        $this->assertSame('yes', $values['true']);
        $this->assertSame('no', $values['false']);
    }

    /**
     * @test
     */
    public function configuredPeerReviewValuesWin()
    {
        $configured = ['true' => 'ja', 'false' => 'nein', 'unknown' => 'unbekannt'];
        $values = $this->managerWithSettings(['peerReviewValues' => $configured])->getPeerReviewValues();
        $this->assertSame($configured, $values);
    }

    /**
     * @test
     */
    public function openAccessValuesFallBackToDefaultsWithoutTypoScript()
    {
        $values = $this->managerWithSettings([])->getOpenAccessValues();
        $this->assertSame('Open Access', $values['true']);
        $this->assertSame('http://purl.org/coar/access_right/c_abf2', $values['trueUri']);
    }
}
