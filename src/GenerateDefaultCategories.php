<?php

namespace Camuthig\Jigsaw\DefaultCategories;

use Illuminate\Support\Collection as BaseCollection;
use TightenCo\Jigsaw\Collection\Collection;
use TightenCo\Jigsaw\Jigsaw;
use TightenCo\Jigsaw\Loaders\DataLoader;

class GenerateDefaultCategories
{
    public function handle(Jigsaw $jigsaw)
    {
        $posts = $jigsaw->getCollection('posts');

        if (!$posts) {
            return;
        }

        $definedCategories = $jigsaw->getCollection('categories') ?? collect();

        $defaultCategoryCollection = $this->getMissingCategoryPages($posts, $definedCategories);

        $this->reloadWithDefaultCategories($jigsaw, $defaultCategoryCollection);
    }

    private function getMissingCategoryPages(BaseCollection $posts, BaseCollection $definedCategories)
    {
        $items = $posts
            ->map(function ($p) {
                return $p->categories;
            })
            ->filter()
            ->flatten()
            ->unique()
            ->diff($definedCategories)
            ->map(function (string $category) {
                return [
                    'extends' => '_layouts.category',
                    'filename' => $category,
                    'title' => "Category: $category",
                ];
            });

        return [
            'path' => '/blog/categories/{filename}',
            'items' => $items,
            'posts' => function ($page, $allPosts) {
                return $allPosts->filter(function ($post) use ($page) {
                    return $post->categories ? in_array($page->getFilename(), $post->categories, true) : false;
                });
            },
        ];
    }

    private function reloadWithDefaultCategories(Jigsaw $jigsaw, array $defaultCategoryCollection)
    {
        /** @var DataLoader $dataLoader */
        $dataLoader = $jigsaw->app->get(DataLoader::class);
        $jigsaw->app->config['collections']['defaultCategories'] = $defaultCategoryCollection;

        $siteData = $dataLoader->loadSiteData($jigsaw->app->config);

        $jigsaw->remoteItemLoader->write($siteData->collections, $jigsaw->getSourcePath());
        $collectionData = $dataLoader->loadCollectionData($siteData, $jigsaw->getSourcePath());
        $jigsaw->getSiteData()->addCollectionData($collectionData);
    }
}