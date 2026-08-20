<?php

namespace App\Survey;

use App\Models\SurveySubscriber;
use Generator;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Imports subscribers from a MailJet CSV export and produces the
 * email,survey_url rows that go back to MailJet as a contact property.
 * Shared by the admin import action and the artisan command.
 */
class SubscriberImporter
{
    /**
     * Import every row with a valid email, honouring MailJet's
     * unsubscribed flag when the export includes one. Existing
     * subscribers keep their token, so re-importing is always safe.
     *
     * @return array{imported: int, skipped: int, unsubscribed: int}
     *
     * @throws InvalidArgumentException when the file is unreadable or has no email column
     */
    public function import(string $path): array
    {
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException("Cannot read [{$path}].");
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new InvalidArgumentException('The CSV is empty.');
            }

            // Exports commonly open with a UTF-8 byte-order mark, which
            // would otherwise glue itself to the first header name.
            if (isset($header[0])) {
                $header[0] = ltrim((string) $header[0], "\u{FEFF}");
            }

            $columns = array_map(fn (?string $column): string => Str::lower(trim((string) $column)), $header);
            $emailIndex = array_search('email', $columns, true);
            $nameIndex = array_search('name', $columns, true);
            $unsubscribedIndex = array_search('unsubscribed', $columns, true);

            if ($emailIndex === false) {
                throw new InvalidArgumentException('No "email" column found in the CSV header.');
            }

            $imported = 0;
            $skipped = 0;
            $unsubscribed = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($unsubscribedIndex !== false
                    && in_array(Str::lower(trim((string) ($row[$unsubscribedIndex] ?? ''))), ['t', 'true', '1', 'yes'], true)) {
                    $unsubscribed++;

                    continue;
                }

                $email = AnswerWriter::normaliseEmail($row[$emailIndex] ?? null);

                if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    continue;
                }

                $name = $nameIndex !== false ? trim((string) ($row[$nameIndex] ?? '')) : '';

                SurveySubscriber::query()->firstOrCreate(
                    ['email' => $email],
                    ['token' => Str::random(32), 'name' => $name !== '' ? $name : null],
                );

                $imported++;
            }

            return ['imported' => $imported, 'skipped' => $skipped, 'unsubscribed' => $unsubscribed];
        } finally {
            fclose($handle);
        }
    }

    /**
     * Every subscriber's email and tokenised survey URL, header row
     * first — ready to write out as CSV.
     *
     * @return Generator<int, array{string, string}>
     */
    public function urlRows(): Generator
    {
        yield ['email', 'survey_url'];

        foreach (SurveySubscriber::query()->orderBy('email')->lazy() as $subscriber) {
            yield [
                $subscriber->email,
                route('survey.entry', ['t' => $subscriber->token]),
            ];
        }
    }
}
