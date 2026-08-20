# Customer Survey — Build Plan

Laravel + Filament + Inertia/Vue app for collecting customer satisfaction responses across
The Sports Center, HeatWave, and The Outlet. Two intake channels feed one dataset.

---

## 1. Core design decisions

**Questions live in config, not the database.** A PHP config file defines every question,
its type, options, and which channel shows it. No question-builder admin UI — that's a CMS
nobody asked for. Changing a question is a git commit.

**Answers are stored as JSON, with the six pooled metrics promoted to real columns.** At a
few thousand rows a year, JSON querying is fine for the long tail. The six items that get
trended and compared across stores are real integer columns so aggregation and correlation
queries stay trivial.

**Responses are written on every step advance, not on final submit.** A respondent who
answers twelve questions and abandons at the contact fields has still given twelve
questions of data. This is the single most important behaviour in the build.

**Channel is stamped on every row.** Register scans and email respondents answer in
different mental states. Pooling them without a `source` column makes the trend line move
when the channel mix changes rather than when customer experience changes.

---

## 2. Schema

### `survey_locations`
| column | type | notes |
|---|---|---|
| id | id | |
| slug | string, unique | `harbour-bay-sc`, `sandyport-hw` — used in QR URLs |
| brand | string | `sports_center`, `heatwave`, `outlet` |
| name | string | display name |
| active | boolean | |

Seed the six: Sports Center Harbour Bay / Marathon Mall / Sandyport, HeatWave Harbour Bay /
Sandyport, The Outlet Harbour Bay.

### `survey_subscribers`
| column | type | notes |
|---|---|---|
| id | id | |
| email | string, unique | normalised lowercase + trimmed |
| token | string(32), unique, indexed | for the MailJet merge-tag URL |
| name | string, nullable | populated if a respondent gives one |

Seeded from a MailJet CSV export. No live API sync in either direction.

### `survey_responses`
| column | type | notes |
|---|---|---|
| id | uuid, primary | used in the public URL — no enumeration |
| source | enum: `qr`, `email` | never nullable |
| survey_location_id | fk, nullable | set from URL on QR; from Q1 on email |
| survey_subscriber_id | fk, nullable | email channel only |
| overall_score | tinyint, nullable | 0–10 |
| found_what_looking_for | tinyint, nullable | 1–5 |
| staff_knowledgeable | tinyint, nullable | 1–5 |
| staff_helpful | tinyint, nullable | 1–5 |
| store_clean | tinyint, nullable | 1–5 |
| prices_fair | tinyint, nullable | 1–5 |
| answers | json | everything else, keyed by question key |
| name | string, nullable | |
| email | string, nullable | normalised |
| follow_up_status | enum, nullable | `pending`, `contacted`, `resolved`, `no_action` |
| last_step | unsigned tinyint | resume point |
| completed_at | timestamp, nullable | null = partial |
| ip_hash | string, nullable | hashed, for rate limiting only |
| created_at / updated_at | | |

Index `source`, `survey_location_id`, `completed_at`, `created_at`.

---

## 3. Question configuration

`config/survey.php` — shape:

```php
return [
    'questions' => [
        [
            'key'      => 'departments',
            'channels' => ['qr', 'email'],
            'type'     => 'multi_select',
            'label'    => [
                'qr'    => 'Which department(s) did you shop in today?',
                'email' => 'Which department(s) do you usually shop in?',
            ],
            'options'  => [
                'mens_apparel'   => "Men's Apparel",
                'womens_apparel' => "Women's Apparel",
                'kids_apparel'   => 'Kids Apparel',
                'footwear'       => 'Footwear',
                'fitness_equip'  => 'Fitness & Sporting Equipment',
            ],
        ],
        [
            'key'      => 'overall_score',
            'channels' => ['qr', 'email'],
            'type'     => 'scale_0_10',
            'column'   => 'overall_score',   // promoted to a real column
            'label'    => 'How would you rate your overall shopping experience with us?',
        ],
        [
            'key'      => 'staff_helpful',
            'channels' => ['qr', 'email'],
            'type'     => 'likert',
            'column'   => 'staff_helpful',
            'label'    => 'Our staff are helpful.',
        ],
        [
            'key'       => 'purchase_barrier',
            'channels'  => ['qr'],
            'type'      => 'multi_select_other',
            'label'     => 'What stopped you?',
            'condition' => ['purchase_intent' => ['less_than', 'nothing']],
            'options'   => [ /* ... */ ],
        ],
        // ...
    ],
];
```

A question with a `column` key writes to that column instead of into `answers`.
A question with a `condition` key is skipped unless the condition matches answers already
given. `label` accepts either a string (same both channels) or a per-channel array.

The two question sets and their exact wording are in section 9 below — transcribe them into
this config.

---

## 4. Routes and flow

```
GET   /survey                        entry point — resolves channel, creates response, redirects
GET   /survey/{uuid}/{step}          renders one question
PATCH /survey/{uuid}                 saves the current step's answer, advances last_step
POST  /survey/{uuid}/complete        sets completed_at
GET   /survey/thanks                 confirmation + prize draw note
```

**Entry resolution:**

- `?src=qr&loc=harbour-bay-sc` → look up location by slug, create response with
  `source=qr`, location set, subscriber null.
- `?t=<token>` → look up subscriber, create response with `source=email`, subscriber set,
  location null (asked as Q1). Prefill `email` from the subscriber record.
- Neither → 404. No unattributed responses.

Store the response UUID in the session so a mid-survey refresh resumes rather than starting
a new row. Redirect to `last_step` on re-entry.

**Question sequencing** is computed from config filtered by channel, with conditional
questions evaluated against answers so far. Step numbers must be resolved dynamically — a
skipped conditional shifts everything after it.

---

## 5. Frontend (Inertia + Vue)

One question per screen. Mobile-first — assume 90%+ phone traffic.

- Progress bar at top, driven by resolved question count for the channel.
- Likert: five tappable buttons in a row, not a select. Labels below (Strongly disagree →
  Strongly agree). Minimum 44px tap targets.
- 0–10 scale: buttons in a wrapping grid, anchor labels at each end.
- Multi-select: tappable chips with a selected state.
- Advance on selection for single-choice questions (no separate Next tap), explicit Next for
  multi-select and text.
- Back button that doesn't lose the saved answer.
- Text areas: clearly marked optional, with a skip affordance.
- Final screen: name and email, both optional, framed as *"Leave your name and email if
  you'd like someone to follow up with you — and to enter this month's gift card draw."*
  On the email channel the address is prefilled and editable.

Brand the QR flow by location brand — Sports Center, HeatWave, and Outlet should each pull
their own logo and accent colour from the location record.

---

## 6. Filament admin

**Responses resource**
- Table: created_at, source, location, overall_score, completed badge, name.
- Filters: source, location, date range, completed vs partial, score range.
- Detail view rendering all answers with their question labels, not raw JSON keys.
- CSV export honouring active filters.

**Follow-up view** — a filtered page for `overall_score <= 6 AND email IS NOT NULL`, with
an editable `follow_up_status`. This is the service-recovery queue. It is the highest-value
screen in the admin and should be built in phase 1.

**Dashboard widgets**
- Average overall score, current month vs prior, split by channel.
- Response count and completion rate by channel.
- Average overall score by location — a bar chart, this is the comparison that drives action.
- The five Likert averages.
- Barrier reason counts, descending.
- "Where do you look first" distribution, segmented by channel and never pooled.

**Open-ended reader** — a simple paginated list of the two text questions with location,
score, and date alongside each. Reading these is most of the value; don't bury them in a
detail modal.

---

## 7. Anti-abuse

- Honeypot field on the entry request.
- Rate limit response creation by hashed IP — something like 5/hour is generous for a
  register with a shared connection but stops casual spam.
- Prize draw integrity handled at draw time by deduplicating on normalised email, not by
  blocking submissions.
- No CAPTCHA. It will cost more completions than it saves.

---

## 8. Setup tasks outside the app

- Export the MailJet list to CSV, seed `survey_subscribers`, generate a CSV of
  `email,survey_url`, import that back to MailJet as a contact property, reference it as a
  merge tag in the eblast.
- Generate six QR codes pointing at `/survey?src=qr&loc={slug}`. One per location, printed
  for the counter. No in-app feature needed.
- Brief cashiers to prompt the scan and mention the draw. Scan rate without a verbal prompt
  is close to zero.

---

## 9. Question sets

Items marked ◆ are pooled across both channels and must stay identically worded.
Items marked ◇ appear in both but are analysed per-channel only.

### QR — `?src=qr&loc={slug}`

1. Which department(s) did you shop in today? *(multi-select)*
2. ◆ How would you rate your overall shopping experience with us? *(0–10)*
3. ◆ I found what I was looking for. *(Likert)*
4. ◆ Our staff are knowledgeable about the products. *(Likert)*
5. ◆ Our staff are helpful. *(Likert)*
6. ◆ The store is clean and easy to navigate. *(Likert)*
7. ◆ Our prices are fair for the quality. *(Likert)*
8. Were you greeted by a staff member? *(Yes / No / Don't remember)*
9. Were you thanked when you checked out? *(Yes / No / Don't remember)*
10. Today, I purchased… *(More than I intended / As much as I intended / Less than I intended / Nothing at all)*
11. *(conditional on 10 = less or nothing)* What stopped you? *(multi-select: Didn't have my size / Didn't have the style I wanted / Price / Couldn't find anyone to help / Checkout line too long / Just browsing / Other → text)*
12. ◇ When you need something we carry, where do you usually look first? *(Sports Center or HeatWave / Another store in Nassau / Order online from the US / Wait until I'm travelling abroad)*
13. How often do you shop with us? *(Weekly / A few times a month / Every few months / A couple times a year / First visit)*
14. Is there a product you'd like us to carry that we don't? *(optional text)*
15. What could we do to improve your experience? *(optional text)*
16. Name and email *(both optional)*

### Email — `?t={token}`

1. Which location do you shop at most often? *(six locations + "I shop at more than one")*
2. Which department(s) do you usually shop in? *(multi-select)*
3. When did you last shop with us? *(Within the past month / 1–3 months / 3–6 months / 6–12 months / Over a year ago)*
4. How often do you shop with us? *(same five options as QR 13)*
5. ◆ How would you rate your overall shopping experience with us? *(0–10)*
6. ◆ I found what I was looking for. *(Likert)*
7. ◆ Our staff are knowledgeable about the products. *(Likert)*
8. ◆ Our staff are helpful. *(Likert)*
9. ◆ The store is clean and easy to navigate. *(Likert)*
10. ◆ Our prices are fair for the quality. *(Likert)*
11. Staff usually greet me when I come in. *(Likert)*
12. ◇ When you need something we carry, where do you usually look first? *(same four options)*
13. When you've left one of our stores without buying, what usually stopped you? *(same multi-select as QR 11, always shown)*
14. Is there a product you'd like us to carry that we don't? *(optional text)*
15. What could we do to improve your experience? *(optional text)*
16. Name, and confirm your email *(both optional, email prefilled)*

---

## 10. Tests (Pest)

- Entry with a valid `loc` slug creates a response with `source=qr` and the location set.
- Entry with a valid token creates a response with `source=email` and the email prefilled.
- Entry with neither returns 404.
- Each PATCH persists immediately — assert the row after step 3 of 16.
- Abandoning mid-survey leaves `completed_at` null but retains all answers given.
- Refreshing mid-survey resumes at `last_step` rather than creating a second row.
- Promoted questions write to their column, not into `answers`.
- The conditional barrier question is skipped when purchase intent is "more" or "as much".
- Email-only and QR-only questions never appear on the wrong channel.
- Honeypot submission is rejected.
- Email normalisation collapses `  Chris@Example.COM ` to `chris@example.com`.

---

## 11. Build order

**Phase 1 — ship this before the eblast goes out**
Migrations and seeders, config, entry resolution, the step flow with partial saving, the
public form UI, the Filament responses table, and the follow-up queue.

**Phase 2 — after the first hundred responses**
Dashboard widgets, the open-ended reader, CSV export.

**Phase 3 — after a few hundred**
Driver analysis: Pearson correlation of each Likert column against `overall_score`,
computed in PHP over the completed rows and rendered as a ranked list. This tells you which
factor actually predicts a good experience, which is usually not what anyone assumes.

---

## 12. Reading the results

The absolute score is close to meaningless — respondents self-select toward the engaged and
the annoyed, and named feedback skews gentle. What is meaningful:

- Month-over-month movement within a channel.
- Store-versus-store gaps at the same point in time.
- Barrier counts, which map directly to buying and staffing decisions.
- The open-ended text, read rather than aggregated.

Never compare a QR average to an email average and treat the gap as a finding.