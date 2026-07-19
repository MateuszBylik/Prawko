<?php

namespace App\Enums;

/**
 * Rola użytkownika w systemie. Fundament pod przyszły panel B2B szkół jazdy
 * oraz uprawnienia w REST API dla aplikacji mobilnej.
 */
enum UserRole: string
{
    case Student = 'student';
    case Instructor = 'instructor';
    case SchoolAdmin = 'school_admin';
    case Admin = 'admin';

    public function label(): string
    {
        return __('enums.role.'.$this->value);
    }

    /** Role z dostępem do panelu szkoły jazdy. */
    public function isSchoolStaff(): bool
    {
        return in_array($this, [self::Instructor, self::SchoolAdmin], true);
    }
}
