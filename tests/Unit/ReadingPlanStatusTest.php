<?php

namespace Tests\Unit;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

class ReadingPlanStatusTest extends TestCase
{
    public function test_each_status_has_correct_display_label(): void
    {
        $this->assertSame(
            '未完了',
            ReadingPlanStatus::InProgress->label()
        );

        $this->assertSame(
            '期限超過',
            ReadingPlanStatus::Expired->label()
        );

        $this->assertSame(
            '完了',
            ReadingPlanStatus::Completed->label()
        );
    }

    public function test_each_status_has_correct_badge_class(): void
    {
        $this->assertSame(
            'bg-blue-100 text-blue-800',
            ReadingPlanStatus::InProgress->badgeClass()
        );

        $this->assertSame(
            'bg-red-100 text-red-800',
            ReadingPlanStatus::Expired->badgeClass()
        );

        $this->assertSame(
            'bg-green-100 text-green-800',
            ReadingPlanStatus::Completed->badgeClass()
        );
    }
}
