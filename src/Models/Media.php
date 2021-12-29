<?php

namespace iamx\Media\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'media',
        'thumbnail',
        'type',
        'collection',
        'disk',
        'path'
    ];

    public function model()
    {
        return $this->morphTo();
    }

    public function scopeFromCollection($query, $collection)
    {
        return $query->where('collection', $collection);
    }

    public function scopeFromDisk($query, $disk)
    {
        return $query->where('disk', $disk);
    }

    public function scopeWhereType($query, $type)
    {
        return $query->where('type', $type);
    }
}