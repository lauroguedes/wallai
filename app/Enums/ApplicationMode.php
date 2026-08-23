<?php

namespace App\Enums;

enum ApplicationMode: string
{
    case Authenticated = 'authenticated';
    case Session = 'session';
}
