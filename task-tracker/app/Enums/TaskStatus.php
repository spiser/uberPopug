<?php

namespace App\Enums;

enum TaskStatus : string {
    case processing = 'В процессе';
    case done = 'Выполнена';
}
