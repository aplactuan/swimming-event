<?php

namespace Tests\Unit;

use App\Models\AgeBracket;
use App\Models\Classification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassificationEffectiveAgeBracketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_classification_uses_its_own_age_brackets(): void
    {
        $root = Classification::factory()->create();
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $root->id,
            'name' => '8-10',
        ]);

        $this->assertFalse($root->inheritsAgeBrackets());
        $this->assertTrue($root->effectiveAgeBrackets()->contains('id', $bracket->id));
    }

    public function test_child_inherits_parent_age_brackets_when_it_has_none(): void
    {
        $parent = Classification::factory()->create();
        $parentBracket = AgeBracket::factory()->create([
            'classification_id' => $parent->id,
            'name' => '9 and below',
        ]);
        $child = Classification::factory()->childOf($parent)->create();

        $this->assertTrue($child->inheritsAgeBrackets());
        $this->assertCount(1, $child->effectiveAgeBrackets());
        $this->assertTrue($child->effectiveAgeBrackets()->contains('id', $parentBracket->id));
    }

    public function test_child_uses_own_age_brackets_when_set(): void
    {
        $parent = Classification::factory()->create();
        AgeBracket::factory()->create([
            'classification_id' => $parent->id,
            'name' => 'Parent bracket',
        ]);
        $child = Classification::factory()->childOf($parent)->create();
        $childBracket = AgeBracket::factory()->create([
            'classification_id' => $child->id,
            'name' => 'Child bracket',
        ]);

        $this->assertFalse($child->inheritsAgeBrackets());
        $this->assertCount(1, $child->effectiveAgeBrackets());
        $this->assertTrue($child->effectiveAgeBrackets()->contains('id', $childBracket->id));
        $this->assertFalse($child->effectiveAgeBrackets()->contains('name', 'Parent bracket'));
    }
}
