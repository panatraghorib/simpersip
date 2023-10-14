<?php

namespace App\Http\Resources;

use App\Http\Resources\Peraturan\PeraturanSearchResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchesResource extends JsonResource
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
            'peraturan_id' => $this->peraturan_id,
            'isi_naskah' => $this->konten,
            'pengelompokan_isi' => $this->kelompok,
            'order_by_pengelompokan' => $this->urutan_kelompok,
            'order_by_naskah' => $this->order,
            'parent_peraturan' => new PeraturanSearchResource($this->whenLoaded('peraturan'))
        ];
    }
}
