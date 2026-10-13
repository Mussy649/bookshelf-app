<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ReadingPlanScheduleTest extends TestCase
{
    public function test_daily_reading_plan_command_is_scheduled_at_20_00(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $event = collect($schedule->events())
            ->first(
                fn ($event): bool => str_contains(
                    $event->command ?? '',
                    'reading-plans:process-daily'
                )
            );

        $this->assertNotNull($event);
        $this->assertSame('0 20 * * *', $event->expression);
    }
}
