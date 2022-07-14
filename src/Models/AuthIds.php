<?php

namespace Fitness\MSCommon\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AuthIds
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider_id
 * @property string $issuer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds query()
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds whereIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthIds whereUserId($value)
 * @mixin \Eloquent
 */
class AuthIds extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
