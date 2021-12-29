<?php

namespace iamx\Media\Traits;

use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Str;
use iamx\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Intervention\Image\ImageManagerStatic as Image;

trait MediaTrait {

    private $disk = "public";
    private $name;
    private $file;
    private $collection = "default";
    private $thumbnail = false;
    private $thumbnailWidth;
    private $thumbnailHeight;

    /**
     * Relationship with Media Model
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * Set media file 
     * 
     * @param File $file
     * @return $this
     */
    public function addMedia($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * Enable file thumbnail
     * 
     * @param int|null $thumbnailWidth
     * @param int|null $thumbnailHeight
     * @return $this
     */
    public function withThumbnail($thumbnailWidth = 100, $thumbnailHeight = 100)
    {  
        $this->thumbnail = true;
        $this->thumbnailWidth = $thumbnailWidth;
        $this->thumbnailHeight = $thumbnailHeight;

        return $this;
    }

    /**
     * Set media disk
     * 
     * @param string $disk|public
     * @return $this
     */
    public function toDisk($disk = "public")
    {
        $this->disk =  $disk;
        return $this;
    }

    /**
     * Set media collection
     * 
     * @param string $collection|default
     * @return $this
     */
    public function toCollection($collection = "default")
    {
        $this->collection =  $collection;
        return $this;
    }

    /**
     * Set media file name
     * 
     * @param string $name
     * @return $this
     */
    public function withName($name)
    {
        $this->name =  $name;
        return $this;
    }

    /**
     * Save media in storage and DB
     * 
     * @return void
     */
    public function store()
    {
        $folderId = $this->id;

        if(!Storage::disk($this->disk)->exists('/' . $folderId)) {
            Storage::disk($this->disk)->makeDirectory('/' . $folderId, 0775, true);
        }

        
        $extension = $this->file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;

        $thumbnailName = 'thumbnail_' . $fileName;

        if($this->name && $this->name != '') {
            $fileName = $this->name . '.' . $extension;
            $thumbnailName = 'thumbnail_' . $this->name . '.' . $extension;
        }
        
        if($this->thumbnail && $this->thumbnail == true) {

            $storagePath = Storage::disk($this->disk)->path($folderId . '/' . $thumbnailName);
            $img = Image::make($this->file->getRealPath());
            $img->resize($this->thumbnailWidth, $this->thumbnailHeight,  function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($storagePath);

        }

        Storage::disk($this->disk)->put($folderId . '/' . $fileName, file_get_contents($this->file));

        $data['media'] = $fileName;
        $data['thumbnail'] = $this->thumbnail && $this->thumbnail == true ? $thumbnailName : NULL; 
        $data['type'] = $extension;
        $data['collection'] = $this->collection;
        $data['path'] = $folderId . '/';
        $data['disk'] = $this->disk;

        $this->media()->create($data);
    }

    /**
     * Delete all media
     * 
     * @param int|null $mediaId
     * @return void
     */
    public function deleteMedia($mediaId = null)
    {
        if($this->hasMedia()) {

            if($mediaId && $mediaId != '') {

                $media = Media::find($mediaId);

                if($media) {

                    if(Storage::disk($media->disk)->exists($media->path . $media->media)) {
                        Storage::disk($media->disk)->delete($media->path . $media->media);
                    }
            
                    if($media->thumbnail && $media->thumbnail != '') {
            
                        if(Storage::disk($media->disk)->exists($media->path . $media->thumbnail)) {
                            Storage::disk($media->disk)->delete($media->path . $media->thumbnail);
                        }
                    }

                    $media->delete();
                }

            } else {

                $this->media->each(function($item) {

                    if(Storage::disk($item->disk)->exists($item->path . $item->media)) {
                        Storage::disk($item->disk)->delete($item->path . $item->media);
                    }
    
                    if($item->thumbnail && $item->thumbnail != '') {
    
                        if(Storage::disk($item->disk)->exists($item->path . $item->thumbnail)) {
                            Storage::disk($item->disk)->delete($item->path . $item->thumbnail);
                        }
                    }
    
                    $item->delete();
    
                });
            }
        }
    }

    /**
     * Delete all media by collection
     * 
     * @param string $collection
     * @return void
     */
    public function deleteCollection($collection)
    {
        if($this->hasMedia()) {

            $media = $this->media()->where('collection', $collection)->get();;

            $media->each(function($item) {

                if(Storage::disk($item->disk)->exists($item->path . $item->media)) {
                    Storage::disk($item->disk)->delete($item->path . $item->media);
                }

                if($item->thumbnail && $item->thumbnail != '') {

                    if(Storage::disk($item->disk)->exists($item->path . $item->thumbnail)) {
                        Storage::disk($item->disk)->delete($item->path . $item->thumbnail);
                    }
                }

                $item->delete();

            });
        }
    }

    /**
     * Update media library
     * 
     * @param File $file
     * @param int $mediaId
     * @param string|null $name
     * @param bool|null $thumbnail
     * @param int|null $thumbnailWidth
     * @param int|null $thumbnailHeight
     * @return void
     */
    public function updateMedia($file, $mediaId, $name = null, $thumbnail = true, $thumbnailWidth = 100, $thumbnailHeight = 100)
    {
        $media = Media::find($mediaId);

        if($media) {

            $folderId = $this->id;

            if(!Storage::disk($media->disk)->exists('/' . $folderId)) {
                Storage::disk($media->disk)->makeDirectory('/' . $folderId, 0775, true);
            }

            
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            $thumbnailName = 'thumbnail_' . $fileName;

            if($name && $name != '') {
                $fileName = $name . '.' . $extension;
                $thumbnailName = 'thumbnail_' . $name . '.' . $extension;
            }
            
            if($thumbnail && $thumbnail == true) {

                $img = Image::make($file->getRealPath());
                $img->resize($this->thumbnailWidth, $this->thumbnailHeight,  function ($constraint) {
                    $constraint->aspectRatio();
                });

                $storagePath = Storage::disk($media->disk)->path($folderId . '/' . $thumbnailName);
                $img->save($storagePath);

            }

            Storage::disk($media->disk)->put($folderId . '/' . $fileName, file_get_contents($file));

            if(Storage::disk($media->disk)->exists($media->path . $media->media)) {
                Storage::disk($media->disk)->delete($media->path . $media->media);
            }
    
            if($media->thumbnail && $media->thumbnail != '') {
    
                if(Storage::disk($media->disk)->exists($media->path . $media->thumbnail)) {
                    Storage::disk($media->disk)->delete($media->path . $media->thumbnail);
                }
            }

            $data['media'] = $fileName;
            $data['thumbnail'] = $thumbnail && $thumbnail == true ? $thumbnailName : NULL; 
            $data['type'] = $extension;

            $media->update($data);            

        }
    }

    /**
     * Verify is model has media
     * 
     * @return bool
     */
    public function hasMedia()
    {
        return $this->media()->exists();
    }

    /**
     * Get media library
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMedia() 
    {
        return $this->transformMedia($this->hasMedia() ? $this->media : []);
    }

    /**
     * Create media_library attribute
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMediaLibraryAttribute()
    {
        return $this->transformMedia($this->hasMedia() ? $this->media : []);
    }

    /**
     * Get media from collection
     * 
     * @param string $collection
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function mediaFromCollection($collection)
    {
        return $this->transformMedia($this->media()->fromCollection($collection)->get());
    }

    /**
     * Get media from disk
     * 
     * @param string $disk
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function mediaFromDisk($disk)
    {
        return $this->transformMedia($this->media()->fromDisk($disk)->get());
    }

    /**
     * Get media by type
     * 
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function mediaWhereType($type)
    {
        return $this->transformMedia($this->media()->whereType($type)->get());
    }

    /**
     * Transform media library 
     * 
     * @param string $collection
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function transformMedia(Collection $media)
    {
        $appUrl = config('app.url');

        $collection = collect();
        $media->each(function($item) use ($collection, $appUrl) {

            $disk = $item->disk && $item->disk != 'public' ? $item->disk . '/' : '';

            $collection->push([
                'id' => $item->id,
                'name' => $item->media,
                'type' => $item->type,
                'collection' => $item->collection,
                'media' => $appUrl . '/storage/' . $disk. $item->path . $item->media,
                'thumbnail' => $item->thumbnail && $item->thumbnail != '' ? $appUrl . '/storage/' . $disk . $item->path . $item->thumbnail : NULL,
            ]);
        });

        return $collection;
    }
}