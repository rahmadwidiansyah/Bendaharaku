# QA Checklist - Chat Bendaharaku

## Web Chat
- [ ] Login works
- [ ] Chat loads
- [ ] All commands respond
- [ ] Messages display correctly (no broken HTML)
- [ ] No console errors
- [ ] Mobile responsive
- [ ] Dark mode works (if applicable)
- [ ] No message loss after refresh
- [ ] Typing indicator appears during AI response
- [ ] Streaming responses (if enabled) render progressively

## Telegram
- [ ] Bot responds
- [ ] All commands work
- [ ] Messages formatted correctly
- [ ] No timeouts for long responses
- [ ] Error messages are user friendly

## Data & Finance
- [ ] Saldo matches DB manual calculation
- [ ] Transfers persist and balances update
- [ ] No duplicate transactions
- [ ] Timezone handling correct
- [ ] Currency formatting correct per locale

## Localization
- [ ] No hardcoded user-facing strings
- [ ] All trans() keys present for ID and EN
- [ ] Numbers & dates localized

## Performance
- [ ] Response time < 2s (normal operations)
- [ ] AI response < 8s
- [ ] History load < 500ms

## CI / Automation
- [ ] New localization tests pass
- [ ] Command tests automated where possible

Instructions: Use this checklist during manual QA and before declaring Sprint 1 complete.
