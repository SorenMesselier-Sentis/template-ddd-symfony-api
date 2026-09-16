<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure;

use App\Shared\Domain\Filter\CursorPagination;
use App\Shared\Domain\Filter\Filters;
use App\Shared\Domain\Filter\Order;
use App\Shared\Domain\Filter\Pagination;
use App\Shared\Domain\ValueObject\Email;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\User\Domain\Mother\EmailMother;
use App\Tests\Unit\User\Domain\Mother\UserMother;
use App\User\Domain\ValueObject\UserId;
use App\User\Infrastructure\Persistence\Doctrine\Repository\DoctrineUserRepository;

final class DoctrineUserRepositoryTest extends IntegrationTestCase
{
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DoctrineUserRepository($this->em);
    }

    public function testItSavesAndFindsAUser(): void
    {
        $user = UserMother::create();

        $this->repository->save($user);

        $found = $this->repository->findById($user->id());

        $this->assertNotNull($found);
        $this->assertTrue($user->id()->equals($found->id()));
    }

    public function testItFindsByEmail(): void
    {
        $email = EmailMother::create('find.me@example.com');
        $user = UserMother::create(email: $email);

        $this->repository->save($user);

        $found = $this->repository->findByEmail($email);

        $this->assertNotNull($found);
        $this->assertEquals('find.me@example.com', $found->email()->value());
    }

    public function testItReturnsNullWhenNotFound(): void
    {
        $found = $this->repository->findById(UserId::random());

        $this->assertNull($found);
    }

    public function testItDetectsExistingEmail(): void
    {
        $email = EmailMother::create('exists@example.com');
        $user = UserMother::create(email: $email);

        $this->repository->save($user);

        $this->assertTrue($this->repository->existsByEmail($email));
        $this->assertFalse($this->repository->existsByEmail(
            Email::fromString('other@example.com')
        ));
    }

    public function testFindByFiltersCursorWalksAllPagesWithoutDuplicatesOrGaps(): void
    {
        $users = [];

        for ($i = 0; $i < 5; ++$i) {
            $user = UserMother::create();
            $this->repository->save($user);
            $users[] = $user;
        }

        $filters = new Filters([], Order::default(), Pagination::fromRequest(1, 20));
        $cursorPagination = CursorPagination::fromRequest(null, 2);
        $seenIds = [];

        do {
            $page = $this->repository->findByFiltersCursor($filters, $cursorPagination);

            foreach ($page->items as $item) {
                $seenIds[] = $item->id()->value();
            }

            $cursorPagination = CursorPagination::fromRequest($page->nextCursor, 2);
        } while (null !== $page->nextCursor);

        $expectedIds = array_map(static fn ($user) => $user->id()->value(), $users);
        sort($expectedIds);
        sort($seenIds);

        $this->assertSame($expectedIds, $seenIds);
        $this->assertCount(5, array_unique($seenIds));
    }

    public function testFindByFiltersCursorReportsNoMoreResultsOnLastPage(): void
    {
        $user = UserMother::create();
        $this->repository->save($user);

        $filters = new Filters([], Order::default(), Pagination::fromRequest(1, 20));
        $page = $this->repository->findByFiltersCursor($filters, CursorPagination::fromRequest(null, 20));

        $this->assertNull($page->nextCursor);
        $this->assertGreaterThanOrEqual(1, \count($page->items));
    }

    public function testFindDeletedBeforeOnlyReturnsUsersDeletedBeforeTheCutoff(): void
    {
        $oldEnough = UserMother::create();
        $oldEnough->delete();
        $this->repository->save($oldEnough);

        $tooRecent = UserMother::create();
        $tooRecent->delete();
        $this->repository->save($tooRecent);

        $stillActive = UserMother::create();
        $this->repository->save($stillActive);

        // Backdate via raw SQL — User::delete() always stamps "now", so the only
        // way to get a deletion that predates a cutoff in a fast test is to move
        // the clock on the row directly rather than racing real time.
        $this->em->getConnection()->executeStatement(
            'UPDATE users SET deleted_at = ? WHERE id = ?',
            [(new \DateTimeImmutable('-60 days'))->format('Y-m-d H:i:s'), $oldEnough->id()->value()],
        );
        $this->em->clear();

        $cutoff = (new \DateTimeImmutable())->modify('-30 days');
        $found = $this->repository->findDeletedBefore($cutoff);

        $foundIds = array_map(static fn ($user) => $user->id()->value(), $found);

        $this->assertContains($oldEnough->id()->value(), $foundIds);
        $this->assertNotContains($tooRecent->id()->value(), $foundIds);
        $this->assertNotContains($stillActive->id()->value(), $foundIds);
    }
}
