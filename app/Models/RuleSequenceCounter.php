<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleSequenceCounter extends Model
{
    protected $fillable = ['rule_id', 'scope_key', 'last_sequence'];

    public function rule() { return $this->belongsTo(BusinessRule::class, 'rule_id'); }
}
