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
}
