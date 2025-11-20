# CHANGELOG 4.x → 5.x

### Command API changes

- Summary: Harmonization of commands in the `Command/` directory to comply with the Symfony Console command contract:
  - the `execute()` method now returns an integer (`: int`),
  - direct `exit` calls have been removed and replaced by status returns (`self::FAILURE`) for cancellations/errors,
  - successful executions now explicitly return `self::SUCCESS`.

### Tree structure changes

- All source code is now located under the `src/` directory, following modern PHP project structure conventions.
- The named service annotation_reader not declared in Doctrine anymore, it is now declared in the bundle configuration.
- SessionInterface cannot be used directly anymore, use RequestStack to get the session from the current request.