# Laravel Generic Resource

see [documentation](https://laravel.com/docs/5.8/eloquent-resources) API Resources

## Introduction

When building an API, you may need a transformation. This resource classes allow you easily transform 
your models and collections or maintain Laravel's JSON Resource.

## Install

```sh
composer require nekoos/laravel-generic-resource
```

## Example

```php
namespace App\Http\Resources;

use App\Export\UserExcelExport;
use App\Export\UserPdfExport;
use NekoOs\Illuminate\Http\Resources\GenericResource;

class UserGenericResource extends GenericResource
{
    public function toArray($request)
    {
        $isEmbedRoles = $request->has('embed-roles');
        return [
            'id' => $this->id,
            'name' => $this->name,
            'roles' => $this->when($isEmbedRoles, function () {
                return $this->roles;    
            })
        ];
    }

    public function doResponse($resource,$request)
    {
        $format = $request->header('x-format');
        if ($format == 'pdf') {
            $response = new UserPdfExport($resource);
        } elseif ($format == 'xls') {
            $response = new UserExcelExport($resource);
        } else { // Response Json
            $response = parent::doResponse($resource, $request);
        }
        return $response;
    }
}
```

```php
namespace App\Http\Controllers;

use App\Http\Resources\UserGenericResource;
use App\User;

class UserController extends Controller {
    
    public function index()
    {
        return UserGenericResource::collection(User::all());
    }

}
```