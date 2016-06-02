# JsonApiModel
Extension to Art4's JsonApiClient https://github.com/Art4/json-api-client

Builds models from a JSON API v1.0-compliant response from JsonApiClient.

## Setup
### Classes
Create classes that extend `ApiModel` that will be represent the remote resources. For instance, if a resource is called "article", make an `Article` class.

You can also use `ApiModelInterface` to make your own `ApiModel` variants.

### Type Factories
You can add your own factories for specific `type`s.

This is useful, for instance, when you want to implement inheritance in your class structure. Let's say you have a `CreditCard` class and a `DebitCard` class that both extend the `Card` class, but the API returns the same type with different attributes.
```
{
   "id": 1
   "type": "card",
   "attributes": {
        "cardtype": "credit"
   }
}

..

{
   "id": 2
   "type": "card",
   "attributes": {
        "cardtype": "debit"
   }
}
```

You can then extend `OskarD\JsonApiModel\Factories\TypeFactory` and make your own `CardFactory` class.
```
namespace MyApp\TypeFactories;

use OskarD\JsonApiModel\Factories\TypeFactory;

class CardFactory
{
    /**
     * Builds an instance of the type.
     *
     * @param $attributes
     * @return ApiModel
     */
    public function build($attributes)
    {
        if($attributes['cardtype'] == 'credit') {
            return new CreditCard($attributes);
        }
        
        return new DebitCard($attributes);
    }
}
```
You also have to set the factory in the ApiModelFactory context.
```
$apiModelFactory->setTypeFactories([
    'card' => MyApp\TypeFactories\CardFactory::class,
]);
```
## Usage
### Create The Factory
Create the ApiModelFactory by passing the document returned from JsonApiClient's `parse` function (`$document`).
```
use Art4\JsonApiClient\Utils\Helper;
use OskarD\JsonApiModel\Factories\ApiModelFactory;

.. 

$document = Helper::parse($resource);
$apiModelFactory = new ApiModelFactory($document);
```

### Namespacing (Optional)
If your `ApiModelInterface` classes exist outside the current namespace, you have to need to provide them. You can do this in three  ways: by setting the default namespace, by mapping them individually, or by mixing both of them at once.

#### Set Default Namespace
If most or all of your `ApiModelInterface` classes exist in the same namespace, you can avoid mapping them by setting the default namespace for the factory to theirs.
```
$apiModelFactory->setDefaultNamespace('MyApp\\ApiModels\\');
```

#### Map types
If one or more of your `ApiModelInterface` classes exist outside the default namespace, you can map them individually.
```
$apiModelFactory->setMappedTypes([
    'articles' => Article::class,
    'people'   => Person::class,
    'comments' => Comment::class,
]);
```

### Building
Building the `Document` will result in an array containing any resources provided.
```
$result = $apiModelFactory->build();
```
