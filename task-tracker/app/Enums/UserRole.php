<?php

namespace App\Enums;

enum UserRole : string {
    case admin = 'Админ';
    case worker = 'Рабочий';
    case manager = 'Менеджер';
    case accountant = 'Бухгалтер';
}
