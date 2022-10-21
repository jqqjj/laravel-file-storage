<?php

namespace Jqqjj\LaravelFileStorage\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Jqqjj\LaravelFileStorage\File;

/**
 * @method static File upload(UploadedFile $file)
 * @method static File|null path($path)
 * @method static File|null hash($md5Hash, $sha1Hash)
 *
 * @see \Jqqjj\LaravelFileStorage\FileStorage
 */
class FileStorage extends Facade
{
    protected static function getFacadeAccessor()
    {
        return "laravel.file_storage";
    }
}
