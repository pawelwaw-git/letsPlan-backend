<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\DataFixtures\AppFixtures;
use App\Entity\Goal;
use App\Enum\GoalTypes;
use App\Enum\RepeatableTypes;
use App\Factory\GoalFactory;
use App\Repository\GoalRepository;
use App\Repository\TaskCalendarRepository;
use App\Service\GoalScheduler\GoalScheduler;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;

use function PHPUnit\Framework\assertEquals;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class TasksContext implements Context
{
    private TaskCalendarRepository $taskCalendarRepository;
    private GoalRepository $goalRepository;

    private GoalScheduler $goalScheduler;

    public function __construct(TaskCalendarRepository $taskCalendarRepository, GoalRepository $goalRepository, GoalScheduler $goalScheduler)
    {
        $this->taskCalendarRepository = $taskCalendarRepository;
        $this->goalRepository = $goalRepository;
        $this->goalScheduler = $goalScheduler;
    }

    /**
     * @Given There are different goal types
     */
    public function thereAreDifferentGoalTypes(): void
    {
        $fixtures = new AppFixtures($this->goalScheduler);
        $fixtures->loadCategories();
        GoalFactory::createSequence(
            [
                ['Repeatable' => RepeatableTypes::EveryDay->value, 'Active' => true],
                ['Repeatable' => RepeatableTypes::EveryWeek->value, 'Active' => true],
                ['Repeatable' => RepeatableTypes::EveryMonth->value, 'Active' => true],
                ['Repeatable' => RepeatableTypes::None->value, 'Active' => true],
            ]
        );
    }

    /**
     * @Given there is :type Goal with  lastDate :lastDateOrNull in db
     */
    public function thereIsGoalWithLatestInDb(?string $type, ?string $lastDateOrNull): void
    {
        $goal = new Goal();
        $goal->setName('test Goal');
        $goal->setPriority(1);
        $goal->setDescription('test Goal');
        $goal->setType(GoalTypes::SimpleHabit->value);
        $goal->setRepeatable($type);
        $goal->setActive(true);
        if ($lastDateOrNull !== 'null') {
            $goal->setLastDateSchedule(\DateTime::createFromFormat('Y-m-d', $lastDateOrNull));
        }
        $this->goalRepository->save($goal, true);
    }

    /**
     * @Given there is no active Goals in db
     */
    public function thereIsNoActiveGoalsInDb(): void
    {
        $tasks = $this->taskCalendarRepository->findAll();
        foreach ($tasks as $task) {
            $this->taskCalendarRepository->remove($task);
        }
        $goals = $this->goalRepository->findAll();
        foreach ($goals as $goal) {
            $this->goalRepository->remove($goal);
        }
        $this->taskCalendarRepository->flush();
        $this->goalRepository->flush();
    }

    /**
     * @Then there are planed :type tasks in db
     */
    public function thereArePlanedTasksInDb(?string $type, ?string $startDate): void
    {
        $this->thereArePlanedTasksInDbFromDate($type, $startDate);
    }

    /**
     * @Then there are planed :type tasks in db from date :startDate
     */
    public function thereArePlanedTasksInDbFromDate(?string $type, ?string $startDate = null): void
    {
        if ($startDate !== false) {
            $startDate = \DateTime::createFromFormat('Y-m-d', $startDate);
        }
        // strange this, $startDate return false in console, but if I use else there is not working !== don't work also
        if ($startDate === false) {
            $startDate = null;
        }

        $days_diff = $this->getQuantityOfPlannedDays($startDate);

        $from_days = match ($type) {
            RepeatableTypes::EveryMonth->value => (int) $days_diff->format('%y') * 12 + (int) $days_diff->format('%m'),
            RepeatableTypes::EveryWeek->value => ceil($days_diff->days / 7),
            RepeatableTypes::EveryDay->value => $days_diff->days,
            default => 0,
        };

        $expected_from_db = $this->taskCalendarRepository->getQuantityOfTasksTypes($type);
        assertEquals($expected_from_db, $from_days);
    }

    /**
     * @Given there are following Goals with lastDates in db:
     */
    public function thereAreFollowingGoalsWithLastDatesInDb(TableNode $table): void
    {
        foreach ($table as $row) {
            $this->thereIsGoalWithLatestInDb($row['goal_type'], $row['lastDate']);
        }
    }

    /**
     * @Then there are following planed tasks in db:
     */
    public function thereAreFollowingPlanedTasksInDb(TableNode $table): void
    {
        foreach ($table as $row) {
            $this->thereArePlanedTasksInDb($row['goal_type'], $row['startDate']);
        }
    }

    private function getQuantityOfPlannedDays(?\DateTime $startDate): \DateInterval
    {
        $today = new \DateTime('today');
        if ($startDate == null) {
            $startDate = new \DateTime('today');
        } else {
            $startDate->modify('+1 day');
        }
        $startDate->setTime(0, 0, 0);
        $end = clone $today;
        $end->add(new \DateInterval(GoalScheduler::SCHEDULE_DATE_INTERVAL_TEXT));

        return $end->diff($startDate);
    }
}
