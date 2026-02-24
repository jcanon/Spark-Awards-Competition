<?php

declare(strict_types=1);

use App\Entities\Accounts\User;

if (!function_exists('view_settings')) {
    function view_settings(array $settings = [], ?string $siteTitle = null): array
    {
        $siteTitle = $siteTitle ?? 'Spark Awards';
        return [
            'url' => $settings['url'] ?? site_url('/'),
            'title' => $settings['title'] ?? $siteTitle,
        ];
    }
}

if (!function_exists('view_user')) {
    /**
     * Accepts a User entity (or null) and returns a minimal, normalized array for views.
     */
    function view_user(?User $user): array
    {
        if (!$user) {
            return [
                'id' => null,
                'email' => null,
                'is_admin' => false,
                'is_editor' => false,
                'is_judge' => false,
                'profile_completed' => false,
            ];
        }

        return [
            'id' => $user->user_id ?? null,
            'email' => $user->email_address ?? null,
            'is_admin' => method_exists($user, 'isAdmin') ? $user->isAdmin() : (($user->is_admin ?? 'No') === 'Yes'),
            'is_editor' => method_exists($user, 'isEditor') ? $user->isEditor() : (($user->is_editor ?? 'No') === 'Yes'),
            'is_judge' => method_exists($user, 'isJudge') ? $user->isJudge() : (($user->is_judge ?? 'No') === 'Yes'),
            'profile_completed' => method_exists($user, 'isProfileCompleted') ? $user->isProfileCompleted(
            ) : (($user->profile_completed ?? 'No') === 'Yes'),
        ];
    }
}