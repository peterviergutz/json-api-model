<?php

namespace App\ApiModel\Factories;

use App\ApiModel\ApiModel;
use App\ApiModel\Exceptions\ApiClassBuilderException;
use App\ApiModel\TypeMapper;
use Art4\JsonApiClient\Document;
use Art4\JsonApiClient\Resource\Collection;
use Art4\JsonApiClient\Resource\Item;

class ApiModelFactory
{
    const API_DEFAULT_MODEL_NAMESPACE = 'App\\';

    /**
     * Type names that have their own factories.
     *
     * * Key = Type name
     * * Value = Classpath
     *
     * @var array
     */
    protected $typeFactories = [];

    /**
     * @var Document
     */
    protected $document;

    /**
     * ApiClassBuilder constructor.
     *
     * @param \Art4\JsonApiClient\Document $document
     */
    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    /**
     * Builds an <code>ApiModel</code> from the <code>Document</code>.
     *
     * @return array
     */
    public function build()
    {
        if($this->isResponseACollection())
        {
            return $this->buildFromCollection();
        }

        return $this->buildFromSimple();
    }

    /**
     * Checks if a <code>Document</code> contains a <code>Collection</code> or not.
     *
     * @return bool
     */
    protected function isResponseACollection()
    {
        return $this->document->has('data') && is_a($this->document->get('data'),
            Collection::class);
    }

    /**
     * Builds objects from a <code>Collection</code> <code>Document</code>.
     *
     * @return array
     * @throws \App\ApiModel\Exceptions\ApiClassBuilderException
     */
    protected function buildFromCollection()
    {
        $includes = $this->parseIncludedCollection();

        $items = [];

        foreach($this->document->get('data')->asArray() as $item)
        {
            $item = $this->buildItem($item);
            $this->completeRelations($item, $includes);

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Builds an object from a non-<code>Collection</code> <code>Document</code>.
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \App\ApiModel\Exceptions\ApiClassBuilderException
     */
    protected function buildFromSimple()
    {
        $item = $this->buildItem($this->document->get('data'));
        $this->completeRelations($item, $this->parseIncludedCollection());

        return $item;
    }

    /**
     * Parses the <code>included</code> part of a <code>Document</code>, if it
     * exists.
     *
     * @return array
     * @throws \App\ApiModel\Exceptions\ApiClassBuilderException
     */
    protected function parseIncludedCollection()
    {
        $result = [];

        if(! $this->document->has('included'))
        {
            return $result;
        }

        $includes = $this->document->get('included')->asArray();

        foreach($includes as $included)
        {
            $result[] = $this->buildItem($included);
        }

        return $result;
    }

    /**
     * Builds an <code>ApiModel</code> from an <code>Item</code>.
     *
     * @param \Art4\JsonApiClient\Resource\Item $item
     * @return \App\ApiModel\ApiModel
     * @throws \App\ApiModel\Exceptions\ApiClassBuilderException
     */
    protected function buildItem(Item $item)
    {
        $itemAttributes = $this->extractItemAttributes($item);

        $object = $this->buildApiModel($item->get('type'), $itemAttributes);

        if(! is_a($object, ApiModel::class))
        {
            throw new ApiClassBuilderException("Tried to build an object from a class that does not extend ApiModel");
        }

        $object->setRelations($this->extractRelationships($item));

        return $object;
    }

    /**
     * Builds an <code>ApiModel</code>.
     *
     * @param string $typeName
     * @param array  $itemAttributes
     * @return \App\ApiModel\ApiModel
     */
    protected function buildApiModel($typeName, array $itemAttributes)
    {
        if($this->typeHasFactory($typeName))
        {
            return $this->buildWithFactory($typeName, $itemAttributes);
        }

        if(TypeMapper::shouldBeMapped($typeName))
        {
            $className = TypeMapper::getMappedClass($typeName);
        } else
        {
            $className = $this->getDefaultModelsNamespace() . $typeName;
        }

        return new $className($itemAttributes);
    }

    /**
     * Gets the namespace where the <code>ApiModel</code>s exist.
     *
     * @return string
     */
    protected function getDefaultModelsNamespace()
    {
        return static::API_DEFAULT_MODEL_NAMESPACE;
    }

    /**
     * Checks if a type has its own factory.
     *
     * @param string $typeName
     * @return bool
     */
    protected function typeHasFactory($typeName)
    {
        return key_exists($typeName, $this->typeFactories);
    }

    /**
     * Builds an <code>ApiModel</code> with the provided type's
     * <code>TypeFactory</code>.
     *
     * @param string $typeName
     * @param array  $itemAttributes
     * @return \App\ApiModel\ApiModel
     */
    protected function buildWithFactory($typeName, array $itemAttributes)
    {
        /** @var TypeFactory $factory */
        $factory = new $this->typeFactories[$typeName]();

        return $factory->build($itemAttributes);
    }

    /**
     * Adds attributes to the relations of an <code>ApiModel</code>.
     *
     * @param \App\ApiModel\ApiModel $object
     * @param array                  $includes The <code>includes</code> section of
     *                                         the <code>Document</code>
     */
    protected function completeRelations(ApiModel $object, array $includes)
    {
        $relations = $object->getRelations();

        for($i = 0; $i < count($relations); $i++)
        {
            $relationClass = get_class($relations[$i]);

            foreach($includes as $include)
            {
                if(is_a($include,
                        $relationClass) && $include->id == $relations[$i]->id
                )
                {
                    $relations[$i] = $include;
                    break;
                }
            }
        }

        $object->setRelations($relations);
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

        if(! $item->has('relationships'))
        {
            return $relationships;
        }

        foreach($item->get('relationships')->asArray() as $relationshipsArray)
        {
            $relationshipIdentifiers = $relationshipsArray->get('data')->asArray();
            foreach($relationshipIdentifiers as $identifier)
            {
                $typeName = $identifier->get('type');

                if(TypeMapper::shouldBeMapped($typeName))
                {
                    $className = TypeMapper::getMappedClass($typeName);
                } else
                {
                    $className = $this->getDefaultModelsNamespace() . $typeName;
                }

                $properties = ['id' => $identifier->get('id')];

                $relationships[] = new $className($properties);
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