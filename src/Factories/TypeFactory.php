<?php

namespace App\ApiModel\Factories;

use App\ApiModel\ApiModel;

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