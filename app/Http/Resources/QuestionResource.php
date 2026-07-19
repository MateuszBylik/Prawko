<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Question */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'points' => $this->points,
            'text' => $this->text,
            'explanation' => $this->explanation,
            'media' => [
                'type' => $this->media_type->value,
                'path' => $this->media_path,
            ],
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
