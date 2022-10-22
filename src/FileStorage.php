<?php

namespace Jqqjj\LaravelFileStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Jqqjj\LaravelFileStorage\Exception\StorageException;
use Jqqjj\LaravelFileStorage\Models\FileStorage as FileStorageModel;

class FileStorage
{
    /**
     * @throws StorageException
     */
    public function upload(UploadedFile $file)
    {
        $path = $file->getPathname();
        $md5Hash = md5_file($path);
        $crc32Hash = hash_file('crc32b', $path);
        $model = FileStorageModel::where([
            'md5' => $md5Hash,
            'crc32' => $crc32Hash,
        ])->first();

        if (!empty($model)) {
            return new File($model);
        }

        //move file to private directory
        $sourceRelativeDirectory = implode(DIRECTORY_SEPARATOR, [
            'laravel_file_storage', 'files', substr($md5Hash,0,2), substr($md5Hash, 2,2),
        ]);
        $sourceFullName = $crc32Hash . '.' . $file->clientExtension();
        if (Storage::exists($sourceRelativeDirectory . DIRECTORY_SEPARATOR . $sourceFullName)) {
            Storage::delete($sourceRelativeDirectory . DIRECTORY_SEPARATOR . $sourceFullName);
        }
        if (!$file->storeAs($sourceRelativeDirectory, $sourceFullName)) {
            throw new StorageException();
        }

        $model = FileStorageModel::create([
            'path' => str_replace(DIRECTORY_SEPARATOR, '/', $sourceRelativeDirectory . DIRECTORY_SEPARATOR . $sourceFullName),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'md5' => $md5Hash,
            'crc32' => $crc32Hash,
        ]);
        return new File($model);
    }

    public function path($path)
    {
        return FileStorageModel::where(['path'=>$path])->first();
    }

    public function hash($md5Hash, $crc32Hash)
    {
        return FileStorageModel::where([
            'md5' => $md5Hash,
            'crc32' => $crc32Hash
        ])->first();
    }
}
