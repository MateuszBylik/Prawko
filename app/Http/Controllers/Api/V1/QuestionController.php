<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuestionController extends Controller
{
    /** Lista pytań z filtrami (kategoria, typ) i stronicowaniem. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Question::query()->active()->with(['answers', 'categories']);

        if ($categoryId = $request->integer('category_id')) {
            $query->forCategory($categoryId);
        }

        $type = QuestionType::tryFrom((string) $request->query('type'));
        if ($type !== null) {
            $query->ofType($type);
        }

        return QuestionResource::collection(
            $query->orderBy('id')->paginate(20)->withQueryString()
        );
    }
}
