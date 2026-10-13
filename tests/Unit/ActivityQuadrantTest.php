<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Activity;

class ActivityQuadrantTest extends TestCase
{
    public function test_get_quadrant_identifies_all_four_quadrants_correctly(): void
    {
        $dummy = new Activity();

        // Quadrant 1: High/Critical priority + High/Critical urgency
        $q1 = new Activity([
            'priority' => 'high',
            'metadata' => ['urgency' => 'high'],
        ]);
        $q1Critical = new Activity([
            'priority' => 'critical',
            'metadata' => ['urgency' => 'critical'],
        ]);
        $this->assertEquals(1, $dummy->getQuadrant($q1));
        $this->assertEquals(1, $dummy->getQuadrant($q1Critical));

        // Quadrant 2: High/Critical priority + Medium/Low urgency
        $q2 = new Activity([
            'priority' => 'high',
            'metadata' => ['urgency' => 'medium'],
        ]);
        $q2Low = new Activity([
            'priority' => 'critical',
            'metadata' => ['urgency' => 'low'],
        ]);
        $this->assertEquals(2, $dummy->getQuadrant($q2));
        $this->assertEquals(2, $dummy->getQuadrant($q2Low));

        // Quadrant 3: Medium/Low priority + High/Critical urgency
        $q3 = new Activity([
            'priority' => 'medium',
            'metadata' => ['urgency' => 'high'],
        ]);
        $q3Low = new Activity([
            'priority' => 'low',
            'metadata' => ['urgency' => 'critical'],
        ]);
        $this->assertEquals(3, $dummy->getQuadrant($q3));
        $this->assertEquals(3, $dummy->getQuadrant($q3Low));

        // Quadrant 4: Medium/Low priority + Medium/Low urgency
        $q4 = new Activity([
            'priority' => 'medium',
            'metadata' => ['urgency' => 'medium'],
        ]);
        $q4Low = new Activity([
            'priority' => 'low',
            'metadata' => ['urgency' => 'low'],
        ]);
        $this->assertEquals(4, $dummy->getQuadrant($q4));
        $this->assertEquals(4, $dummy->getQuadrant($q4Low));
    }
}
