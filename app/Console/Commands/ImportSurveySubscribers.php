<?php

namespace App\Console\Commands;

use App\Survey\SubscriberImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportSurveySubscribers extends Command
{
    protected $signature = 'survey:import-subscribers
        {path : Path to the MailJet CSV export}
        {--output=storage/app/survey_urls.csv : Where to write the email,survey_url CSV for re-import}';

    protected $description = 'Import subscribers from a MailJet CSV export and emit a CSV of tokenised survey URLs for the merge tag';

    public function handle(SubscriberImporter $importer): int
    {
        try {
            $result = $importer->import($this->argument('path'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $outputPath = $this->option('output');
        $output = fopen($outputPath, 'w');

        if ($output === false) {
            $this->error("Cannot write to [{$outputPath}].");

            return self::FAILURE;
        }

        foreach ($importer->urlRows() as $row) {
            fputcsv($output, $row);
        }

        fclose($output);

        $this->info("Imported {$result['imported']} subscribers ({$result['skipped']} rows skipped, {$result['unsubscribed']} unsubscribed excluded).");
        $this->info("Survey URL CSV written to [{$outputPath}] — import it back into MailJet as a contact property.");

        return self::SUCCESS;
    }
}
