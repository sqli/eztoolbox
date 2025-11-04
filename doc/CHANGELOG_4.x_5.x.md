# CHANGELOG 4.x → 5.x

## 2025-11-04

### Command API changes

- Summary: Harmonization of commands in the `Command/` directory to comply with the Symfony Console command contract:
  - the `execute()` method now returns an integer (`: int`),
  - direct `exit` calls have been removed and replaced by status returns (`self::FAILURE`) for cancellations/errors,
  - successful executions now explicitly return `self::SUCCESS`.
