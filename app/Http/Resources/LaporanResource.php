<?php

namespace App\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'prologue' => $this->prologue,
            'thumbnail' => $this->thumbnail,
            'date' => $this->date,
            'year' => json_decode($this->year),
            'periode' => Str::lower($this->periode),
            'desc' => $this->desc,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path ? URL::to($this->file_path) : null,
            'fpath' => [],
            'status' => !!$this->status,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'category_id' => $this->category_id,
            'created_at' => (new \DateTime($this->created_at))->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime($this->updated_at))->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at,
        ];

    }
}
