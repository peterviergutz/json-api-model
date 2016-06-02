<?php

namespace OskarD\JsonApiModel;

use ArrayAccess;

interface ApiModelInterface extends ArrayAccess
{

    /**
     * Gets any relationships assigned to the resource. If a classpath is passed to
     * <code>$class</code>, only relationships of that class are returned.
     *
     * @param string $class
     * @return array
     */
    public function getRelationships($class = null);

    /**
     * Sets the relationships associated with the resource.
     *
     * @param array $relationships
     */
    public function setRelationships(array $relationships);

    /**
     * ApiModel constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = []);

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key);

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value);

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key);

    /**
     * Unset an attribute on the model.
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key);
}