<?php

namespace App\Trait;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait UploadFileTrait
{
    public static function store(UploadedFile $file, string $publicStoragePath): string
    {
        $path = $file->store($publicStoragePath, 'public');
        return $path;
    }

    public static function delete(string $path)
    {
        if (Storage::exists('public/' . $path)) {
            Storage::delete('public/' . $path);
            return true;
        }
        return false;
    }
}