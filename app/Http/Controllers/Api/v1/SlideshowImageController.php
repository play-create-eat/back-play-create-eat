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
    public function getPhotos(Celebration $celebration)
    {
        $slideshow = SlideshowImage::where('celebration_id', $celebration->id)->first();

        if (!$slideshow) {
            return response()->json(['images' => []]);
        }

        return response()->json(['images' => $slideshow->getMedia('slideshow_images')]);
    }
}
