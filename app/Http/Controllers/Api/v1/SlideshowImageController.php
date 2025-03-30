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
    public function destroy(Celebration $celebration, Media $media)
    {
        $slideshow = SlideshowImage::where('celebration_id', $celebration->id)->first();

        if (!$slideshow || $media->model_id !== $slideshow->id || $media->collection_name !== 'slideshow_images') {
            return response()->json(['message' => 'Image not found or not allowed.'], 404);
        }

        $media->delete();

        return response()->json(['message' => 'Image deleted successfully.']);
    }
}
