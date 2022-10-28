<?php

namespace Camuthig\Jigsaw\DefaultCategories;

use TightenCo\Jigsaw\Jigsaw;
use TightenCo\Jigsaw\Loaders\CollectionRemoteItemLoader;
use TightenCo\Jigsaw\Loaders\DataLoader;

class GenerateDefaultCategories
{
    public function handle(Jigsaw $jigsaw)
    {
        $defaultCategoryCollection = $this->getDefaultCategoryPages($jigsaw);

        if (!$defaultCategoryCollection) {
            return;
        }

        $this->reloadWithDefaultCategories($jigsaw, $defaultCategoryCollection);
    }

    /**
     * @param Jigsaw $jigsaw
     *
     * @return array|null
     */
    private function getDefaultCategoryPages(Jigsaw $jigsaw)
    {
        $posts = $jigsaw->getCollection('posts') ?? collect();
        $definedCategories = $jigsaw->getCollection('categories') ?? collect();
        $defaultCategoryConfig = $jigsaw->getConfig('defaultCategories') ?? [];

        $items = $posts
            ->map(function ($p) {
                return $p->categories;
            })
            ->filter()
            ->flatten()
            ->unique()
            ->diff($definedCategories)
            ->map(function (string $category) use ($defaultCategoryConfig) {
                return [
                    'extends' => $defaultCategoryConfig['extends'] ?? '_layouts.category',
                    'filename' => $category,
                    'title' => "Category: $category",
                ];
            });

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'path' => $defaultCategoryConfig['path'] ?? '/blog/categories/{filename}',
            'items' => $items,
            'posts' => function ($page, $allPosts) {
                return $allPosts->filter(function ($post) use ($page) {
                    return $post->categories ? in_array($page->getFilename(), $post->categories, true) : false;
                });
            },
        ];
    }

    /**
     * @param Jigsaw $jigsaw
     * @param array $defaultCategoryCollection
     */
    private function reloadWithDefaultCategories(Jigsaw $jigsaw, array $defaultCategoryCollection)
    {
        /** @var DataLoader $dataLoader */
        $dataLoader = $jigsaw->app->get(DataLoader::class);
        $jigsaw->app->config['collections']['defaultCategories'] = $defaultCategoryCollection;

        $siteData = $dataLoader->loadSiteData($jigsaw->app->config);

        $remoteItemLoader = $jigsaw->app->get(CollectionRemoteItemLoader::class);
        $remoteItemLoader->write($siteData->collections, $jigsaw->getSourcePath());

        $collectionData = $dataLoader->loadCollectionData($siteData, $jigsaw->getSourcePath());
        $jigsaw->getSiteData()->addCollectionData($collectionData);
    }
}