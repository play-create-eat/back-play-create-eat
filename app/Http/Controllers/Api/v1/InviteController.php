<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\InvitationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Imagick;
use ImagickException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Throwable;

class InviteController extends Controller
{
    public function index()
    {
        $invitations = InvitationTemplate::all();

        return  response()->json($invitations);
    }

    public function store()
    {
        return view('invitations.background.index');
    }

    /**
     * @throws ImagickException
     * @throws Throwable
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function generate(Celebration $celebration, InvitationTemplate $template)
    {
        $invitationData = (object)[
            'background_color' => $template->background_color ?? '#ffffff',
            'text_color'       => $template->text_color ?? '#000000',
            'logo_color'       => $template->logo_color ?? '#000000',
            'decoration_type'  => $template->decoration_type,
            'child_name'       => $celebration->child->first_name,
            'date'             => Carbon::parse($celebration->celebration_date)->format('d F Y'),
            'hour'             => Carbon::parse($celebration->celebration_date)->format('H:i'),
            'place'            => 'Wadi Al Safa 4 (Beside Global Village)',
        ];

        $html = view('invitations.background.index', [
            'invitation' => $invitationData,
        ])->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper([0, 0, 310, 428]);
        $pdfPath = storage_path('app/public/temp_' . $template->id . '.pdf');
        file_put_contents($pdfPath, $pdf->output());

        $pngPath = storage_path('app/public/temp_' . $template->id . '.png');
        $imagick = new Imagick();
        $imagick->setResolution(300, 300);
        $imagick->readImage($pdfPath);
        $imagick->setImageFormat('png');
        $imagick->writeImage($pngPath);
        $imagick->clear();

        $template->clearMediaCollection('invitation');

        $template
            ->addMedia($pngPath)
            ->preservingOriginal()
            ->toMediaCollection('invitation');

        $invitationUrl = $template->getFirstMediaUrl('invitation');

        unlink($pdfPath);
        unlink($pngPath);

        return response()->json(['url' => $invitationUrl]);
    }
}
