<?php

namespace App\Enums;

enum TransactionType : string {
    case enrolment = 'enrolment';
    case withdrawal = 'withdrawal';
    case payment = 'payment';
}
