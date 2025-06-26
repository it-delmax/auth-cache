<?php

namespace ItDelmax\AuthCache\Models\Traits;

use ItDelmax\AuthCache\Models\User;

trait RecordsUserActivity
{
  public static function bootRecordsUserActivity()
  {
    static::creating(function ($model) {
      if (auth()->check()) {
        $model->CREATED_BY = auth()->id();
      }
    });

    static::updating(function ($model) {
      if (auth()->check()) {
        $model->UPDATED_BY = auth()->id();
      }
    });
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'CREATED_BY', 'user_id');
  }

  public function updater()
  {
    return $this->belongsTo(User::class, 'UPDATED_BY', 'user_id');
  }
}
