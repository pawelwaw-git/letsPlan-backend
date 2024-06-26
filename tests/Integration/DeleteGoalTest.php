<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Goal;
use App\Factory\CategoryFactory;
use App\Factory\GoalFactory;
use App\Factory\TaskCalendarFactory;
use App\Repository\GoalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @internal
 *
 * @coversNothing
 */
class DeleteGoalTest extends KernelTestCase
{
    use Factories;

    private GoalRepository $goal_repository;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->goal_repository = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Goal::class)
        ;
    }

    /**
     * @test
     */
    public function canDeleteGoalWithTaskCalendar(): void
    {
        // GIVEN
        $goal = $this->createGoalWithTaskCalendar();

        // WHEN
        $this->removeGoal($goal);

        // THEN
        $this->goalIsNotExists();
    }

    private function createGoalWithTaskCalendar(): Goal
    {
        CategoryFactory::createOne();
        $goal = GoalFactory::createOne();
        TaskCalendarFactory::createOne(['Goal' => $goal]);

        return $goal->object();
    }

    private function removeGoal(Goal $goal): void
    {
        $this->goal_repository->remove($goal);
        $this->goal_repository->flush();
    }

    private function goalIsNotExists(): void
    {
        $result = $this->goal_repository->findAll();
        $this->assertEmpty($result);
    }
}
