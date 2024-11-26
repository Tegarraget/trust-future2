<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'album_id'
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public static function getAllPhotos()
    {
        try {
            $albums = [];

            // 1. Ambil foto dari album
            $albumFolders = Storage::disk('public')->directories('photos/album_');
            foreach ($albumFolders as $albumFolder) {
                $albumId = str_replace('photos/album_', '', $albumFolder);
                $albumInfoPath = "albums/album_{$albumId}.json";

                if (Storage::disk('public')->exists($albumInfoPath)) {
                    $albumInfo = json_decode(Storage::disk('public')->get($albumInfoPath), true);
                    $albumName = $albumInfo['name'] ?? "Album {$albumId}";
                    $albums[$albumName] = self::getPhotosFromDirectory($albumFolder);
                }
            }

            // 2. Ambil foto dari jurusan
            $jurusanPaths = [
                'Jurusan PPLG' => 'photos/jurusan/pplg',
                'Jurusan TJKT' => 'photos/jurusan/tjkt',
                'Jurusan TFLM' => 'photos/jurusan/tflm',
                'Jurusan TKR' => 'photos/jurusan/tkr'
            ];

            foreach ($jurusanPaths as $name => $path) {
                if (Storage::disk('public')->exists($path)) {
                    $photos = self::getPhotosFromDirectory($path);
                    if (!empty($photos)) {
                        $albums[$name] = $photos;
                    }
                }
            }

            // 3. Ambil foto dari agenda
            $agendaPath = 'photos/agenda';
            if (Storage::disk('public')->exists($agendaPath)) {
                $photos = self::getPhotosFromDirectory($agendaPath);
                if (!empty($photos)) {
                    $albums['Agenda Sekolah'] = $photos;
                }
            }

            // 4. Ambil foto dari info
            $infoPath = 'photos/info';
            if (Storage::disk('public')->exists($infoPath)) {
                $photos = self::getPhotosFromDirectory($infoPath);
                if (!empty($photos)) {
                    $albums['Informasi'] = $photos;
                }
            }

            return $albums;
        } catch (\Exception $e) {
            \Log::error('Error getting all photos: ' . $e->getMessage());
            return [];
        }
    }

    private static function getPhotosFromDirectory($directory)
    {
        $photos = [];
        $files = Storage::disk('public')->files($directory);

        foreach ($files as $file) {
            // Filter hanya file gambar
            if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                continue;
            }

            $photoId = pathinfo($file, PATHINFO_FILENAME);
            $photoInfoPath = 'photos_info/' . $photoId . '.json';
            
            $photoName = $photoId;
            $photoDescription = '';

            // Ambil info foto jika ada
            if (Storage::disk('public')->exists($photoInfoPath)) {
                $photoInfo = json_decode(Storage::disk('public')->get($photoInfoPath), true);
                $photoName = $photoInfo['name'] ?? $photoId;
                $photoDescription = $photoInfo['description'] ?? '';
            }

            $photos[] = (object)[
                'id' => $photoId,
                'name' => $photoName,
                'description' => $photoDescription,
                'path' => Storage::url($file)
            ];
        }

        return $photos;
    }
} 