<?php

declare(strict_types=1);

namespace App\Repeatable;

use App\Enum\RepeatableTypes;

class RepeatableFactory
{
    public static function getSuitableRepeatableType(string $intervalName): EveryDayRepeatableType|EveryMonthRepeatableType|EveryWeekRepeatableType|NoneRepeatableType|RepetableTypeException
    {
        return match ($intervalName) {
            RepeatableTypes::EveryDay->value => new EveryDayRepeatableType(),
            RepeatableTypes::EveryWeek->value => new EveryWeekRepeatableType(),
            RepeatableTypes::EveryMonth->value => new EveryMonthRepeatableType(),
            RepeatableTypes::None->value => new NoneRepeatableType(),
            default => new RepetableTypeException('No suitable interval'),
        };
    }
}
class RepetableTypeException extends \Exception {}
