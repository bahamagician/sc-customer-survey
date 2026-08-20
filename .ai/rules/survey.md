---
paths:
  - 'app/Survey/**'
---

# Survey

## Mutate survey answers through the array cast, never JSON -> update syntax
Production runs MySQL but the Pest suite runs in-memory SQLite, where Laravel's `answers->key` update syntax and array-form whereJsonContains are unsupported. All writes to survey_responses.answers must go through AnswerWriter (read the cast array, mutate in PHP, save). Questions with a `column` config key write to that promoted column instead — never duplicate into answers.
