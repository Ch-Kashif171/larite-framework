<?php

namespace Lumite\Support\Pagination;

use Lumite\Support\Collection\Collection;
use Lumite\Support\Constants;
use Lumite\Support\Pagination\Paginator;
use ReturnTypeWillChange;

class Paginate implements \ArrayAccess, \IteratorAggregate, \Countable
{
    // Declare all pagination-related properties
    public mixed $total;
    public mixed $page;
    public mixed $per_page;
    public mixed $current_page;
    public mixed $last_page;
    public mixed $from;
    public mixed $to;
    public mixed $first_page_url;
    public mixed $last_page_url;
    public mixed $next_page_url;
    public mixed $prev_page_url;
    public mixed $path;
    public mixed $simple;
    public mixed $pageName;

    public Collection $data;


    public function __construct(array $pagination)
    {
        // Register as current paginator for the view layer convenience
        \Lumite\Support\Pagination\Paginator::setCurrent($this);
        foreach (Constants::KEYS as $key) {
            $this->{$key} = $pagination[$key] ?? null;
        }

        $this->data = new Collection($pagination['data'] ?? []);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = [];

        foreach (Constants::KEYS as $key) {
            $array[$key] = $this->{$key} ?? null;
        }

        $array['data'] = $this->data->toArray();

        return $array;
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->data->getIterator();
    }

    public function count(): int
    {
        return $this->data->count();
    }

    public function __call($method, $parameters)
    {
        // Handle pagination-specific methods
        if ($method === 'links' || $method === 'render') {
            return $this->links();
        }
        
        // Forward other methods to the data collection
        if (method_exists($this->data, $method)) {
            return $this->data->$method(...$parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->$offset ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === 'data' && is_array($value)) {
            $this->$offset = new Collection($value);
        } else {
            $this->$offset = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }

    /**
     * Render the pagination links
     * @param string $view
     * @return string
     */
    public function links(string $view = 'default'): string
    {
        // Check if this is a simple pagination
        if (isset($this->simple) && $this->simple) {
            return Paginator::simplePagination($this);
        }
        
        return Paginator::pagination($this);
    }

    /**
     * Get the pagination links as a string (alias for links method)
     * @return string
     */
    public function render(): string
    {
        return $this->links();
    }

    /**
     * Magic method to support old-style pagination syntax
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === 'links') {
            return $this->links();
        }
        
        return $this->$name ?? null;
    }
}
