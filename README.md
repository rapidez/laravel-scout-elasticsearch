# Laravel Scout ElasticSearch & Opensearch
  
  <img alt="Import progress report" src="https://raw.githubusercontent.com/rapidez/laravel-scout-elasticsearch/master/docs/demo.gif" >

  <p align="center">
    <a href="#"><img src="https://github.com/rapidez/laravel-scout-elasticsearch/actions/workflows/test-application.yaml/badge.svg" alt="Build Status"></img></a>
    <a href="https://packagist.org/packages/rapidez/laravel-scout-elasticsearch"><img src="https://poser.pugx.org/rapidez/laravel-scout-elasticsearch/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/rapidez/laravel-scout-elasticsearch"><img src="https://poser.pugx.org/rapidez/laravel-scout-elasticsearch/v/stable.svg" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/rapidez/laravel-scout-elasticsearch"><img src="https://poser.pugx.org/rapidez/laravel-scout-elasticsearch/license.svg" alt="License"></a>
  </p>
</p>

This package is based on the great work done by [Serhii Shliakhov](https://github.com/matchish) if you just need ElasticSearch support check out [his project](https://github.com/matchish/laravel-scout-elasticsearch) as this package's main focus is supporting Opensearch indexing via Scout in the same way.

The package provides the perfect starting point to integrate
ElasticSearch into your Laravel application. It is carefully crafted to simplify the usage
of ElasticSearch within the [Laravel Framework](https://laravel.com).

It’s built on top of the latest release of [Laravel Scout](https://laravel.com/docs/scout), the official Laravel search
package. Using this package, you are free to take advantage of all of Laravel Scout’s
great features, and at the same time leverage the complete set of ElasticSearch’s search experience.

## :two_hearts: Features  
Don't forget to :star: the package if you like it. :pray:

- Laravel Scout 10.x support
- Laravel Nova support
- [Search amongst multiple models](#search-amongst-multiple-models)
- [**Zero downtime** reimport](#zero-downtime-reimport) - it’s a breeze to import data in production.
- [Eager load relations](#eager-load) - speed up your import.
- Parallel import to make your import as fast as possible (in [alpha version](https://github.com/rapidez/laravel-scout-elasticsearch/releases/tag/8.0.0-alpha.1) for now)
- Import all searchable models at once.
- A fully configurable mapping for each model.
- Support for Elasticsearch and Opensearch.

## :rocket: Installation

Use composer to install the package:

```
composer require rapidez/laravel-scout-elasticsearch
```

Set env variables
```
SCOUT_DRIVER=Rapidez\ScoutElasticSearch\Engines\ElasticSearchEngine
```

The package uses `\ElasticSearch\Client` from official package, but does not try to configure it 
beyond connection configuration, so feel free do it in your app service provider. 
But if you don't want to do it right now, 
you can use `Rapidez\ElasticSearchServiceProvider` from the package.  
Register the provider, adding to `config/app.php`
```php
'providers' => [
    // Other Service Providers

    \Rapidez\ScoutElasticSearch\ElasticSearchServiceProvider::class
],
```
Set `ELASTICSEARCH_HOST` env variable
```
ELASTICSEARCH_HOST=host:port
```
or use commas as separator for additional nodes
```
ELASTICSEARCH_HOST=host:port,host:port
```

You can disable SSL verification by setting the following in your env
```
ELASTICSEARCH_SSL_VERIFICATION=false
```

And publish config example for elasticsearch  
`php artisan vendor:publish --tag config`

Basic OpenSearch support is provided. Add to your .env file OpenSearch as backend.

Default is ElasticSearch
```
SCOUT_SEARCH_BACKEND=opensearch
```

## :bulb: Usage

> **Note:** This package adds functionalities to [Laravel Scout](https://github.com/laravel/scout), and for this reason, we encourage you to **read the Scout documentation first**. Documentation for Scout can be found on the [Laravel website](https://laravel.com/docs/scout).

### Index [settings](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html#create-index-settings) and [mappings](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html#mappings)
It is very important to define the mapping when we create an index — an inappropriate preliminary definition and mapping may result in the wrong search results.

To define mappings or settings for index, set config with right value. 

For example if method `searchableAs` returns 
`products` string

Config key for mappings should be  
`elasticsearch.indices.mappings.products`  
Or you you can specify default mappings with config key 
`elasticsearch.indices.mappings.default`

Same way you can define settings

For index `products` it will be  
`elasticsearch.indices.settings.products`  

And for default settings  
`elasticsearch.indices.settings.default`

### Eager load
To speed up import you can eager load relations on import using global scopes.

You should configure `ImportSourceFactory` in your service provider(`register` method)
```php
use Rapidez\ScoutElasticSearch\Searchable\ImportSourceFactory;
...
public function register(): void
{
$this->app->bind(ImportSourceFactory::class, MyImportSourceFactory::class);
``` 
Here is an example of `MyImportSourceFactory`
```php
namespace Rapidez\ScoutElasticSearch\Searchable;

final class MyImportSourceFactory implements ImportSourceFactory
{
    public static function from(string $className): ImportSource
    {
        //Add all required scopes
        return new DefaultImportSource($className, [new WithCommentsScope()]);
    }
}

class WithCommentsScope implements Scope {

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->with('comments');
    }
}
```

You can also customize your indexed data when you save models by leveraging the [`toSearchableArray`](https://laravel.com/docs/9.x/scout#configuring-searchable-data) method
provided by Laravel Scout through the `Searchable` trait

#### Example:
```php
class Product extends Model 
{
    use Searchable;

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $with = [
            'categories',
        ];

        $this->loadMissing($with);

        return $this->toArray();
    }
}
```

This example will make sure the categories relationship gets always loaded on the model when 
saving it.
### Zero downtime reimport
While working in production, to keep your existing search experience available while reimporting your data, you also can use `scout:import` Artisan command:  

`php artisan scout:import`

The command create new temporary index, import all models to it, and then switch to the index and remove old index.

## :free: License
Scout ElasticSearch is an open-sourced software licensed under the [MIT license](LICENSE.md).
