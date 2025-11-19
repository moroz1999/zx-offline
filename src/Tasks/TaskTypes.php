<?php

namespace App\Tasks;

enum TaskTypes
{
    case sync_prods;
    case sync_releases;
    case build_titles;
    case check_prod_releases;
    case check_failed_files;
    case delete_release_file;
    case delete_release;
    case delete_prod;
    case retry_file;
    case check_release_files;
}
