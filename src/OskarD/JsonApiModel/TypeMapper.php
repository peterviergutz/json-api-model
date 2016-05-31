<?php

namespace OskarD\JsonApiModel;

use OskarD\JsonApiModel\Exceptions\TypeMapperException;

class TypeMapper
{
    /**
     * Types that should be mapped to classes outside the default namespace.
     * * Key = Type name
     * * Value = Classpath
     *
     * @var array
     */
    protected $mappedTypes = [
        // 'foo' => Bar\Foo::class,
    ];

    /**
     * Checks if a type should be mapped.
     *
     * @param $type
     * @return bool
     */
    public function shouldBeMapped($type)
    {
        return isset($this->getMappedTypes()[$type]) || array_key_exists($type, $this->getMappedTypes());
    }

    /**
     * Gets the mapped class name for the type.
     *
     * @param $type
     * @return mixed
     * @throws \OskarD\JsonApiModel\Exceptions\TypeMapperException
     */
    public function getMappedClass($type)
    {
        if (! static::shouldBeMapped($type)) {
            throw new TypeMapperException("Could not map $type to any class");
        }

        return $this->getMappedTypes()[$type];
    }

    /**
     * Gets the mapped types.
     *
     * @return mixed
     */
    public function getMappedTypes()
    {
        return $this->mappedTypes;
    }

    /**
     * Sets the mapped types.
     *
     * @param $mappedTypes
     */
    public function setMappedTypes($mappedTypes)
    {
        $this->mappedTypes = $mappedTypes;
    }
}