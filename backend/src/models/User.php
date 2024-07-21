<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Shipyard\Traits\HasRoles;

/**
 * @property string   $name
 * @property string   $email
 * @property string   $password
 * @property int|null $steamid
 * @property bool     $activated
 */
class User extends Model {
    use HasRoles;
    use HasRef;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that are casted.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activated' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'password', 'remember_token', 'activated', 'id', 'email', 'steamid', 'created_at', 'updated_at',
    ];

    /**
     * Retrieve ships by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ships() {
        return $this->retrieve_type(Ship::class);
    }

    /**
     * Retrieve saves by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function saves() {
        return $this->retrieve_type(Save::class);
    }

    /**
     * Retrieve modifications by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function modifications() {
        return $this->retrieve_type(Modification::class);
    }

    /**
     * Retrieve tagged items of a specific class.
     *
     * @param class-string $class
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function retrieve_type($class) {
        return $this->hasMany($class, 'user_id', 'id');
    }

    /** @return UserActivation */
    public function create_activation() {
        /** @var UserActivation $activation */
        $activation = UserActivation::query()->create([
            'email' => $this->email,
        ]);

        return $activation;
    }

    /** @return void */
    public function activate() {
        if ($this->activated) {
            return;
        }
        $activation = UserActivation::query()->where('email', $this->email);
        if ($activation !== null) {
            $activation->delete();
        }
        $this->activated = true;
        $this->save();
    }

    /** @return bool */
    public function active() {
        return $this->activated;
    }
}
