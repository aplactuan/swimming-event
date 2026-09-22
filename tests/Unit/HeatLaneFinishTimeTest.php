<?php

namespace Tests\Unit;

use App\Models\HeatLane;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HeatLaneFinishTimeTest extends TestCase
{
    /**
     * Every shape the heat timing screen can produce must parse.
     *
     * @return array<string, array{string, int|null}>
     */
    public static function finishTimes(): array
    {
        return [
            'hundredths only' => ['0.03', 3],
            'seconds and hundredths' => ['0.38', 38],
            'single digit seconds' => ['3.82', 382],
            'two digit seconds' => ['38.20', 3820],
            'a minute' => ['1:02.45', 6245],
            'many minutes' => ['11:02.45', 66245],
            'trailing hundredth' => ['38.2', 3820],
            'whole seconds' => ['45', 4500],
            'blank' => ['', null],
            'whitespace' => ['   ', null],
            'not a time' => ['fast', null],
        ];
    }

    #[DataProvider('finishTimes')]
    public function test_it_parses_a_finish_time_into_hundredths(string $value, ?int $expected): void
    {
        $this->assertSame($expected, HeatLane::parseFinishTime($value));
    }

    public function test_a_null_value_clears_the_time(): void
    {
        $this->assertNull(HeatLane::parseFinishTime(null));
    }
}
