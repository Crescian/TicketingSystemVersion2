<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'ticket_id',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function humanSize(): string
    {
        $bytes = $this->size ?? 0;

        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1) . ' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 1) . ' KB',
            default => $bytes . ' B',
        };
    }
}
