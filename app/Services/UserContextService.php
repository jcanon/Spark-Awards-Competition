<?php

namespace App\Services;

use App\Models\Accounts\UserModel;
use App\Models\Entries\UserEntriesModel;
use App\Models\Accounts\UserPricingModel;
use App\Entities\Accounts\User;

/**
 * Provides the current request user's context (User entity, entries, pricing).
 * - current()/get() return an array with Entities inside for compatibility: ['user' => User|null, 'entries' => array<Entity>, 'pricing' => mixed]
 * - arrays() returns the same structure but coerced to arrays for legacy callers.
 */
class UserContextService
{
    /** @var array{user: ?User, entries: array<int, object>, pricing: mixed}|null */
    private ?array $context = null;

    public function get(): array
    {
        return $this->current();
    }

    public function current(): array
    {
        if ($this->context !== null) {
            return $this->context;
        }

        $uid = (string)(session('uid') ?? '');
        if ($uid === '') {
            return $this->context = ['user' => null, 'entries' => [], 'pricing' => null];
        }

        // User entity
        /** @var User|null $user */
        $user = (new UserModel())->find($uid);

        // Entries: prefer a model helper if present, else query by user_id
        $entriesModel = new UserEntriesModel();
        if (method_exists($entriesModel, 'forUser')) {
            $entries = $entriesModel->forUser($uid);
        } else {
            $entries = $entriesModel->where('user_id', $uid)->orderBy('date_created', 'DESC')->findAll();
        }

        // Pricing: requires user_type_id (nullable-safe)
        $userTypeId = $user ? (int)($user->user_type_id ?? 0) : 0;
        $pricing = $userTypeId > 0
            ? (new UserPricingModel())->forType($userTypeId)
            : null;

        return $this->context = ['user' => $user, 'entries' => $entries, 'pricing' => $pricing];
    }

    /**
     * Legacy-safe array version of the context (deep toArray where possible).
     */
    public function arrays(): array
    {
        $ctx = $this->current();

        $userArr = $this->toArray($ctx['user'] ?? null);
        $entriesArr = [];
        foreach (($ctx['entries'] ?? []) as $e) {
            $entriesArr[] = $this->toArray($e);
        }

        return [
            'user' => $userArr,
            'entries' => $entriesArr,
            'pricing' => is_object($ctx['pricing'] ?? null) && method_exists($ctx['pricing'], 'toArray')
                ? $ctx['pricing']->toArray()
                : ($ctx['pricing'] ?? null),
        ];
    }

    // --- helpers ---

    private function toArray($maybeEntity): ?array
    {
        if ($maybeEntity === null) {
            return null;
        }
        if (is_object($maybeEntity)) {
            if (method_exists($maybeEntity, 'toArray')) {
                return $maybeEntity->toArray();
            }
            // Fallback shallow cast
            return get_object_vars($maybeEntity);
        }
        return is_array($maybeEntity) ? $maybeEntity : null;
    }
}