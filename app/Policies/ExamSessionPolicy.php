<?php

namespace App\Policies;

use App\Models\ExamSession;
use App\Models\User;

/**
 * Sesja należy wyłącznie do swojego użytkownika. Fundament autoryzacji także
 * pod przyszły panel B2B (instruktor będzie mógł widzieć sesje swoich kursantów).
 */
class ExamSessionPolicy
{
    public function view(User $user, ExamSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function update(User $user, ExamSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
