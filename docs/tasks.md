# Task Queue

## Purpose

The queue lets the project break synchronization into resumable local tasks persisted in SQLite.

## Storage

Table: `tasks`

Important fields:

- `id`
- `type`
- `target_id`
- `status`
- `attempts`
- `created_at`

Statuses used in code:

- `todo`
- `in_progress`
- `done`
- `failed`

## Task types

Defined in `src/Tasks/TaskTypes.php`:

- `sync_prods`
- `sync_releases`
- `build_titles`
- `check_prod_releases`
- `check_failed_files`
- `delete_release_file`
- `delete_release`
- `delete_prod`
- `retry_file`
- `check_release_files`

## Execution model

1. A command or service inserts a task row.
2. `run:daemon` fetches the oldest `todo` task.
3. The task is locked as `in_progress`.
4. The daemon invokes `php cli.php run:task <id>`.
5. `TaskRunner` dispatches by task type.
6. On success, task becomes `done`; on exception, `failed`.

## Notes

- Queue order is FIFO by `created_at`.
- The queue is simple and local. There is no external worker system.
- Because `run:daemon` spawns child CLI processes, task execution is isolated per task.
