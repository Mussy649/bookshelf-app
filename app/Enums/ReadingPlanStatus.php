<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case InProgress = 'in_progress';
    case Expired = 'expired';
    case Completed = 'completed';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::InProgress => '未完了',
            self::Expired => '期限超過',
            self::Completed => '完了',
        };
    }

    /**
     * Get the Tailwind CSS classes for the status badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Expired => 'bg-red-100 text-red-800',
            self::Completed => 'bg-green-100 text-green-800',
        };
    }
}
