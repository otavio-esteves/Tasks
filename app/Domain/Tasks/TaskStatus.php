<?php

namespace App\Domain\Tasks;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluído',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::Pending => in_array($target, [self::InProgress, self::Completed], true),
            self::InProgress => in_array($target, [self::Pending, self::Completed], true),
            self::Completed => in_array($target, [self::Pending, self::InProgress], true),
        };
    }
}
