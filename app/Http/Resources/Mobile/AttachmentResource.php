<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// A ticket or chat attachment. `url` needs the same Bearer token as every other
// mobile call; add ?download=1 to get it as a download instead of inline.
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_label' => $this->humanSize(),
            'url' => route('mobile.attachments.show', $this->resource),
        ];
    }
}
