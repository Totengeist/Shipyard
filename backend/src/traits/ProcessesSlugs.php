<?php

namespace Shipyard\Traits;

use Cocur\Slugify\Slugify;
use Valitron\Validator;

trait ProcessesSlugs {
    /**
     * Force the slug to be lowercase and remove spaces.
     *
     * @return string
     */
    public static function slugify(string $label) {
        $slug = (new Slugify())->slugify($label);

        return $slug;
    }

    /**
     * Create or add on to a validator.
     *
     * @return Validator
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
}
