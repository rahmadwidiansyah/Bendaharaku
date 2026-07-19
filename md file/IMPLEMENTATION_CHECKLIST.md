# Chat Bendaharaku - Implementation Checklist

**Status**: Ready for Implementation  
**Phase**: Phase 1 - Localization (Must Complete Before Production)  
**Estimated Duration**: 1 week  
**Team**: 1-2 developers

---

## Phase 1: Localization Fix ⚠️ CRITICAL

### FINDING-015: Map All Translation Keys

**Task 1.1: Audit Translation Keys**
```bash
# Create list of used keys
grep -r "trans(" app/Chat --include="*.php" | \
  grep -o "'[^']*'" | \
  sort | uniq > used_keys.txt

# Create list of defined keys
grep "=>" lang/*/chat.php | \
  grep -o "'[^']*'" | \
  sort | uniq > defined_keys.txt

# Find missing keys
comm -23 used_keys.txt defined_keys.txt > missing_keys.txt
```

**Task 1.2: Add Missing Keys to lang Files**
- Review `missing_keys.txt`
- Add each missing key to `lang/id/chat.php`
- Add English equivalent to `lang/en/chat.php`
- Use consistent key naming: `chat.{feature}.{action}` (e.g., `chat.report.monthly_summary`)

**Task 1.3: Create Test to Prevent Regressions**
```php
// tests/Unit/Chat/TranslationKeysTest.php
public function test_all_trans_keys_defined(): void {
    $usedKeys = $this->extractUsedTransKeys('app/Chat');
    $definedKeys = $this->extractDefinedTransKeys('lang/id');
    
    $missing = array_diff($usedKeys, $definedKeys);
    
    $this->assertEmpty($missing, "Missing keys: " . implode(', ', $missing));
}
```

**Acceptance Criteria**:
- ✅ All `trans()` calls have corresponding keys
- ✅ No MissingTranslationException in logs
- ✅ Test added to CI/CD pipeline

---

### FINDING-014: Remove Hardcoded Strings

**Task 2.1: Find All Hardcoded Strings**
Grep for common patterns in ChatApplicationService:
- Income/Expense type names → lines 700-750
- Report titles → lines 710-750
- Section headers → lines 800-900

**Task 2.2: Replace with Translation Keys**

File: `app/Chat/ChatApplicationService.php`

Find & Replace:
```php
// ❌ Before
'Income' : 'Expense'

// ✅ After
trans("chat.transaction.type_" . strtolower($type), [], $locale)
```

**Task 2.3: Review for Consistency**
- Verify all user-facing strings use `trans()`
- No exception for debug strings (use logger for internal data)
- Consistent parameter passing: `trans($key, $params, $locale)`

**Acceptance Criteria**:
- ✅ 0 hardcoded user-facing strings in Chat code
- ✅ All strings respect locale parameter
- ✅ Code review approved

---

### FINDING-016: Extract Pattern Matching Keywords

**Task 3.1: Create Configuration File**

File: `config/chat.php` (new)
```php
return [
    'keywords' => [
        'wallet' => ['wallet', 'dompet', 'saldo', 'balance', 'tunai'],
        'category' => ['kategori', 'category', 'jenis', 'type', 'tipe'],
        'amount' => ['nominal', 'amount', 'value', 'jumlah', 'nilai'],
        'date' => ['tanggal', 'date', 'tgl'],
    ],
];
```

**Task 3.2: Create Keyword Matcher Service**

File: `app/Chat/Services/ChatKeywordMatcher.php` (new)
```php
class ChatKeywordMatcher {
    public function matchKeyword(string $text, array $keywords): bool {
        $lower = strtolower(trim($text));
        foreach ($keywords as $keyword) {
            if (str_contains($lower, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
}
```

**Task 3.3: Update ChatApplicationService**

Replace all hardcoded pattern matching:
```php
// ❌ Before
if (str_contains($message, 'Wallet') || str_contains($message, 'Dompet')) { ... }

// ✅ After
if ($this->keywordMatcher->matchKeyword($message, config('chat.keywords.wallet'))) { ... }
```

**Acceptance Criteria**:
- ✅ All pattern matching uses keyword config
- ✅ New keywords can be added without code changes
- ✅ Keyword matching handles case/spacing variations

---

### FINDING-013: Translate Command Registry

**Task 4.1: Review ChatCommandRegistry**

File: `app/Chat/ChatCommandRegistry.php`

Identify hardcoded fields:
- Command icons (emojis)
- Command descriptions
- Category names

**Task 4.2: Add Translation Keys**

```php
// ❌ Before
[
    'command' => '/saldo',
    'icon' => '💰',
    'description' => 'Lihat saldo semua dompet',
]

// ✅ After
[
    'command' => '/saldo',
    'icon' => trans('chat.command_icon_saldo'),  // or keep emoji if universal
    'description_key' => 'chat.command.saldo.description',
]
```

**Acceptance Criteria**:
- ✅ All command metadata translatable
- ✅ Icons either universal or translated
- ✅ Frontend can render translated descriptions

---

## Phase 1 Completion Checklist

- [ ] Task 1.1: Audit translation keys (30 min)
- [ ] Task 1.2: Add missing keys to lang files (1 hour)
- [ ] Task 1.3: Create regression test (30 min)
- [ ] Task 2.1: Find hardcoded strings (30 min)
- [ ] Task 2.2: Replace with translation keys (2 hours)
- [ ] Task 2.3: Code review (1 hour)
- [ ] Task 3.1: Create config/chat.php (15 min)
- [ ] Task 3.2: Create ChatKeywordMatcher service (30 min)
- [ ] Task 3.3: Update ChatApplicationService (1 hour)
- [ ] Task 4.1: Review registry (15 min)
- [ ] Task 4.2: Add translation keys (30 min)
- [ ] **QA Testing**: Run full chat flow with both languages (1-2 hours)
- [ ] **PR Submission & Merge**
- [ ] **Update Documentation**

**Total Effort**: ~12 hours (1.5 days for 1 developer)

**Phase 1 Success Criteria**:
- ✅ All trans() calls mapped to keys
- ✅ No hardcoded user-facing strings
- ✅ Pattern matching externalized to config
- ✅ Tests verify no regressions
- ✅ Chat works correctly in both ID and EN languages

---

## Phase 1 → Phase 2 Transition

Once Phase 1 is complete:

1. **Run Full Test Suite** (including new localization tests)
2. **Deploy to Staging** for manual QA
3. **Verify No New Issues** in error logs
4. **Create Release Notes** documenting changes
5. **Begin Phase 2: Type Safety** (parallel or sequential)

---

## Recommended Timeline

| Date | Phase | Status |
|------|-------|--------|
| 2026-07-19 | Audit Complete | ✅ Done |
| 2026-07-22 | Phase 1 Complete | 📅 This week |
| 2026-07-23 | Phase 1 QA | 📅 This week |
| 2026-07-25 | Phase 1 Merged | 📅 This week |
| 2026-07-28 | Phase 2-3 Progress | 📅 Next week |

---

## Questions? Issues?

If any task is blocked:
1. Document the blocker
2. Create a subtask for investigation
3. Escalate if needed
4. Continue with other tasks to maintain momentum

---

**Next Action**: Start Task 1.1 (Audit Translation Keys)

