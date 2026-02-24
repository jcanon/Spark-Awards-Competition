<?php

if (!function_exists('render_fusion_menu')) {
    /**
     * Recursively render a multi-level Fusion menu.
     *
     * @param array $items The tree from WpMenuService::getMenuTree()
     * @param int $depth Current depth (start at 0)
     * @return string
     */
    function render_fusion_menu(array $items, int $depth = 0): string
    {
        $html = '';
        foreach ($items as $item) {
            $hasKids = !empty($item['children']);
            $classes = [
                'menu-item',
                'menu-item-type-post_type',
                "menu-item-object-{$item['object_type']}",
                "menu-item-{$item['id']}",
            ];

            if ($hasKids) {
                $classes[] = 'menu-item-has-children';
                $classes[] = $depth === 0
                    ? 'fusion-dropdown-menu'
                    : 'fusion-dropdown-submenu';
            }

            $cls = implode(' ', $classes);
            $data = $depth === 0 ? " data-item-id=\"{$item['id']}\"" : '';

            $html .= "<li id=\"menu-item-{$item['id']}\" class=\"{$cls}\"{$data}>";
            $html .= '<a href="' . esc($item['url']) . '"'
                . ' class="fusion-textcolor-highlight">';
            $html .= $depth === 0
                ? '<span class="menu-text">' . esc($item['title']) . '</span>'
                : '<span>' . esc($item['title']) . '</span>';

            if ($hasKids) {
                $html .= '<span class="fusion-caret">'
                    . '<i class="fusion-dropdown-indicator"></i>'
                    . '</span>';
            }

            $html .= '</a>';

            if ($hasKids) {
                $html .= '<ul role="menu" class="sub-menu">'
                    . render_fusion_menu($item['children'], $depth + 1)
                    . '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }
}