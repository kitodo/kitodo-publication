<?php
namespace EWW\Dpf\Tests\Unit\Helper;

use EWW\Dpf\Helper\DocumentTypeGuard;
use PHPUnit\Framework\TestCase;

/**
 * A submission may only use a document type that the storage-aware lookup
 * (findOneByUid) finds for the current client. The type uid comes from the
 * request, so a type of another client (other storage pid) must be rejected.
 */
class DocumentTypeGuardTest extends TestCase
{
    /** Stub repository. $result is what findOneByUid returns for the current storage. */
    private function repository($result, array &$calls = [])
    {
        return new class($result, $calls) {
            private $result;
            private $calls;

            public function __construct($result, array &$calls)
            {
                $this->result = $result;
                $this->calls = &$calls;
            }

            public function findOneByUid($uid)
            {
                $this->calls[] = $uid;
                return $this->result;
            }
        };
    }

    public function testAcceptsTypeFoundInCurrentStorage()
    {
        DocumentTypeGuard::assertAvailable($this->repository(new \stdClass()), 6);
        $this->addToAssertionCount(1);
    }

    public function testRejectsTypeOfAnotherStorage()
    {
        // findOneByUid returns null for a type on a different storage pid (e.g. uid 196 on pid 177)
        $this->expectException(\InvalidArgumentException::class);
        DocumentTypeGuard::assertAvailable($this->repository(null), 196);
    }

    public function testRejectsEmptyUidWithoutQueryingRepository()
    {
        $calls = [];
        try {
            DocumentTypeGuard::assertAvailable($this->repository(new \stdClass(), $calls), 0);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame([], $calls);
        }
    }

    public function testRejectsNonNumericUid()
    {
        $this->expectException(\InvalidArgumentException::class);
        DocumentTypeGuard::assertAvailable($this->repository(new \stdClass()), '6 OR 1=1');
    }
}
