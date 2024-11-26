<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Photo;

class PhotoController extends Controller
{
    public function index()
    {
        try {
            // Ambil semua foto dari storage/app/public/photos
            $photos = collect(Storage::disk('public')->files('photos'))
                ->filter(function ($file) {
                    return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                })
                ->map(function ($file) {
                    return [
                        'name' => basename($file),
                        'path' => Storage::url($file)
                    ];
                });

            // Ambil foto dari semua subfolder di photos
            $subfolders = Storage::disk('public')->directories('photos');
            foreach ($subfolders as $folder) {
                $subfolderPhotos = collect(Storage::disk('public')->files($folder))
                    ->filter(function ($file) {
                        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
                    })
                    ->map(function ($file) {
                        return [
                            'name' => basename($file),
                            'path' => Storage::url($file)
                        ];
                    });
                $photos = $photos->merge($subfolderPhotos);
            }

            return view('photos.index', ['photos' => $photos]);
        } catch (\Exception $e) {
            Log::error('Error in PhotoController@index: ' . $e->getMessage());
            return view('photos.index', ['photos' => collect([])]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'name' => 'required|string|max:255',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            $category = $request->category;
            $path = $this->getCategoryPath($category);
            
            // Buat direktori jika belum ada
            if (!Storage::disk('public')->exists($path)) {
                Storage::disk('public')->makeDirectory($path);
            }
            
            // Simpan file
            $file = $request->file('photo');
            $fileName = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            
            // Simpan file ke storage
            $filePath = $file->storeAs($path, $fileName, 'public');

            if (!$filePath) {
                throw new \Exception('Gagal menyimpan file');
            }

            return redirect()->back()->with('success', 'Foto berhasil diupload!');
        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengupload foto: ' . $e->getMessage());
        }
    }

    private function getCategoryPath($category)
    {
        switch ($category) {
            case 'jurusan_pplg':
                return 'photos/jurusan/pplg';
            case 'jurusan_tjkt':
                return 'photos/jurusan/tjkt';
            case 'jurusan_tflm':
                return 'photos/jurusan/tflm';
            case 'jurusan_tkr':
                return 'photos/jurusan/tkr';
            case 'agenda':
                return 'photos/agenda';
            case 'info':
                return 'photos/info';
            default:
                return 'photos';
        }
    }

    public function uploadForm()
    {
        return view('photos.upload');
    }

    public function destroy($category, $filename)
    {
        try {
            $path = $this->getCategoryPath($category) . '/' . $filename;
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                return redirect()->back()->with('success', 'Foto berhasil dihapus');
            }
            
            return redirect()->back()->with('error', 'Foto tidak ditemukan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus foto: ' . $e->getMessage());
        }
    }

    public function updateImage(Request $request, $photoId)
    {
        try {
            // Dapatkan data album dari AlbumController
            $albumController = new \App\Http\Controllers\AlbumController();
            $albumId = session('current_album_id');
            
            if (!$albumId) {
                throw new \Exception('ID Album tidak ditemukan');
            }

            $album = $albumController->getAlbumData($albumId);
            
            if (!$album) {
                throw new \Exception('Album tidak ditemukan');
            }

            // Cari foto yang akan diupdate
            $photo = null;
            foreach ($album['photos'] as $p) {
                if ($p['id'] == $photoId) {
                    $photo = $p;
                    break;
                }
            }

            if (!$photo) {
                throw new \Exception('Foto tidak ditemukan');
            }

            // Validate request
            $request->validate([
                'photo' => 'required|image|max:2048'
            ]);

            // Path untuk album spesifik
            $albumPath = "photos/album_{$albumId}";
            
            // Delete old file dari folder album
            $oldPath = "public/{$albumPath}/" . $photo['filename'];
            if (Storage::exists($oldPath)) {
                Storage::delete($oldPath);
            }

            // Store new file ke folder album
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $newPath = $albumPath . '/' . $filename;
            
            // Pastikan direktori album ada
            if (!Storage::disk('public')->exists($albumPath)) {
                Storage::disk('public')->makeDirectory($albumPath);
            }
            
            // Simpan file baru
            $file->storeAs("public/{$albumPath}", $filename);

            // Update photo data in album
            foreach ($album['photos'] as &$p) {
                if ($p['id'] == $photoId) {
                    $p['filename'] = $filename;
                    $p['url'] = Storage::url($newPath);
                    break;
                }
            }

            // Save updated album data
            $albumController->updateAlbumData($albumId, $album);

            // Hapus info foto lama jika ada
            $oldInfoPath = 'photos_info/' . pathinfo($photo['filename'], PATHINFO_FILENAME) . '.json';
            if (Storage::disk('public')->exists($oldInfoPath)) {
                Storage::disk('public')->delete($oldInfoPath);
            }

            // Simpan info foto baru
            $photoInfo = [
                'name' => pathinfo($filename, PATHINFO_FILENAME),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $newInfoPath = 'photos_info/' . pathinfo($filename, PATHINFO_FILENAME) . '.json';
            Storage::disk('public')->put($newInfoPath, json_encode($photoInfo));

            return redirect()->back()->with('success', 'Foto berhasil diperbarui');
        } catch (\Exception $e) {
            Log::error('Error updating photo: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memperbarui foto: ' . $e->getMessage());
        }
    }
} 