<?php

namespace ItDelmax\AuthCache\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'etg_utf8';
    protected $table = 'PERSONAL_ACCESS_TOKENS';
    protected $primaryKey = 'id';
}
