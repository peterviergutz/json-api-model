<?php

namespace OskarD\JsonApiModel;

abstract class ApiModel implements ApiModelInterface
{

    /**
     * Relationships associated with this resource.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Gets any relationships assigned to the resource. If a classpath is passed to
     * <code>$class</code>, only relationships of that class are returned.
     *
     * @param string $class
     * @return array
     */
    public function getRelationships($class = null)
    {
        if (is_null($class)) {
            return $this->relationships;
        }

        return array_filter($this->relationships,
            function ($relationship) use ($class) {
                if (is_a($relationship, $class)) {
                    return true;
                }

                return false;
            });
    }

    /**
     * Sets the relationships associated with the resource.
     * 
     * @param array $relationships
     */
    public function setRelationships(array $relationships)
    {
        $this->relationships = $relationships;
    }

    /**
     * ArrayAccess implementation.
     */

    /** @var array The attributes of the resource. */
    protected $attributes = [];

    /**
     * ApiModel constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return (isset($this->attributes[$key]));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relationships[$key]);
    }

}
