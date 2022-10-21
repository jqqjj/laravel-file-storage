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
        $sha1Hash = sha1_file($path);
        $model = FileStorageModel::where([
            'md5' => $md5Hash,
            'sha1' => $sha1Hash,
        ])->first();

        if (!empty($model)) {
            return new File($model);
        }

        //move file to private directory
        $sourceRelativeDirectory = implode(DIRECTORY_SEPARATOR, [
            'laravel_file_storage', substr($md5Hash,0,2), substr($md5Hash, 2,2),
        ]);
        $sourceFullName = $sha1Hash . '_' . $file->getSize() . '.' . $file->clientExtension();
        if (Storage::exists($sourceRelativeDirectory . DIRECTORY_SEPARATOR . $sourceFullName)) {
            unlink($sourceRelativeDirectory . DIRECTORY_SEPARATOR . $sourceFullName);
        }
        if (!$file->storeAs($sourceRelativeDirectory, $sourceFullName)) {
            throw new StorageException();
        }

        $model = FileStorageModel::create([
            'path' => $sourceRelativeDirectory . DIRECTORY_SEPARATOR . $sourceFullName,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'md5' => $md5Hash,
            'sha1' => $sha1Hash,
        ]);
        return new File($model);
    }

    public function path($path)
    {
        return FileStorageModel::where(['path'=>$path])->first();
    }

    public function hash($md5Hash, $sha1Hash)
    {
        return FileStorageModel::where([
            'md5' => $md5Hash,
            'sha1' => $sha1Hash
        ])->first();
    }
}
