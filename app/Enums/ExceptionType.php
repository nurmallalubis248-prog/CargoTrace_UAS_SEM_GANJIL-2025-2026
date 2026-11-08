<?php

namespace App\Enums;

enum ExceptionType: string
{
    case CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    case WEATHER_DELAY = 'WEATHER_DELAY';
    case PORT_CONGESTION = 'PORT_CONGESTION';
    case DAMAGED = 'DAMAGED';
    case MISROUTED = 'MISROUTED';
    case DOCUMENTATION_ISSUE = 'DOCUMENTATION_ISSUE';

    /** English reason shown in the web UI (the browser dictionary translates it). */
    public function label(): string
    {
        return match ($this) {
            self::CUSTOMS_HOLD => 'Held at customs',
            self::WEATHER_DELAY => 'Bad weather',
            self::PORT_CONGESTION => 'Port congestion',
            self::DAMAGED => 'Cargo damaged',
            self::MISROUTED => 'Misrouted',
            self::DOCUMENTATION_ISSUE => 'Incomplete documents',
        };
    }

    /** Reason in the server locale (used in the PDF report). */
    public function localized(): string
    {
        return __('ui.exception_types.'.$this->value);
    }
}
