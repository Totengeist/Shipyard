<?php

namespace Shipyard;

use Cocur\Slugify\Slugify;
use Illuminate\Database\Eloquent\Model;
use Valitron\Validator;

class SlugModel extends Model {
    /**
     * Clean a slug if it is dirty. If no slug is
     * specified, use the name field by default.
     *
     * @return string
     */
    protected function cleanSlug() {
        if (!isset($this->slug) || $this->slug == '') {
            $this->slug = $this->label;
        }
        if (!$this->isDirty()) {
            return;
        }
        $this->slug = SlugModel::slugify((string) $this->slug);
    }

    /**
     * Force the slug to be lowercase and remove spaces.
     *
     * @return string
     */
    protected static function slugify(string $label) {
        $slug = (new Slugify())->slugify($label);

        return $slug;
    }

    /**
     * Get a validator for an incoming request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected static function slug_validator(array $data, Validator $v = null) {
        if ($v === null) {
            $v = new Validator($data);
        }

        $v->rules([
            'required' => [
                ['slug'],
                ['label']
            ],
            'slug' => [
                ['slug']
            ],
            'lengthMax' => [
                ['slug', 255],
                ['label', 255]
            ]
        ]);

        return $v;
    }

    /**
     * Override the save function to verify a name is present and to clean the
     * slug.
     *
     * @todo remove special characters from slugs
     */
    public function save(array $options = []) {
        $this->cleanSlug();
        parent::save($options);
    }
}
