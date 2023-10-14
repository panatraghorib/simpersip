<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PerubahanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'set' => true,
            'revoking_id' => $this->revoking_id,
            'revoked_id' => $this->revoked_id,
            'revoking_number' => $this->revoking_number,
            'revoked_number' => $this->revoked_number,
            'revoked_contents' => $this->revoked_contents,
            'step' => $this->step,
            'revoking_type' => $this->revoking_type == 1 ? true : false,
            'notes' => $this->notes,
        ];
    }
}
