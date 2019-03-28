<?php

namespace Camuthig\Jigsaw\DefaultCategories;

use TightenCo\Jigsaw\Collection\Collection;
use TightenCo\Jigsaw\Jigsaw;
use TightenCo\Jigsaw\Loaders\DataLoader;

class GenerateCategories
{
    public function handle(Jigsaw $jigsaw)
    {
        $defaultCategoryCollection = $this->getMissingCategoryPages($jigsaw);

        $this->reloadWithDefaultCategories($jigsaw, $defaultCategoryCollection);
    }

    private function getMissingCategoryPages(Jigsaw $jigsaw)
    {
        /** @var Collection $posts */
        $posts = $jigsaw->getCollection('posts');
        /** @var Collection $categories */
        $categories = $jigsaw->getCollection('categories');

        $items = $posts
            ->map(function ($p) {
                return $p->categories;
            })
            ->flatten()
            ->unique()
            ->diff($categories->keys())
            ->map(function (string $category) use ($categories, $posts) {
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