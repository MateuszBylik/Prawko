<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExamSession */
class ExamSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mode' => $this->mode->value,
            'status' => $this->status->value,
            'total_questions' => $this->total_questions,
            'correct_count' => $this->correct_count,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'passed' => $this->passed,
            'time_limit_seconds' => $this->time_limit_seconds,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'questions' => QuestionResource::collection(
                $this->whenLoaded('answers', fn () => $this->answers->pluck('question'))
            ),
        ];
    }
}
