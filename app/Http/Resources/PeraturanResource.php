<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;

class PeraturanResource extends JsonResource
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
            'nomor' => $this->nomor,
            'nomor_peraturan' => $this->nomor_peraturan,
            'judul' => $this->judul,
            'slug' => $this->slug,
            'tahun' => $this->tahun,
            'tanggal_ditetapkan' => $this->tanggal_ditetapkan,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path ? URL::to($this->file_path) : null,
            'filePath' => [],
            'category_id' => $this->category_id,
            'status' => !!$this->status,
            'status_peraturan' => $this->status_peraturan,
            'created_at' => (new \DateTime($this->created_at))->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime($this->updated_at))->format('Y-m-d H:i:s'),
            'kategori' => new KategoriResource($this->whenLoaded('kategori')),
            'perubahan' => new PerubahanResource($this->whenLoaded('perubahan')),
            // 'konten' => ContentResource::collection($this->whenLoaded('contents')),
            // 'kategori' => new KategoriResource($this->kategori),
            // 'perubahan' => PerubahanResource::collection($this->whenLoaded('perubahan')),
        ];

    }
}
