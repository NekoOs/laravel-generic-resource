<?php

namespace NekoOs\Illuminate\Http\Resources;

use ArrayAccess;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Symfony\Component\HttpFoundation\Response;

class GenericResource implements ArrayAccess, Responsable, UrlRoutable
{
    use ConditionallyLoadsAttributes, DelegatesToResource;

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string
     */
    public static $wrap = 'data';

    /**
     * The resource instance.
     *
     * @var mixed
     */
    public $resource;

    /**
     * The additional data that should be added to the top-level resource array.
     *
     * @var array
     */
    public $with = [];

    /**
     * The additional meta data that should be added to the resource response.
     *
     * Added during response construction by the developer.
     *
     * @var array
     */
    public $additional = [];

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     *
     * @return void
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Create a new resource instance.
     *
     * @param mixed ...$parameters
     *
     * @return static
     */
    public static function make(...$parameters)
    {
        return new static(...$parameters);
    }

    /**
     * Create new anonymous resource collection.
     *
     * @param mixed $resource
     *
     * @return AnonymousResourceCollection
     */
    public static function collection($resource)
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

    /**
     * Resolve the resource to an array.
     *
     * @param Request|null $request
     *
     * @return array
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function resolve($request = null)
    {
        $data = $this->toArray(
            $request = $request ?: Container::getInstance()->make('request')
        );

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        return $this->filter((array)$data);
    }

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        if ($this->resource === null) {
            return [];
        }

        return is_array($this->resource)
            ? $this->resource
            : $this->resource->toArray();
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function with($request)
    {
        return $this->with;
    }

    /**
     * Add additional meta data to the resource response.
     *
     * @param array $data
     *
     * @return $this
     */
    public function additional(array $data)
    {
        $this->additional = $data;

        return $this;
    }

    /**
     * Customize the response for a request.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return void
     */
    public function withResponse($request, $response)
    {

    }

    /**
     * Set the string that should wrap the outer-most resource array.
     *
     * @param string $value
     *
     * @return void
     */
    public static function wrap($value)
    {
        static::$wrap = $value;
    }

    /**
     * Disable wrapping of the outer-most resource array.
     *
     * @return void
     */
    public static function withoutWrapping()
    {
        static::$wrap = null;
    }

    /**
     * Transform the resource into an HTTP response.
     *
     * @param Request|null $request
     *
     * @return GenericResource
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function response($request = null)
    {
        return $this->toResponse(
            $request ?: Container::getInstance()->make('request')
        );
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     *
     * @return GenericResource
     */
    public function toResponse($request)
    {
        return (new ResourceResponse($this))->toResponse($request);
    }

    /**
     * @param array   $resource
     * @param Request $request
     *
     * @return mixed
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function doResponse($resource, $request)
    {
        return tap(Container::getInstance()->make(ResponseFactory::class)->json($resource), function ($response) use ($request) {
            $response->original = $this->resource;
            $this->resource->withResponse($request, $response);
        });
    }
}
