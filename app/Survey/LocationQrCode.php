<?php

namespace App\Survey;

use App\Models\SurveyLocation;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use RuntimeException;
use ZipArchive;

/**
 * Renders the printable register QR code for a location. Generated from
 * the live location row and the current host, so a printed code can
 * never carry a typo'd slug or the wrong domain.
 */
class LocationQrCode
{
    public function entryUrl(SurveyLocation $location): string
    {
        return route('survey.entry', ['src' => 'qr', 'loc' => $location->slug]);
    }

    public function svg(SurveyLocation $location): string
    {
        $renderer = new ImageRenderer(new RendererStyle(1000), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($this->entryUrl($location));
    }

    /**
     * A zip of every active location's QR code, as binary content.
     */
    public function activeZip(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'survey-qr');

        if ($path === false) {
            throw new RuntimeException('Could not create a temporary file for the zip.');
        }

        try {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);

            SurveyLocation::query()
                ->where('active', true)
                ->orderBy('slug')
                ->each(function (SurveyLocation $location) use ($zip): void {
                    $zip->addFromString("qr-{$location->slug}.svg", $this->svg($location));
                });

            $zip->close();

            $content = file_get_contents($path);

            if ($content === false) {
                throw new RuntimeException('Could not read the generated zip.');
            }

            return $content;
        } finally {
            unlink($path);
        }
    }
}
