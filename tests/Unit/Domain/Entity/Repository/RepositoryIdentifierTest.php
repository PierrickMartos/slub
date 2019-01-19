<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\Repository;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;

class RepositoryIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_repository_identifier()
    {
        $identifier = RepositoryIdentifier::fromString('akeneo/pim-community-dev');
        $this->assertNotNull($identifier);
    }

    /**
     * @test
     */
    public function it_tells_if_it_is_equal_to_another_repository_identifier()
    {
        $identifier = RepositoryIdentifier::fromString('akeneo/pim-community-dev');
        $anotherIdentifier = RepositoryIdentifier::fromString('SamirBoulil/watch');
        $this->assertTrue($identifier->equals($identifier));
        $this->assertFalse($identifier->equals($anotherIdentifier));
    }
}
