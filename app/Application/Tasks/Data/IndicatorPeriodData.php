<?php

namespace App\Application\Tasks\Data;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class IndicatorPeriodData
{
    public const MAX_POINTS = 366;

    private function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public string $grouping,
    ) {}

    public static function fromInput(?string $startDate, ?string $endDate, ?string $grouping): self
    {
        $start = self::parseDate($startDate, CarbonImmutable::now()->startOfYear());
        $end = self::parseDate($endDate, CarbonImmutable::now()->endOfYear());
        $grouping = $grouping ?? 'monthly';

        if (! in_array($grouping, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
            throw new InvalidArgumentException('O agrupamento dos indicadores é inválido.');
        }

        if ($end->isBefore($start)) {
            throw new InvalidArgumentException('A data final dos indicadores deve ser igual ou posterior à data inicial.');
        }

        $cursor = match ($grouping) {
            'daily' => $start->startOfDay(),
            'weekly' => $start->startOfWeek(),
            'monthly' => $start->startOfMonth(),
            'yearly' => $start->startOfYear(),
        };

        for ($points = 0; $cursor->lessThanOrEqualTo($end); $points++) {
            if ($points >= self::MAX_POINTS) {
                throw new InvalidArgumentException('O período dos indicadores deve ter no máximo 366 pontos no agrupamento selecionado.');
            }

            $cursor = match ($grouping) {
                'daily' => $cursor->addDay(),
                'weekly' => $cursor->addWeek(),
                'monthly' => $cursor->addMonth(),
                'yearly' => $cursor->addYear(),
            };
        }

        return new self($start->startOfDay(), $end->endOfDay(), $grouping);
    }

    private static function parseDate(?string $value, CarbonImmutable $default): CarbonImmutable
    {
        if ($value === null || $value === '') {
            return $default;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Informe datas válidas para os indicadores.');
        }

        if ($date === null || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Informe datas válidas para os indicadores.');
        }

        return $date;
    }
}
