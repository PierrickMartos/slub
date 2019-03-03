<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Event\CIGreen;
use Slub\Domain\Event\CIRed;
use Slub\Domain\Event\PRMerged;

class PRTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_PR_and_normalizes_itself()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );

        $this->assertSame(
            [
                'IDENTIFIER'  => 'akeneo/pim-community-dev/1111',
                'GTMS'         => 0,
                'NOT_GTMS'     => 0,
                'CI_STATUS'   => 'PENDING',
                'IS_MERGED'   => false,
                'MESSAGE_IDS' => ['1'],
            ],
            $pr->normalize()
        );
    }

    /**
     * @test
     */
    public function it_is_created_from_normalized()
    {
        $normalizedPR = [
            'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
            'GTMS'        => 2,
            'NOT_GTMS'    => 0,
            'CI_STATUS'  => 'GREEN',
            'IS_MERGED'  => true,
            'MESSAGE_IDS' => ['1', '2']
        ];

        $pr = PR::fromNormalized($normalizedPR);

        $this->assertSame($normalizedPR, $pr->normalize());
    }

    /**
     * @test
     * @dataProvider normalizedWithMissingInformation
     */
    public function it_throws_if_there_is_not_enough_information_to_create_from_normalized(
        array $normalizedWithMissingInformation
    ) {
        $this->expectException(\InvalidArgumentException::class);
        PR::fromNormalized($normalizedWithMissingInformation);
    }

    /**
     * @test
     */
    public function it_can_be_GTM_multiple_times()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );
        $this->assertEquals(0, $pr->normalize()['GTMS']);

        $pr->GTM();
        $this->assertEquals(1, $pr->normalize()['GTMS']);

        $pr->GTM();
        $this->assertEquals(2, $pr->normalize()['GTMS']);
    }

    /**
     * @test
     */
    public function it_can_be_NOT_GTM_multiple_times()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );
        $this->assertEquals(0, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM();
        $this->assertEquals(1, $pr->normalize()['NOT_GTMS']);

        $pr->notGTM();
        $this->assertEquals(2, $pr->normalize()['NOT_GTMS']);
    }

    /**
     * @test
     */
    public function it_can_become_green()
    {
        $pr = PR::create(
            PRIdentifier::fromString('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );
        $pr->green();
        $this->assertEquals($pr->normalize()['CI_STATUS'], 'GREEN');
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(CIGreen::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_can_become_red()
    {
        $pr = PR::create(PRIdentifier::fromString('akeneo/pim-community-dev/1111'), MessageIdentifier::fromString('1'));
        $pr->red();
        $this->assertEquals($pr->normalize()['CI_STATUS'], 'RED');
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(CIRed::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_can_be_merged()
    {
        $pr = PR::create(PRIdentifier::fromString('akeneo/pim-community-dev/1111'), MessageIdentifier::fromString('1'));
        $pr->merged();
        $this->assertEquals($pr->normalize()['IS_MERGED'], true);
        $this->assertCount(1, $pr->getEvents());
        $this->assertInstanceOf(PRMerged::class, current($pr->getEvents()));
    }

    /**
     * @test
     */
    public function it_returns_its_identifier()
    {
        $identifier = PRIdentifier::create('akeneo/pim-community-dev/1111');

        $pr = PR::create($identifier, MessageIdentifier::fromString('1'));

        $this->assertTrue($pr->PRIdentifier()->equals($identifier));
    }

    /**
     * @test
     */
    public function it_returns_the_message_ids()
    {
        $pr = PR::create(
            PRIdentifier::create('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );
        $this->assertEquals('1', current($pr->messageIds())->stringValue());
    }

    /**
     * @test
     */
    public function it_can_be_put_to_review_multiple_times()
    {
        $pr = PR::create(
            PRIdentifier::fromString('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );
        $pr->putToReviewAgainViaMessage(MessageIdentifier::create('2'));
        $this->assertEquals($pr->normalize()['MESSAGE_IDS'], ['1', '2']);
    }

    /**
     * @test
     */
    public function it_can_be_put_to_review_multiple_times_with_the_same_message()
    {
        $pr = PR::create(
            PRIdentifier::fromString('akeneo/pim-community-dev/1111'),
            MessageIdentifier::fromString('1')
        );
        $pr->putToReviewAgainViaMessage(MessageIdentifier::create('1'));
        $this->assertEquals($pr->normalize()['MESSAGE_IDS'], ['1']);
    }

    public function normalizedWithMissingInformation(): array
    {
        return [
            'Missing identifier'     => [
                [
                    'GTMS'       => 0,
                    'NOT_GTMS'   => 0,
                    'CI_STATUS' => 'PENDING',
                    'IS_MERGED' => false,
                ],
            ],
            'Missing GTMS'            => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'NOT_GTMS'    => 0,
                    'CI_STATUS'  => 'PENDING',
                    'IS_MERGED'  => false,
                ],
            ],
            'Missing NOT GTMS'        => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'GTMS'        => 0,
                    'CI_STATUS'  => 'PENDING',
                    'IS_MERGED'  => false,
                ],
            ],
            'Missing CI status'      => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'GTMS'        => 0,
                    'NOT_GTMS'    => 0,
                    'IS_MERGED'  => false,

                ],
            ],
            'Missing is merged flag' => [
                [
                    'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                    'GTMS'        => 0,
                    'NOT_GTMS'    => 0,
                    'CI_STATUS'  => 'PENDING',
                ],
            ],
        ];
    }
}
