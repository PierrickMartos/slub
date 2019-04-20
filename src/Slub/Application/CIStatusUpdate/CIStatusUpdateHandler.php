<?php

declare(strict_types=1);

namespace Slub\Application\CIStatusUpdate;

use Psr\Log\LoggerInterface;
use Slub\Application\NotifySquad\ChatClient;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\Repository\RepositoryIdentifier;
use Slub\Domain\Query\IsSupportedInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CIStatusUpdateHandler
{
    public const MESSAGE_CI_GREEN = ':white_check_mark: CI OK';
    public const MESSAGE_CI_RED = ':octagonal_sign: CI Failed';

    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var IsSupportedInterface */
    private $isSupported;

    /** @var ChatClient */
    private $chatClient;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        IsSupportedInterface $isSupported,
        ChatClient $chatClient,
        LoggerInterface $logger
    ) {
        $this->PRRepository = $PRRepository;
        $this->isSupported = $isSupported;
        $this->chatClient = $chatClient;
        $this->logger = $logger;
    }

    public function handle(CIStatusUpdate $CIStatusUpdate): void
    {
        if (!$this->isSupported($CIStatusUpdate)) {
            return;
        }
        $this->updateCIStatus($CIStatusUpdate);
        $this->notifySquad($CIStatusUpdate);
        $this->logIt($CIStatusUpdate);
    }

    private function isSupported(CIStatusUpdate $CIStatusUpdate): bool
    {
        $repositoryIdentifier = RepositoryIdentifier::fromString($CIStatusUpdate->repositoryIdentifier);
        Assert::boolean($CIStatusUpdate->isGreen);

        return $this->isSupported->repository($repositoryIdentifier);
    }

    private function updateCIStatus(CIStatusUpdate $CIStatusUpdate): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($CIStatusUpdate->PRIdentifier));
        if ($CIStatusUpdate->isGreen) {
            $PR->green();
        } else {
            $PR->red();
        }
        $this->PRRepository->save($PR);
    }

    private function notifySquad(CIStatusUpdate $CIStatusUpdate): void
    {
        $PR = $this->PRRepository->getBy(PRIdentifier::fromString($CIStatusUpdate->PRIdentifier));
        $lastMessageIdentifier = last($PR->messageIdentifiers());
        if ($CIStatusUpdate->isGreen) {
            $squadMessage = self::MESSAGE_CI_GREEN;
        } else {
            $squadMessage = self::MESSAGE_CI_RED;
        }
        $this->chatClient->replyInThread($lastMessageIdentifier, $squadMessage);
    }

    private function logIt(CIStatusUpdate $CIStatusUpdate): void
    {
        if ($CIStatusUpdate->isGreen) {
            $logMessage = sprintf('Squad has been notified PR "%s" has a Green CI', $CIStatusUpdate->PRIdentifier);
        } else {
            $logMessage = sprintf('Squad has been notified PR "%s" has a Red CI', $CIStatusUpdate->PRIdentifier);
        }
        $this->logger->info($logMessage);
    }
}
