# Laravel Media Library

### Installation

```sh
$ composer require iamx/laravel-media
```
### Publish migration file

```sh
$ php artisan vendor:publish --provider="iamx\Media\Providers\MediaServiceProvider" --tag="migrations"
```
### Usage

##### Import the media trait in your model
Note: Only available for images for now

```php
<?php

namespace App;

use iamx\Media\Traits\MediaTrait;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use MediaTrait;
}

```
Quick example:

```php

public function store(Request $request)
{
    $this->validate($request, [
        'avatar' => 'required|image|mimes:png,jpg,jpeg'
    ]);

    $user = User::first();

    $user->addMedia($request->file('avatar'))->store();
}
```

##### Upload options:

```php
public function store(Request $request)
{
    $this->validate($request, [
        'avatar' => 'required|image|mimes:png,jpg,jpeg'
    ]);

    $user = User::first();

    $user->addMedia($request->file('avatar'))
            ->withThumbnail(200, 200) // Create a thumbnail of file, accepts with and height values
            ->withName('avatar') // Set custom media name
            ->toDisk('avatars') // Set storage disk
            ->toCollection('gallery') // Set collection name
            ->store();
}

```

##### Getting media of model:

```php

$user = User::first();

// Verify is model has media
if($user->hasMedia()) {
    return response()->json($user->getMedia());
}

// Access to media
$user->getMedia();
// Or
$user->media_library;

```
##### It will return a response like this:

```json

[
    {
        "id": 4,
        "name": "d6871be4-2690-4965-a98a-df835973fc4b.png",
        "type": "png",
        "collection": "gallery",
        "media": "http://laravel-packages.test/storage/1/d6871be4-2690-4965-a98a-df835973fc4b.png",
        "thumbnail": "http://laravel-packages.test/storage/1/thumbnail_d6871be4-2690-4965-a98a-df835973fc4b.png"
    },
    ...
]
```

##### Update a item of media

```php
public function update(Request $request, $id)
{
    $this->validate($request, [
        'avatar' => 'required|image|mimes:png,jpg,jpeg'
    ]);

    $user = User::find($id);
    $mediaId = 3;
    $user->updateMedia($request->file('avatar'), $mediaId);
}
```

##### Optional parameters of updateMedia function:

```php
public function update(Request $request, $id)
{
    $this->validate($request, [
        'avatar' => 'required|image|mimes:png,jpg,jpeg'
    ]);

    $user = User::find($id);
    $mediaId = 3;
    $mediaName = 'avatar';
    $createThumbnail = true;
    $thumbnailWidth = 200;
    $thumbnailHeight = 200;

    $user->updateMedia($request->file('avatar'), $mediaId, $mediaName, $createThumbnail, $thumbnailWidth, $thumbnailHeight);
    
}
```

##### Delete media

```php
public function destroy($id)
{
    $user = User::find($id);

    // Delete all related media
    $user->deleteMedia();

    // Delete a single media by id
    $mediaId = 3;
    $user->deleteMedia($mediaId);

    // Delete a entire collection
    $collectionName = "gallery";
    $user->deleteCollection($collectionName);
}
```