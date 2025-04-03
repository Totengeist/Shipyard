<?php

namespace Shipyard;

class SitemapGenerator {
    /**
     * Generate a sitemap.
     *
     * @return string
     */
    public static function generate() {
        $types = [
            'ship' => 'Shipyard\Models\Ship',
            'save' => 'Shipyard\Models\Save',
            'modification' => 'Shipyard\Models\Modification',
        ];
        $output = self::getHeader();
        foreach ($types as $type => $class) {
            /** @var \Illuminate\Database\Eloquent\Builder $items */
            $items = $class::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orderBy('updated_at', 'DESC');
            $items = $items->get();
            foreach ($items as $item) {
                /** @var \Shipyard\Models\Ship|\Shipyard\Models\Save|\Shipyard\Models\Modification $item */
                $output .= self::getItem(['url' => $_SERVER['BASE_URL_ABS'] . '/' . $type . '/' . $item->ref, 'modified' => date("Y-m-d\TH:i:sO", (int) strtotime($item->updated_at))]);
            }
        }

        return $output . self::getFooter();
    }

    /**
     * @param array<string, string> $data
     *
     * @return string
     */
    public static function getItem($data) {
        return <<<ITEM
        <loc>{$data['url']}</loc>
        <lastmod>{$data['modified']}</lastmod>
        ITEM;
    }

    /**
     * @return string
     */
    public static function getHeader() {
        return <<<HEADER
        <?xml version='1.0' encoding='UTF-8'?>
        <urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        HEADER;
    }

    /**
     * @return string
     */
    public static function getFooter() {
        return <<<FOOTER
        </urlset>
        FOOTER;
    }
}
