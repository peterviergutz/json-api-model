<?php

namespace OskarD\JsonApiModel\Factories;

use Art4\JsonApiClient\Document;
use Art4\JsonApiClient\DocumentInterface;
use Art4\JsonApiClient\ElementInterface;
use Art4\JsonApiClient\Resource\CollectionInterface;
use Art4\JsonApiClient\Resource\IdentifierCollectionInterface;
use Art4\JsonApiClient\Resource\Item;
use Art4\JsonApiClient\Utils\DataContainer;
use OskarD\JsonApiModel\ApiModel;
use OskarD\JsonApiModel\Exceptions\ApiModelBuilderException;
use OskarD\JsonApiModel\TypeMapper;

class ApiModelFactory
{
    /**
     * Types that have their own factories.
     * * Key = Type name
     * * Value = Factory classpath
     *
     * @var array
     */
    protected $typeFactories = [
        // 'foo' => Bar\FooFactory::class,
    ];

    /** @var Document */
    protected $document;

    /** @var  array */
    protected $included;

    /** @var  TypeMapper */
    protected $typeMapper;

    /**
     * ApiModelFactory constructor.
     *
     * @param \Art4\JsonApiClient\Document $document
     */
    public function __construct(Document $document)
    {
        $this->setDocument($document);
    }

    /**
     * Builds a set of <code>ApiModel</code>s from the
     * <code>DocumentInterface</code>.
     *
     * @return array|\OskarD\JsonApiModel\ApiModel
     */
    public function build()
    {
        $this->parseIncludedCollection();

        return $this->buildResource($this->document->get('data'));
    }

    /**
     * Builds an <code>ApiModel</code> from an <code>ElementInterface</code>.
     *
     * @param \Art4\JsonApiClient\ElementInterface $resource
     * @return array|\OskarD\JsonApiModel\ApiModel
     */
    protected function buildResource(ElementInterface $resource)
    {
        if ($this->isCollection($resource)) {
            return $this->buildFromCollection($resource);
        }

        /** @var Item $resource */
        return [$this->buildFromItem($resource)];
    }

    /**
     * Sets the <code>Document</code> to build from.
     *
     * @param \Art4\JsonApiClient\DocumentInterface $document
     */
    protected function setDocument(DocumentInterface $document)
    {
        $this->document = $document;
    }

    /**
     * Checks if a resource is a type of <code>Collection</code>.
     *
     * @param $resource
     * @return bool
     */
    protected function isCollection($resource)
    {
        return is_a($resource, CollectionInterface::class) || is_a($resource,
            IdentifierCollectionInterface::class);
    }

    /**
     * Checks if the <code>Document</code> being built has a <code>Collection</code>
     * in its data or not.
     *
     * @return bool
     */
    protected function documentContainsACollection()
    {
        return $this->document->has('data') && is_a($this->document->get('data'),
            CollectionInterface::class);
    }

    /**
     * Builds objects from an <code>ElementInterface</code> <code>Collection</code>.
     *
     * @param \Art4\JsonApiClient\ElementInterface $collection
     * @return array
     */
    protected function buildFromCollection(ElementInterface $collection)
    {
        $items = [];

        /** @var ElementInterface $collectionData */
        $collectionData = $collection;

        foreach ($collectionData->asArray() as $item) {
            $items[] = $this->buildFromItem($item);
        }

        return $items;
    }

    /**
     * Builds an object from a non-<code>Collection</code> <code>Document</code>.
     *
     * @param \Art4\JsonApiClient\Resource\Item $item
     * @return \Art4\JsonApiClient\Resource\ItemInterface|\OskarD\JsonApiModel\ApiModel
     * @throws \OskarD\JsonApiModel\Exceptions\ApiModelBuilderException
     */
    protected function buildFromItem(Item $item)
    {
        $item = $this->buildItem($item);
        $this->completeRelations($item);

        return $item;
    }

    /**
     * Parses the <code>included</code> part of the <code>DocumentInterface</code>,
     * if it exists.
     *
     * @throws \OskarD\JsonApiModel\Exceptions\ApiModelBuilderException
     */
    protected function parseIncludedCollection()
    {
        $this->included = [];

        if ($this->document->has('included')) {
            $includedArray = $this->document->get('included')->asArray();

            foreach ($includedArray as $included) {
                $this->included[] = $this->buildItem($included);
            }
        }
    }

    /**
     * Builds an <code>ApiModel</code> from an <code>Item</code>.
     *
     * @param \Art4\JsonApiClient\Resource\Item $item
     * @return \OskarD\JsonApiModel\ApiModel
     * @throws \OskarD\JsonApiModel\Exceptions\ApiModelBuilderException
     */
    protected function buildItem(Item $item)
    {
        $itemAttributes = $this->extractItemAttributes($item);

        $object = $this->buildApiModel($item->get('type'), $itemAttributes);

        if (! is_a($object, ApiModel::class)) {
            throw new ApiModelBuilderException("Tried to build an object from a class that does not extend ApiModel");
        }

        $object->setRelationships($this->extractRelationships($item));

        return $object;
    }

    /**
     * Builds an <code>ApiModel</code>.
     *
     * @param string $typeName
     * @param array  $itemAttributes
     * @return \OskarD\JsonApiModel\ApiModel
     */
    protected function buildApiModel($typeName, array $itemAttributes)
    {
        if ($this->typeHasFactory($typeName)) {
            return $this->buildWithFactory($typeName, $itemAttributes);
        }

        $className = $this->getClassName($typeName);

        return new $className($itemAttributes);
    }

    /**
     * Gets the class name to be used for a specific type.
     *
     * @param $typeName
     * @return mixed|string
     * @throws \OskarD\JsonApiModel\Exceptions\TypeMapperException
     */
    protected function getClassName($typeName)
    {
        if ($this->getTypeMapper()->shouldBeMapped($typeName)) {
            return $this->getTypeMapper()->getMappedClass($typeName);
        }

        return ApiModel::getDefaultNamespace() . $typeName;
    }

    /**
     * Checks if a type has its own factory.
     *
     * @param string $typeName
     * @return bool
     */
    protected function typeHasFactory($typeName)
    {
        return key_exists($typeName, $this->getTypeFactories());
    }

    /**
     * Sets the types that have their own factories.
     *
     * @param array $typeFactories
     */
    public function setTypeFactories(array $typeFactories)
    {
        $this->typeFactories = $typeFactories;
    }

    /**
     * Gets the types that have their own factories.
     *
     * @return array
     */
    public function getTypeFactories()
    {
        return $this->typeFactories;
    }

    /**
     * Gets the <code>TypeMapper</code> used.
     *
     * @return null|\OskarD\JsonApiModel\TypeMapper
     */
    protected function getTypeMapper()
    {
        if ($this->typeMapper === null) {
            $this->typeMapper = new TypeMapper();
        }

        return $this->typeMapper;
    }

    /**
     * Gets the types that should be mapped.
     *
     * @return array
     */
    public function getMappedTypes()
    {
        return $this->getTypeMapper()->getMappedTypes();
    }

    /**
     * Sets the types that should be mapped.
     *
     * @param array $mappedTypes
     */
    public function setMappedTypes(array $mappedTypes)
    {
        $this->getTypeMapper()->setMappedTypes($mappedTypes);
    }

    /**
     * Builds an <code>ApiModel</code> with the provided type's
     * <code>TypeFactory</code>.
     *
     * @param string $typeName
     * @param array  $itemAttributes
     * @return \OskarD\JsonApiModel\ApiModel
     */
    protected function buildWithFactory($typeName, array $itemAttributes)
    {
        $typeFactories = $this->getTypeFactories();

        /** @var TypeFactory $factory */
        $factory = new $typeFactories[$typeName]();

        return $factory->build($itemAttributes);
    }

    /**
     * Adds attributes to the relations of an <code>ApiModel</code>.
     *
     * @param \OskarD\JsonApiModel\ApiModel $object
     */
    protected function completeRelations(ApiModel $object)
    {
        $relations = $object->getRelationships();

        for ($i = 0; $i < count($relations); $i++) {
            $relationClass = get_class($relations[$i]);

            foreach ($this->included as $include) {
                if (is_a($include,
                        $relationClass) && $include->id == $relations[$i]->id
                ) {
                    $relations[$i] = $include;
                    break;
                }
            }
        }

        $object->setRelationships($relations);
    }

    /**
     * Extracts the relationships from an <code>Item</code>.
     *
     * @param \Art4\JsonApiClient\Resource\Item $item
     * @return array
     */
    protected function extractRelationships(Item $item)
    {
        $relationships = [];

        if (! $item->has('relationships')) {
            return $relationships;
        }

        foreach ($item->get('relationships')->asArray() as $relationship) {
            /** @var ElementInterface $relationship */

            if ($relationship->has('data')) { // TODO: Relationship can contain only links
                $relationshipContainer = $relationship->get('data');

                if ($this->isCollection($relationshipContainer)) {
                    /** @var DataContainer $relationshipContainer */
                    $relationshipContainer = $relationshipContainer->asArray();
                } else {
                    $relationshipContainer = [$relationshipContainer];
                }

                foreach ($relationshipContainer as $relationshipNode) {
                    /** @var ElementInterface $relationshipNode */
                    $typeName = $relationshipNode->get('type');

                    $className = $this->getClassName($typeName);

                    $properties = ['id' => $relationshipNode->get('id')];

                    $relationships[] = new $className($properties);
                }
            }
        }

        return $relationships;
    }

    /**
     * Parses an item retrieved with the API and gets the resource's ID and
     * attributes.
     *
     * @param \Art4\JsonApiClient\Resource\Item $item
     * @return array
     */
    protected function extractItemAttributes(Item $item)
    {
        $attributes = $item->get('attributes')->asArray();

        $attributes = array_merge(['id' => $item->get('id')], $attributes);

        return $attributes;
    }
}