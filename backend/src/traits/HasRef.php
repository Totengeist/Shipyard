<?php

namespace Shipyard\Traits;

use Valitron\Validator;

/**
 * @property string $ref
 *
 * @method \Shipyard\Models\Model|\Illuminate\Database\Eloquent\Builder|static whereRef(string $ref)
 */
trait HasRef {
    use CreatesUniqueIDs;

    /**
     * Create or add on to a validator.
     *
     * @param mixed[]        $data
     * @param Validator|null $v
     *
     * @return Validator
     */
    protected static function ref_validator($data, $v = null) {
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
     *
     * @param mixed[] $options
     *
     * @return bool
     */
    public function save(array $options = []) {
        if (!isset($this->attributes['ref'])) {
            do {
                $ref = self::get_guid(9);
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = static::where('ref', $ref);
            } while ($query->get()->count() != 0);
            $this->setAttribute('ref', $ref);
        }

        return parent::save($options);
    }
}
