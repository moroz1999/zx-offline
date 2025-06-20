<?php

namespace App\Tasks;

enum TaskTypes
{
    case sync_prods;
    case sync_releases;
    case check_prod_releases;
    case check_release;
}
