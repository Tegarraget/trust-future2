<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Album;

class GalleryController extends Controller
{
    public function index()
    {
        try {
            $albums = Photo::getAllPhotos();
            return view('gallery.index', compact('albums'));
        } catch (\Exception $e) {
            \Log::error('Error in gallery index: ' . $e->getMessage());
            return view('gallery.index', ['albums' => []]);
        }
    }

    private function getAlbumNameFromPhoto($photoName)
    {
        // Logic to extract album name from photo name
        // This is a placeholder; adjust according to your naming convention
        return explode('_', $photoName)[0];
    }
} 