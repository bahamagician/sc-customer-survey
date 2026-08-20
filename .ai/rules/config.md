---
paths:
  - config/survey.php
---

# Config

## Survey question config: pooled wording is load-bearing, order lives in sequences
Questions marked pooled (overall_score + the five Likert columns) must stay identically worded across the qr and email channels — a wording drift silently corrupts cross-channel trends, and QuestionSetTest asserts equality. Channel order lives in the `sequences` arrays (the two channels ask shared questions in different positions); a question appears on a channel iff its key is in that channel's sequence. Question keys are stable identifiers used in URLs, answers JSON, and analysis — never rename one after responses exist.
