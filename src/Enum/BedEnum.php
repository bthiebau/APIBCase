<?php

namespace App\Enum;

enum BedEnum: string
{
    case OneSimpleBed = '1 lit simple';
    case TwoSimpleBed = '2 lits simples';
    case OneDoubleBed = '1 lit double';
    case TwoDoubleBed = '2 lits doubles';
}