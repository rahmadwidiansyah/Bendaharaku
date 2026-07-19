# Known Bugs - Chat Bendaharaku

## BLOCKING (Production Blocker)

- [ ] `/ringkasan` command error
  - Where reported: 
  - Affects: Web / Telegram / Both
  - Impact: Users can't get summary
  - Error message: 
  - Reproducible: 

## HIGH (Incorrect Data)

- [ ] Saldo calculation mismatch
  - Reproducible: ?
  - Affects: Reports, /saldo
- [ ] Transfer between wallets edge-case
- [ ] Negative saldo handling

## MEDIUM (UX / Stability)

- [ ] Web Chat: typing indicator missing in some flows
- [ ] Scroll behavior: history jump on new messages

## LOW (Polish)

- [ ] Localization: some messages still in English
- [ ] Formatting: currency formatting inconsistent

---

Instructions: fill each entry with reproducer steps, root cause, owner, effort estimate, and status.

---

## Test Run Notes

- Ran: `php artisan test --filter TelegramAdapterTest` from project root.
- Result: tests failed to run due to missing DB driver (pgsql) in current environment.
  - Error: "could not find driver (Connection: pgsql, Host: db, Port: 5432, Database: testing)"
  - Impact: Automated unit tests cannot be executed here; need Docker Compose or CI environment with Postgres + PHP extensions.

## Immediate Action Items (environment)

- [ ] Ensure Docker Compose test stack includes Postgres and PHP PDO_PGSQL extension.
- [ ] Provide guidance/scripts to run tests locally: `docker compose up -d && docker compose exec app composer install && docker compose exec app php artisan test --filter TelegramAdapterTest`.
- [ ] Add instruction in ENVIRONMENT_SETUP.md (done: add recommended test command).

