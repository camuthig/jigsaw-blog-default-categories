# Jigsaw Blog Template Category Generator

An event listener to allow generating default category pages for the [Jigsaw Blog template](https://github.com/tightenco/jigsaw-blog-template)
when an explicit page is not configured.

## Install

```sh
composer require camuthig/jigsaw-blog-default-categories:dev-master@dev
```

## Setup

Add the listener to the `afterCollections` phase of your blog's `bootstrap.php`

```php
<?php

// bootstrap.php

$events->afterCollections(\Camuthig\Jigsaw\DefaultCategories\GenerateDefaultCategories::class);
```

## How it Works

The listener waits for the collections to be built, it then goes through the posts and categories collections,
determining which categories exist on the posts but do not have a file configured for them in the categories collection
already.

From there, it creates a new `collections` configuration, adds it to Jigsaw's configuration, and reloads the collection
data.

Reloading all of the collection data is likely overkill. However, after spending more time than I wanted trying to create
the appropriate data myself, I decided it was better to go this route.

Each of the default category pages is treated as a [remote collection](https://jigsaw.tighten.co/docs/collections-remote-collections/),
so an empty `source/_defaultCategories` directory will created after building the site.
