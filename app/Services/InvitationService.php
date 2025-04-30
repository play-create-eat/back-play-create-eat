<?php

namespace App\Services;

use App\Models\Celebration;
use App\Models\PartyInvitation;
use App\Models\PartyInvitationTemplate;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;

class InvitationService
{
    /**
     * @throws Exception
     */
    public function generate(PartyInvitationTemplate $template, Celebration $celebration): string
    {
        $templateContent = file_get_contents($template->image_url);

        $img = Image::read($templateContent);

        $fontPath = public_path('fonts/TT_Fors/TT_Fors_Black.ttf');
        $fontColor = $template->text_color;

        $childName = strtoupper($celebration->child->first_name . "'S");
        $img->text($childName, $img->width() / 2, $img->height() * 0.32, function ($font) use ($fontPath, $fontColor) {
            $font->file($fontPath);
            $font->size(30);
            $font->color($fontColor);
            $font->align('center');
            $font->valign('middle');
        });

        $eventTitle = strtoupper("Birthday");
        $img->text($eventTitle, $img->width() / 2, $img->height() * 0.43, function ($font) use ($fontPath, $fontColor) {
            $font->file($fontPath);
            $font->size(30);
            $font->color($fontColor);
            $font->align('center');
            $font->valign('middle');
        });

        $eventTitle = strtoupper("Party");
        $img->text($eventTitle, $img->width() / 2, $img->height() * 0.52, function ($font) use ($fontPath, $fontColor) {
            $font->file($fontPath);
            $font->size(30);
            $font->color($fontColor);
            $font->align('center');
            $font->valign('middle');
        });

        $fontPath = public_path('fonts/TT_Fors/TT_Fors_Regular.ttf');
        $dateTime = Carbon::parse($celebration->celebration_date)->format('d F Y   h:i A');
        $img->text($dateTime, $img->width() / 2 + 25, $img->height() * 0.693, function ($font) use ($fontPath, $fontColor) {
            $font->file($fontPath);
            $font->size(14);
            $font->color($fontColor);
            $font->align('center');
            $font->valign('middle');
        });

        $location = "Wadi Al Safa 4 (Beside Global Village)";
        $img->text($location, $img->width() / 2 + 12.5, $img->height() * 0.80, function ($font) use ($fontPath, $fontColor) {
            $font->file($fontPath);
            $font->size(14);
            $font->color($fontColor);
            $font->align('center');
            $font->valign('middle');
        });

        $disclaimer = 'Please be there 15 minutes before to sign the waiver';

        $img->text($disclaimer, $img->width() / 2, $img->height() - 10, function (FontFactory $font) use ($fontPath, $fontColor) {
            $font->file($fontPath);
            $font->size(12);
            $font->color($fontColor);
            $font->align('center');
            $font->valign('bottom');
            $font->wrap(200);
        });

        $filename = 'invitations/generated/' . Str::uuid() . '.png';

        $encoder = new PngEncoder();
        Storage::disk('s3')->put($filename, $img->encode($encoder));

        PartyInvitation::create([
            'template_id'    => $template->id,
            'celebration_id' => $celebration->id,
        ]);

        return Storage::cloud()->url($filename);
    }

    public function templates(): Collection
    {
        return PartyInvitationTemplate::all();
    }
}
