<?php

namespace App\Enum;

enum Payed: string {
    case PAID = 'paid';
    case UNPAID = 'unpaid';
}