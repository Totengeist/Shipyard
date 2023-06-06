<?php

namespace Shipyard\Traits;

trait HasSlug {
    use ProcessesSlugs;

    /**
     * Clean a slug if it is dirty. If no slug is
     * specified, use the name field by default.
     */
    protected function cleanSlug() {
        if (!isset($this->slug) || $this->slug == '') {
            $this->slug = $this->label;
        }
        $this->slug = self::slugify((string) $this->slug);
    }

    /**
     * Override the save function to verify a name is present and to clean the
     * slug.
     *
     * @todo remove special characters from slugs
     */
    public function save(array $options = []) {
        $this->cleanSlug();

        return parent::save($options);
    }
}
