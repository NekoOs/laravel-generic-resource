<?php

/**
 * @noinspection PhpUndefinedNamespaceInspection Model
 * @noinspection PhpUndefinedClassInspection     Model
 */

namespace NekoOs\Illuminate\Http\Resources;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ResourceResponse implements Responsable
{
    /**
     * The underlying resource.
     *
     * @var mixed|AnonymousResourceCollection
     */
    public $resource;

    /**
     * Create a new resource response.
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
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     *
     * @return GenericResource
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function toResponse($request)
    {
        if ($this->resource instanceof AnonymousResourceCollection) {
            $resource = Container::getInstance()->make($this->resource->collects, ['resource' => null]);
        } else {
            $resource = $this->resource;
        }

        $value = $this->wrap(
            $this->resource->resolve($request),
            $this->resource->with($request),
            $this->resource->additional
        );

        if ($resource instanceof GenericResource) {
            $resource->resource = $this->resource;
            return $resource->doResponse($value, $request);
        }

        return tap(Container::getInstance()->make(ResponseFactory::class)->json($value, $this->calculateStatus()), function ($response) use ($request) {
            $response->original = $this->resource;
            $this->resource->withResponse($request, $response);
        });
    }

    /**
     * Wrap the given data if necessary.
     *
     * @param array $data
     * @param array $with
     * @param array $additional
     *
     * @return array
     */
    protected function wrap($data, $with = [], $additional = [])
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        if ($this->haveDefaultWrapperAndDataIsUnwrapped($data)) {
            $data = [$this->wrapper() => $data];
        } elseif ($this->haveAdditionalInformationAndDataIsUnwrapped($data, $with, $additional)) {
            $data = [($this->wrapper() ?? 'data') => $data];
        }

        return array_merge_recursive($data, $with, $additional);
    }

    /**
     * Determine if we have a default wrapper and the given data is unwrapped.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function haveDefaultWrapperAndDataIsUnwrapped($data)
    {
        return $this->wrapper() && !array_key_exists($this->wrapper(), $data);
    }

    /**
     * Determine if "with" data has been added and our data is unwrapped.
     *
     * @param array $data
     * @param array $with
     * @param array $additional
     *
     * @return bool
     */
    protected function haveAdditionalInformationAndDataIsUnwrapped($data, $with, $additional)
    {
        return (!empty($with) || !empty($additional)) &&
            (!$this->wrapper() ||
                !array_key_exists($this->wrapper(), $data));
    }

    /**
     * Get the default data wrapper for the resource.
     *
     * @return string
     */
    protected function wrapper()
    {
        return get_class($this->resource)::$wrap;
    }

    /**
     * Calculate the appropriate status code for the response.
     *
     * @return int
     */
    protected function calculateStatus()
    {
        return $this->resource->resource instanceof Model &&
        $this->resource->resource->wasRecentlyCreated ? 201 : 200;
    }
}

