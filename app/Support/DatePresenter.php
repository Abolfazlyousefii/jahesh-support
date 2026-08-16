<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

final class DatePresenter
{
    public function date(?DateTimeInterface $date): string
    {
        return $this->format($date, 'yyyy/MM/dd');
    }

    public function dateTime(?DateTimeInterface $date): string
    {
        return $this->format($date, 'yyyy/MM/dd، HH:mm');
    }

    public function today(): string
    {
        return $this->date(now());
    }

    private function format(?DateTimeInterface $date, string $pattern): string
    {
        if ($date === null) {
            return '—';
        }

        $formatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            config('app.timezone'),
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        return $formatter->format($date) ?: '—';
    }
}
