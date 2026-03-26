<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SsoClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'redirect_uris',
        'is_active',
    ];

    protected $casts = [
        'redirect_uris' => 'array',
        'is_active' => 'boolean',
    ];

    public function authorizationCodes(): HasMany
    {
        return $this->hasMany(SsoAuthorizationCode::class);
    }

    public function setClientSecretAttribute(string $value): void
    {
        if (Str::startsWith($value, '$2y$')) {
            $this->attributes['client_secret'] = $value;

            return;
        }

        $this->attributes['client_secret'] = bcrypt($value);
    }

    public function canRedirectTo(string $redirectUri): bool
    {
        return in_array($redirectUri, $this->redirect_uris ?? [], true);
    }
}
