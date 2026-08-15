<?php

namespace Tests\Unit;

use App\Models\AgeBracket;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AgeBracketBirthdateMatchingTest extends TestCase
{
    public function test_closed_range_matches_inclusive_boundaries(): void
    {
        $bracket = new AgeBracket([
            'start_birthday' => '2014-01-01',
            'end_birthday' => '2016-12-31',
        ]);

        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2014-01-01')));
        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2015-06-15')));
        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2016-12-31')));
        $this->assertFalse($bracket->matchesBirthdate(CarbonImmutable::parse('2013-12-31')));
        $this->assertFalse($bracket->matchesBirthdate(CarbonImmutable::parse('2017-01-01')));
    }

    public function test_open_ended_younger_bracket_matches_on_or_after_start(): void
    {
        $bracket = new AgeBracket([
            'start_birthday' => '2018-01-01',
            'end_birthday' => null,
        ]);

        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2018-01-01')));
        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2020-05-01')));
        $this->assertFalse($bracket->matchesBirthdate(CarbonImmutable::parse('2017-12-31')));
    }

    public function test_open_ended_older_bracket_matches_on_or_before_end(): void
    {
        $bracket = new AgeBracket([
            'start_birthday' => null,
            'end_birthday' => '2012-12-31',
        ]);

        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2012-12-31')));
        $this->assertTrue($bracket->matchesBirthdate(CarbonImmutable::parse('2010-01-01')));
        $this->assertFalse($bracket->matchesBirthdate(CarbonImmutable::parse('2013-01-01')));
    }

    public function test_bracket_without_dates_matches_nothing(): void
    {
        $bracket = new AgeBracket([
            'start_birthday' => null,
            'end_birthday' => null,
        ]);

        $this->assertFalse($bracket->matchesBirthdate(CarbonImmutable::parse('2015-01-01')));
    }
}
