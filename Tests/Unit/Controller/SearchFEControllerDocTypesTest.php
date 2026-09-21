<?php
namespace EWW\Dpf\Tests\Unit\Controller;

use EWW\Dpf\Controller\SearchFEController;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class SearchFEControllerDocTypesTest extends UnitTestCase
{
    private const TYPES = ['article' => 'Zeitschriftenartikel', 'researchData' => 'Forschungsdaten', 'software' => 'Software'];

    /**
     * @test
     */
    public function removesExcludedTypesAndTrimsNames()
    {
        $this->assertSame(
            ['article' => 'Zeitschriftenartikel'],
            SearchFEController::withoutExcludedDocTypes(self::TYPES, 'researchData, software')
        );
    }

    /**
     * @test
     */
    public function emptySettingKeepsAllTypes()
    {
        $this->assertSame(self::TYPES, SearchFEController::withoutExcludedDocTypes(self::TYPES, ''));
    }

    /**
     * @test
     */
    public function unknownNamesAreIgnored()
    {
        $this->assertSame(self::TYPES, SearchFEController::withoutExcludedDocTypes(self::TYPES, 'nope,,'));
    }
}
