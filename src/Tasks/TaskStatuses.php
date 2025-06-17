<?php

namespace App\Tasks;

enum TaskStatuses
{
    case todo;
    case done;
    case in_progress;
    case failed;
}
