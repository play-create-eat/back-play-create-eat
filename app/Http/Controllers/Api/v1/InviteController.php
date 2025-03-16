<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\Invite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class InviteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'celebration_id' => 'required|exists:celebrations,id',
        ]);

        $celebration = Celebration::with('theme')->findOrFail($request->celebration_id);
        $theme = $celebration->theme->name ?? 'Default Theme';

        $imagePath = $this->generateInviteImage($celebration, $theme);
        $pdfPath = $this->generateInvitePDF($celebration, $theme);

        $invite = Invite::create([
            'celebration_id' => $celebration->id,
            'child_name' => $celebration->child->name,
            'theme' => $theme,
            'invite_image' => $imagePath,
            'invite_pdf' => $pdfPath,
        ]);

        return response()->json($invite, 201);
    }

    public function show()
    {

    }

    private function generateInviteImage($celebration, $theme)
    {
        $childName = $celebration->child->name;

        $backgroundPath = storage_path("app/public/theme_templates/{$theme}.png");
        $img = Image::read(file_get_contents($backgroundPath));

        $img->text($childName, 350, 500, function($font) {
            $font->file(storage_path('fonts/arial.ttf'));
            $font->size(50);
            $font->color('#FFFFFF');
            $font->align('center');
        });

        $imagePath = "invitations/{$celebration->id}_{$theme}.png";
        Storage::disk('public')->put($imagePath, $img->encode());

        return $imagePath;
    }

    private function generateInvitePDF($celebration, $theme)
    {
        $childName = $celebration->child->name;
        $data = ['child_name' => $childName, 'theme' => $theme];

        $pdf = Pdf::loadView('pdf.invite', $data);
        $pdfPath = "invitations/{$celebration->id}_{$theme}.pdf";

        Storage::disk('public')->put($pdfPath, $pdf->output());

        return $pdfPath;
    }
}
