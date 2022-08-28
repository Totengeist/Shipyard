<?php

namespace Shipyard;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    use HasRoles;
    use CreatesUniqueIDs;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that are casted.
     *
     * @var array
     */
    protected $casts = [
        'activated' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'activated', 'id',
    ];

    public static function create(array $attributes = []) {
        if (!isset($attributes['ref'])) {
            $attributes['ref'] = self::get_guid();
        }

        return static::query()->create($attributes);
    }

    public function save(array $options = []) {
        if (!isset($this->attributes['ref'])) {
            $this->setAttribute('ref', self::get_guid());
        }

        return parent::save($options);
    }

    public function create_activation() {
        return UserActivation::create([
            'email' => $this->email,
        ]);
    }

    public function activate() {
        if ($this->activated) {
            return;
        }
        $activation = UserActivation::where('email', $this->email);
        if ($activation !== null) {
            $activation->delete();
        }
        $this->activated = true;
        $this->save();
    }

    public function active() {
        return $this->activated;
    }
}
