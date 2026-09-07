<?php
namespace EWW\Dpf\Tests\Unit\Services\Identifier;

use EWW\Dpf\Services\Identifier\UrnBuilder;
use PHPUnit\Framework\TestCase;

class UrnBuilderTest extends TestCase
{
    /**
     * Regression test for check digit calculation, which used curly-brace
     * string offset access (removed in PHP 8, deprecated in 7.4).
     * Expected value is the algorithm's actual current output (the class
     * docblock's example check digit is stale/illustrative, not accurate).
     */
    public function testGetUrnProducesExpectedCheckDigit()
    {
        $builder = new UrnBuilder('bsz', '14');
        $this->assertSame('urn:nbn:de:bsz:14-qucosa-87654', $builder->getUrn('qucosa-8765'));
    }

    public function testGetCheckDigitReturnsSingleDigit()
    {
        $builder = new UrnBuilder('bsz', '14');
        $checkDigit = $builder->getCheckDigit('qucosa-8765');
        $this->assertMatchesRegularExpression('/^\d$/', $checkDigit);
    }

    public function testGetCheckDigitIsDeterministic()
    {
        $builder = new UrnBuilder('bsz', '14');
        $this->assertSame(
            $builder->getCheckDigit('qucosa-1234'),
            $builder->getCheckDigit('qucosa-1234')
        );
    }

    public function testConstructorRejectsInvalidFirstSubnamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        new UrnBuilder('bs1', '14');
    }

    public function testConstructorRejectsInvalidSecondSubnamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        new UrnBuilder('bsz', '14-');
    }

    public function testGetUrnRejectsInvalidNiss()
    {
        $this->expectException(\InvalidArgumentException::class);
        $builder = new UrnBuilder('bsz', '14');
        $builder->getUrn('qucosa_8765');
    }
}
