<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractorDocument extends Model
{
    protected $fillable = ['contractor_id', 'document_type', 'original_name', 'file_path', 'uploaded_by'];

    public function contractor() { return $this->belongsTo(Contractor::class); }
    public function uploader()   { return $this->belongsTo(User::class, 'uploaded_by'); }
}
