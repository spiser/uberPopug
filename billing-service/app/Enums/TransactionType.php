<?php

namespace App\Enums;

enum TransactionType : string {
    case task = 'task';
    case payment = 'payment';
}
