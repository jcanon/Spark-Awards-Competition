<?php

declare(strict_types=1);

namespace App\Controllers;

class CompetitionsController extends BaseController
{
    public function index()
    {
        $ctx = service('userctx')->current();
        $user = $ctx['user'] ?? null;

        if (!$user || !$user->isProfileCompleted()) {
            return redirect()->to('/profile');
        }

        $browse = service('compbrowse');
        $open = array_merge(
            $browse->getOpenPhase1ForCurrentUser(),
            $browse->getOpenPhase2ForCurrentUser()
        );

        return view('competitions/index', [
            'user' => $user,
            'open' => $open,
            'upcoming' => $browse->getUpcomingPhase1ForCurrentUser(),
        ]);
    }

    public function create(int $compId)
    {
        return redirect()->to('/submissions/create/' . $compId);
    }
}
