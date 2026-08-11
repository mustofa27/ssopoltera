<?php

namespace App\Http\Controllers;

use App\Models\DirectionBoard;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectionBoardController extends Controller
{
    public function edit(): View
    {
        $directionBoard = DirectionBoard::query()
            ->with([
                'director:id,name,email',
                'viceDirector1:id,name,email',
                'viceDirector2:id,name,email',
                'viceDirector3:id,name,email',
            ])
            ->first() ?? DirectionBoard::create();

        return view('direction-board.edit', compact('directionBoard'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'director_user_id' => ['nullable', 'exists:users,id'],
            'vice_director_1_user_id' => ['nullable', 'exists:users,id'],
            'vice_director_2_user_id' => ['nullable', 'exists:users,id'],
            'vice_director_3_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $assignedIds = array_filter($validated);

        if (count($assignedIds) !== count(array_unique($assignedIds))) {
            return back()->withInput()->withErrors([
                'director_user_id' => 'Each position must be assigned to a different person.',
            ]);
        }

        $directionBoard = DirectionBoard::query()->first() ?? new DirectionBoard();
        $directionBoard->fill($validated)->save();

        AuditLogger::log(
            event: 'organization.management',
            action: 'direction_board_updated',
            request: $request,
            targetType: DirectionBoard::class,
            targetId: $directionBoard->id,
            targetLabel: 'Direction Board'
        );

        return redirect()->route('direction-board.edit')->with('success', 'Direction board updated successfully.');
    }
}
