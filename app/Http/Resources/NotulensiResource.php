<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\URL;
use Illuminate\Http\Resources\Json\JsonResource;

class NotulensiResource extends JsonResource
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
            'meeting_leader' => $this->meeting_leader,
            'meeting_agenda' => $this->meeting_agenda,
            'meeting_participants' => json_decode($this->meeting_participants),
            'meeting_date' => $this->meeting_date,
            'notulis' => $this->notulis,
            'thumbnail' => $this->thumbnail,
            'file_path' => $this->file_path ? URL::to($this->file_path) : null,
            'fpath' => [],
            'file_name' => $this->file_name,
            'status' => !!$this->status,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'additional_info' => $this->additional_info,
            'category_id' => $this->category_id,
            'created_at' => (new \DateTime($this->created_at))->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime($this->updated_at))->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at,
        ];
    }
}
