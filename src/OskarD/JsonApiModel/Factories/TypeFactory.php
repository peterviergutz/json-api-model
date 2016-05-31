<?php

namespace OskarD\JsonApiModel\Factories;

use OskarD\JsonApiModel\ApiModel;

interface TypeFactory
{
    /**
     * Builds an instance of the type.
     *
     * @param $attributes
     * @return ApiModel
     */
    public function build($attributes);
}