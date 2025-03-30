<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use App\Models\SlideshowImage;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SlideshowImageController extends Controller
{
    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function store(Request $request)
    {
        $request->validate([
            'celebration_id' => 'required|exists:celebrations,id',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $celebration = Celebration::findOrFail($request->celebration_id);
        $slideshow = SlideshowImage::firstOrCreate(['celebration_id' => $celebration->id]);

        if ($slideshow->getMedia('slideshow_images')->count() >= 20) {
            return response()->json(['message' => 'Maximum 20 photos allowed.'], 400);
        }

        foreach ($request->file('photos') as $photo) {
            $slideshow->addMedia($photo)->toMediaCollection('slideshow_images');
        }

        return response()->json(['message' => 'Photos uploaded successfully!', 'images' => $slideshow->getMedia('slideshow_images')]);
    }

    public function getPhotos($celebrationId)
    {
        $slideshow = SlideshowImage::where('celebration_id', $celebrationId)->first();

        if (!$slideshow) {
            return response()->json(['images' => []]);
        }

        return response()->json(['images' => $slideshow->getMedia('slideshow_images')]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['image_id' => 'required|integer']);
        $image = Media::findOrFail($request->image_id);
        $image->delete();

        return response()->json(['message' => 'Photo deleted successfully!']);
    }
}
