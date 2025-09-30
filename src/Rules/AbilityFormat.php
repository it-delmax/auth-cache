<?php

namespace ItDelmax\AuthCache\Rules;

use Illuminate\Contracts\Validation\Rule;

class AbilityFormat implements Rule
{
  protected $invalidTags = [];

  public function passes($attribute, $value)
  {
    if (!is_array($value)) {
      return false;
    }

    // Dodaj - (hyphen) u character class
    $this->invalidTags = collect($value)
      ->filter(fn($tag) => !preg_match('/^[a-z0-9_-]+:[a-z0-9_-]+$/i', $tag))
      ->toArray();

    return empty($this->invalidTags);
  }

  public function message()
  {
    return 'Neispravne dozvole: ' . implode(', ', $this->invalidTags) . '. Koristite format "scope:akcija" ili "scope:resurs-akcija".';
  }
}
