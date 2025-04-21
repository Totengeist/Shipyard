<?php

namespace Shipyard;

use League\CommonMark\CommonMarkConverter;

class AtomGenerator {
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
            $items = $class::with('user', 'primary_screenshot', 'tags')->whereRaw('(flags & 1 <> 1 AND flags & 2 <> 2)')->orderBy('updated_at', 'DESC')->take(10);
            $items = $items->get();
            foreach ($items as $item) {
                /** @var Models\Ship|Models\Save|Models\Modification $item */
                $description = $item->description;
                if ($item->primary_screenshot->first() != null) {
                    $description = "![Screenshot]({$_SERVER['BASE_URL_ABS']}/api/v1/screenshot/{$item->primary_screenshot[0]->ref}/preview/800)\n\n" . $description;
                }
                $markdown = new CommonMarkConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]);
                $output .= self::getItem([
                    'type' => ucfirst($type),
                    'title' => $item->title,
                    'author' => $item->user->name,
                    'description' => htmlspecialchars($markdown->convert($description), ENT_XML1 | ENT_COMPAT | ENT_QUOTES, 'UTF-8'),
                    'url' => $_SERVER['BASE_URL_ABS'] . '/' . $type . '/' . $item->ref,
                    'id' => $type . '/' . $item->ref,
                    'modified' => date('c', (int) strtotime($item->updated_at)),
                    'published' => date('c', (int) strtotime($item->created_at))
                ]);
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

        <entry>
            <title>{$data['type']}: {$data['title']} by {$data['author']}</title>
            <author>
                <name>{$data['author']}</name>
            </author>
            <link href="{$data['url']}"/>
            <id>{$data['url']}</id>
            <updated>{$data['modified']}</updated>
            <published>{$data['published']}</published>
            <content>{$data['description']}</content>
        </entry>
        ITEM;
    }

    /**
     * @return string
     */
    public static function getHeader() {
        return <<<HEADER
        <?xml version="1.0" encoding="utf-8"?>
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>New Items - {$_SERVER['APP_TITLE']}</title>
            <link href="{$_SERVER['BASE_URL_ABS']}"/>
            <link rel="self" href="{$_SERVER['BASE_URL_ABS']}/feed" />
            <updated>2003-12-13T18:30:02Z</updated>
            <id>{$_SERVER['BASE_URL_ABS']}</id>
        HEADER;
    }

    /**
     * @return string
     */
    public static function getFooter() {
        return <<<FOOTER

        </feed>
        FOOTER;
    }
}
