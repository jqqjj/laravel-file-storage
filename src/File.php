<?php

namespace Jqqjj\LaravelFileStorage;

use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Storage;
use Jqqjj\LaravelFileStorage\Exception\StorageException;

class File
{
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function __call($name, $args)
    {
        return $this->model->$name(...$args);
    }

    public function __set($name, $value)
    {
        $this->model->$name = $value;
    }

    public function __get($name)
    {
        return $this->model->$name;
    }

    /**
     * @throws StorageException
     */
    public function link()
    {
        //link source
        $linkRelativeDirectory = implode(DIRECTORY_SEPARATOR, [
            'upload', date('Y-m'), date('d'),
        ]);
        $linkFullDirectory = Storage::disk('public')->path($linkRelativeDirectory);
        if (!FileFacade::isDirectory($linkFullDirectory) && !FileFacade::makeDirectory($linkFullDirectory, 0777, true, true)) {
            throw new StorageException();
        }

        //link target
        $targetFullPath = Storage::path($this->model->path);
        $link = $linkFullDirectory . DIRECTORY_SEPARATOR . basename($this->model->path);
        if (!Storage::disk('public')->exists($linkRelativeDirectory . DIRECTORY_SEPARATOR . basename($this->model->path))) {
            FileFacade::link($targetFullPath, $link);
        }

        return $linkRelativeDirectory . DIRECTORY_SEPARATOR . basename($this->model->path);
    }
}
