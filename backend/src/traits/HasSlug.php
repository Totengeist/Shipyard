<?php

namespace Shipyard\Traits;

/**
 * @property string $label
 * @property string $slug
 *
 * @method \Shipyard\Models\Model|\Illuminate\Database\Eloquent\Builder|static whereSlug(string $slug)
 */
trait HasSlug {
    use ProcessesSlugs;

    /**
     * Clean a slug if it is dirty. If no slug is
     * specified, use the name field by default.
     *
     * @return void
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
     *
     * @param mixed[] $options
     *
     * return bool
     */
    public function save(array $options = []) {
        $this->cleanSlug();

        return parent::save($options);
    }
}
