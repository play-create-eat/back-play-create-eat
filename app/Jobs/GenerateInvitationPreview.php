<?php

namespace App\Jobs;

use App\Models\InvitationTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Imagick;
use ImagickException;
use Spatie\MediaLibrary\InteractsWithMedia;
use Throwable;

class GenerateInvitationPreview implements ShouldQueue
{
    use Queueable;
    use InteractsWithMedia;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public InvitationTemplate $template)
    {
    }

    /**
     * Execute the job.
     * @throws ImagickException
     * @throws Throwable
     */
    public function handle(): void
    {
        $invitationData = (object)[
            'background_color' => $this->template->background_color,
            'text_color'       => $this->template->text_color,
            'logo_color'       => $this->template->logo_color,
            'decoration_type'  => $this->template->decoration_type,
            'child_name'       => 'TATA',
            'date'             => '10 March 2025',
            'hour'             => '14:30',
            'place'            => 'Wadi Al Safa 4 (Beside Global Village)',
        ];

        $html = view('invitations.background.index', [
            'invitation' => $invitationData,
        ])->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper([0, 0, 310, 428]);
        $pdfPath = storage_path('app/public/temp_preview_' . $this->template->id . '.pdf');
        file_put_contents($pdfPath, $pdf->output());

        $pngPath = storage_path('app/public/temp_preview_' . $this->template->id . '.png');
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);
        $imagick->setImageFormat('png');
        $imagick->writeImage($pngPath);
        $imagick->clear();

        $this->template->clearMediaCollection('invitation_preview');

        $this->template
            ->addMedia($pngPath)
            ->preservingOriginal()
            ->toMediaCollection('invitation_preview');

        unlink($pdfPath);
        unlink($pngPath);
    }
}
