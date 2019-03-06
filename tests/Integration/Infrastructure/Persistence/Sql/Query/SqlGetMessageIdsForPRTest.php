<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SqlGetMessageIdsForPRTest extends KernelTestCase
{
    private const PR_IDENTIFIER = 'akeneo/pim-community-dev/1111';

    /** @var GetMessageIdsForPR */
    private $fileBasedGetMessageIdsForPR;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileBasedGetMessageIdsForPR = $this->get('slub.infrastructure.persistence.get_message_ids_for_pr');
        $this->resetDB();
    }

    /**
     * @test
     */
    public function it_returns_the_message_ids_for_a_given_PR_identifier()
    {
        $this->createPRWithMessageIds(['1', '2']);
        $messageIds = $this->fileBasedGetMessageIdsForPR->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertMessageIds(['1', '2'], $messageIds);
    }

    /**
     * @test
     */
    public function it_throws_if_the_PR_does_not_exists()
    {
        $this->expectException(PRNotFoundException::class);
        $this->fileBasedGetMessageIdsForPR->fetch(PRIdentifier::fromString('unknown_identifier'));
    }

    /**
     * @test
     */
    public function it_throws_if_the_PR_does_not_have_any_message()
    {
        $this->expectException(PRNotFoundException::class);
        $this->fileBasedGetMessageIdsForPR->fetch(PRIdentifier::fromString('unknown_identifier'));
    }

    private function createPRWithMessageIds(array $messageIds): void
    {
        /** @var PRRepositoryInterface $fileBasedPRRepository */
        $fileBasedPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $PR = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            MessageIdentifier::fromString(current($messageIds))
        );
        for ($i = 1, $iMax = \count($messageIds); $i < $iMax; $i++) {
            $PR->putToReviewAgainViaMessage(MessageIdentifier::fromString($messageIds[$i]));
        }
        $fileBasedPRRepository->save($PR);
    }

    private function assertMessageIds(array $expectedMessageIds, array $actualMessageIds): void
    {
        $normalizedActualMessageIds = array_map(function (MessageIdentifier $messageId) {
            return $messageId->stringValue();
        }, $actualMessageIds);
        $this->assertEquals($expectedMessageIds, $normalizedActualMessageIds);
    }

    private function resetDB(): void
    {
        $sqlPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $sqlPRRepository->reset();
    }
}
