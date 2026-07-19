<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ExamMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartSessionRequest;
use App\Http\Resources\ExamSessionResource;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExamSessionController extends Controller
{
    /** Historia sesji zalogowanego użytkownika. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ExamSessionResource::collection(
            $request->user()->examSessions()->with('category')->latest()->paginate(15)
        );
    }

    /** Rozpoczęcie sesji nauki lub egzaminu — zwraca sesję z zestawem pytań. */
    public function store(StartSessionRequest $request, ExamService $exam): JsonResponse
    {
        $category = DrivingCategory::findOrFail($request->validated('driving_category_id'));
        $session = $exam->start($request->user(), $category, ExamMode::from($request->validated('mode')));

        return (new ExamSessionResource($session->load(['category', 'answers.question.answers'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, ExamSession $session): ExamSessionResource
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        return new ExamSessionResource(
            $session->load(['category', 'answers.question.answers'])
        );
    }
}
