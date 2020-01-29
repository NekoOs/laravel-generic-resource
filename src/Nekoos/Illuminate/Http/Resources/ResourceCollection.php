<?php


namespace NekoOs\Illuminate\Http\Resources;


use Countable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\CollectsResources;
use Illuminate\Support\Collection;
use IteratorAggregate;

class ResourceCollection extends GenericResource implements Countable, IteratorAggregate
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var Collection
     */
    public $collection;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     *
     * @return void
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Return the count of items in the resource collection.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(static function ($resource) use ($request) {
            /** @var GenericResource $resource */
            return $resource->toArray($request);
        })->all();
    }
}
