<?php

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;

class WpMenuService
{
    protected ConnectionInterface $db;

    public function __construct(ConnectionInterface &$db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Fetch a nested WP nav menu as an array tree.
     */
    public function getMenuTree(string $menuSlug): array
    {
        // 1) Get the term_taxonomy_id
        $tt = $this->db
            ->table('spark_term_taxonomy tt')
            ->select('tt.term_taxonomy_id')
            ->join('spark_terms t', 't.term_id = tt.term_id')
            ->where('tt.taxonomy', 'nav_menu')
            ->where('t.slug', $menuSlug)
            ->get()
            ->getRowArray();

        if (empty($tt)) {
            return [];
        }
        $ttId = (int)$tt['term_taxonomy_id'];

        // 2) Pull all nav_menu_item rows + parent/meta info
        $raw = $this->db
            ->table('spark_posts p')
            ->select([
                'p.ID',
                'COALESCE(CAST(pm_parent.meta_value AS UNSIGNED), 0) AS menu_parent_id',
                "NULLIF(p.post_title,'') AS explicit_label",
                'pm_url.meta_value       AS custom_url',
                'pm_obj.meta_value       AS object_id',
                'pm_obj_type.meta_value  AS object_type',
                'p.menu_order',
            ])
            ->join(
                'spark_term_relationships tr',
                "tr.object_id = p.ID AND tr.term_taxonomy_id = {$ttId}"
            )
            ->join(
                'spark_postmeta pm_parent',
                "pm_parent.post_id = p.ID AND pm_parent.meta_key = '_menu_item_menu_item_parent'",
                'left'
            )
            ->join(
                'spark_postmeta pm_url',
                "pm_url.post_id = p.ID    AND pm_url.meta_key = '_menu_item_url'",
                'left'
            )
            ->join(
                'spark_postmeta pm_obj',
                "pm_obj.post_id = p.ID    AND pm_obj.meta_key = '_menu_item_object_id'",
                'left'
            )
            ->join(
                'spark_postmeta pm_obj_type',
                "pm_obj_type.post_id = p.ID AND pm_obj_type.meta_key = '_menu_item_object'",
                'left'
            )
            ->where('p.post_type', 'nav_menu_item')
            ->where('p.post_status', 'publish')
            ->orderBy('p.menu_order', 'ASC')
            ->get()
            ->getResultArray();

        // 3) Normalize + build flat list
        $items = [];
        foreach ($raw as $r) {
            $id = (int)$r['ID'];
            $parent = (int)$r['menu_parent_id'];
            $objId = (int)$r['object_id'];
            $otype = $r['object_type'];
            $title = $r['explicit_label'] ?: $this->fallbackTitle($objId, $otype);
            if (!$title) {
                continue;
            }
            $url = $this->buildUrl($r['custom_url'], $otype, $objId);

            $items[$id] = [
                'id' => $id,
                'parent' => $parent,
                'object_id' => $objId,
                'object_type' => $otype,
                'title' => $title,
                'url' => $url,
                'children' => [],
            ];
        }

        // 4) Nest them into a tree
        $tree = [];
        foreach ($items as $id => &$it) {
            if ($it['parent'] && isset($items[$it['parent']])) {
                $items[$it['parent']]['children'][] = &$it;
            } else {
                $tree[] = &$it;
            }
        }
        unset($it);

        return $tree;
    }

    protected function fallbackTitle(int $objId, string $type): string
    {
        switch ($type) {
            case 'page':
            case 'post':
                $row = $this->db
                    ->table('spark_posts')
                    ->select('post_title')
                    ->where('ID', $objId)
                    ->get()
                    ->getRowArray();
                return $row['post_title'] ?? '';

            case 'category':
                $row = $this->db
                    ->table('spark_terms')
                    ->select('name')
                    ->where('term_id', $objId)
                    ->get()
                    ->getRowArray();
                return $row['name'] ?? '';

            default:
                return '';
        }
    }

    protected function buildUrl(?string $customUrl, string $type, int $id): string
    {
        if ($customUrl) {
            $url = $customUrl;
        } elseif (in_array($type, ['page', 'post'], true)) {
            $url = $this->buildPageUrl($id);
        } else {
            $url = '#';
        }

        // prefix domain if not absolute
        if (strpos($url, 'http') !== 0) {
            $url = 'https://www.sparkawards.com' . $url;
        }

        return $url;
    }

    protected function buildPageUrl(int $pageId): string
    {
        $row = $this->db
            ->table('spark_posts')
            ->select('post_name, post_parent')
            ->where('ID', $pageId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return '/';
        }

        if ((int)$row['post_parent']) {
            $parentPath = rtrim($this->buildPageUrl((int)$row['post_parent']), '/');
            return "{$parentPath}/{$row['post_name']}/";
        }

        return "/{$row['post_name']}/";
    }
}