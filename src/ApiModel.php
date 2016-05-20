<?php

namespace App\ApiModel;

use Illuminate\Database\Eloquent\Model;

abstract class ApiModel extends Model
{
    /**
     * Gets any relationships
     *
     * @param string $class
     * @return array
     */
    public function getRelations($class = null)
    {
        if (!is_null($class)) {
            return array_filter(parent::getRelations(),
                function ($relationship) use ($class) {
                    if (is_a($relationship, $class)) {
                        return true;
                    }

                    return false;
                });
        }

        return parent::getRelations();
    }

    /**
     * Gets a <code>Party</code> by ID.
     *
     * @param $id
     * @param $options array
     * @return \App\ApiModel\Party
     */
    public static function show($id, $options = [])
    {
        /* @var Document */
        $document = ApiParty::show($id, $options);

        $builder = new ApiModelFactory($document);
        $party = $builder->build();

        return $party;
    }

    /**
     * Gets multiple <code>Party</code>s by IDs in an <code>array</code>.
     *
     * @param array $ids
     * @param array $options
     * @return mixed
     */
    public static function showMany(array $ids, $options = [])
    {
        $document = ApiParty::showMany($ids, $options);

        $builder = new ApiModelFactory($document);
        $party = $builder->build();

        return $party;
    }

    /**
     * Gets multiple unsorted <code>Party</code>s.
     *
     * @param $options array
     * @return array
     */
    public static function all($options = [])
    {
        $result = ApiParty::all($options)->get('data')->asArray();

        foreach ($result as $key => $value) {
            $result[$key] = new Party(static::getAttributesArray($value));
        }

        return $result;
    }

    /**
     * Parses an item retrieved with the API and gets the resource's ID and
     * attributes.
     *
     * @param \Art4\JsonApiClient\Resource\Item $data
     * @return array
     */
    protected static function getAttributesArray(Item $data)
    {
        $attributes = array_merge(['id' => $data->get('id')],
            $data->get('attributes')->asArray());

        return $attributes;
    }
}
