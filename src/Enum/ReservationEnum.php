<?php

namespace App\Enum;

enum ReservationEnum: string 
{
    case Canceled = 'Annulé';
    case Pending = "En attente";
    case Confirmed = "Confirmée";
}