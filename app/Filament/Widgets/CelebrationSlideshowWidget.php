<?php

namespace App\Filament\Widgets;

use App\Models\Celebration;
use Exception;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use ZipArchive;

class CelebrationSlideshowWidget extends Widget
{
    protected static string $view = 'filament.widgets.celebration-slideshow-widget';
    protected static bool $isLazy = false;
    public array $slideshowData = [
        'images'        => [],
        'celebrationId' => 'default',
        'hasImages'     => false
    ];
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }

    public function downloadSlideshow()
    {
        $celebrationId = $this->slideshowData['celebrationId'] ?? null;

        if (!$celebrationId || $celebrationId === 'default') {
            $this->dispatch('notify', [
                'type'  => 'warning',
                'title' => 'Download Failed',
                'body'  => 'Invalid celebration ID.'
            ]);
            return;
        }

        $celebration = Celebration::find($celebrationId);

        if (!$celebration) {
            $this->dispatch('notify', [
                'type'  => 'warning',
                'title' => 'Download Failed',
                'body'  => 'Celebration not found.'
            ]);
            return;
        }

        try {
            $slideshow = $celebration->slideshow;

            if (!$slideshow) {
                Notification::make()
                    ->title('No Slideshow Found')
                    ->body('This celebration has no slideshow.')
                    ->warning()
                    ->send();
                return;
            }

            $images = $slideshow->getMedia('slideshow_images')->map(function ($media) {
                return [
                    'id'  => $media->id,
                    'url' => $media->getUrl()
                ];
            });

            if ($images->isEmpty()) {
                Notification::make()
                    ->title('No Images Found')
                    ->body('This celebration has no slideshow images to download.')
                    ->warning()
                    ->send();
                return;
            }

            $zipFileName = "celebration_{$celebration->id}_slideshow_" . now()->format('Y-m-d_H-i-s') . ".zip";
            $zipPath = storage_path("app/temp/{$zipFileName}");

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Cannot create ZIP file');
            }

            $imageCount = 0;
            foreach ($images as $index => $image) {
                try {
                    $imageContent = file_get_contents($image['url']);
                    if ($imageContent !== false) {
                        $extension = pathinfo(parse_url($image['url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                        $imageName = "slideshow_image_" . ($index + 1) . "." . $extension;
                        $zip->addFromString($imageName, $imageContent);
                        $imageCount++;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            $zip->close();

            if ($imageCount === 0) {
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }

                Notification::make()
                    ->title('Download Failed')
                    ->body('No images could be downloaded.')
                    ->danger()
                    ->send();
                return;
            }

            Notification::make()
                ->title('Download Started')
                ->body("Downloading {$imageCount} slideshow images...")
                ->success()
                ->send();

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            Notification::make()
                ->title('Download Failed')
                ->body('An error occurred while preparing the download: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        return [
            'slideshowData' => $this->slideshowData,
        ];
    }
}
