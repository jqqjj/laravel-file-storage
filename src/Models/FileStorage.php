<?php

namespace Jqqjj\LaravelFileStorage\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $file_storage_id
 * @property string $path
 * @property int $size
 * @property string $mime
 * @property string $md5
 * @property string $crc32
 * @property string $created_at
 * @property string $updated_at
 *
 */
class FileStorage extends Model
{
    protected $primaryKey = 'file_storage_id';

    protected $fillable = [
        'path',
        'size',
        'mime',
        'md5',
        'crc32',
    ];
}
