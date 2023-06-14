<?php

namespace Shipyard\Models;

use Shipyard\Traits\HasRef;
use Shipyard\Traits\HasRoles;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 * @property bool   $activated
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
        'password', 'remember_token', 'activated', 'id',
    ];

    /** @return \Shipyard\Models\UserActivation */
    public function create_activation() {
        /** @var \Shipyard\Models\UserActivation $activation */
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
