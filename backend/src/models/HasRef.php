<?php

namespace Shipyard;

trait HasRef {
    use CreatesUniqueIDs;

    /**
     * Get a validator for an incoming request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected static function ref_validator(array $data, Validator $v = null) {
        if ($v === null) {
            $v = new Validator($data);
        }

        $v->rules([
            'required' => [
                ['ref']
            ],
            'slug' => [
                ['ref']
            ],
            'lengthMax' => [
                ['ref', 255]
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
        if (!isset($this->attributes['ref'])) {
            $this->setAttribute('ref', self::get_guid());
        }

        return parent::save($options);
    }
}
