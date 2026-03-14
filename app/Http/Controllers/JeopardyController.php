<?php

namespace App\Http\Controllers;

use App\Models\JeopardyBoard;
use App\Models\JeopardySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class JeopardyController extends Controller
{
    // ─── Board listing ────────────────────────────────────────────────────────

    public function index()
    {
        $boards = JeopardyBoard::where('is_public', true)
            ->with('owner')
            ->latest()
            ->paginate(12);

        $myBoards = Auth::check()
            ? JeopardyBoard::where('user_id', Auth::id())->latest()->get()
            : collect();

        return view('games.jeopardy.index', compact('boards', 'myBoards'));
    }

    // ─── Board builder ────────────────────────────────────────────────────────

    public function create()
    {
        return view('games.jeopardy.builder');
    }

    public function edit(JeopardyBoard $board)
    {
        Gate::authorize('update', $board);
        return view('games.jeopardy.builder', compact('board'));
    }

    public function destroy(JeopardyBoard $board)
    {
        Gate::authorize('delete', $board);
        $board->delete();
        return redirect()->route('games.jeopardy.index')->with('success', 'Board deleted.');
    }

    // ─── Session hosting ──────────────────────────────────────────────────────

    public function hostCreate(JeopardyBoard $board)
    {
        Gate::authorize('view', $board);

        $session = JeopardySession::create([
            'board_id'         => $board->id,
            'host_id'          => Auth::id(),
            'code'             => JeopardySession::generateCode(),
            'status'           => 'lobby',
            'point_percentage' => 100,
        ]);

        // Pre-populate session_questions for every question on this board
        foreach ($board->categories as $category) {
            foreach ($category->questions as $question) {
                $session->sessionQuestions()->create([
                    'question_id' => $question->id,
                    'is_revealed' => false,
                    'zoom_level'  => 4,
                ]);
            }
        }

        return redirect()->route('games.jeopardy.host', $session->code);
    }

    public function host(string $code)
    {
        $session = JeopardySession::where('code', $code)->firstOrFail();
        Gate::authorize('host', $session);
        return view('games.jeopardy.host', compact('session'));
    }

    // ─── Player joining ───────────────────────────────────────────────────────

    public function joinShow(string $code)
    {
        $session = JeopardySession::where('code', $code)
            ->whereIn('status', ['lobby', 'active'])
            ->firstOrFail();

        return view('games.jeopardy.join', compact('session'));
    }

    public function joinStore(Request $request, string $code)
    {
        $session = JeopardySession::where('code', $code)
            ->whereIn('status', ['lobby', 'active'])
            ->firstOrFail();

        $data = $request->validate([
            'player_name' => 'required|string|max:40',
            'mode'        => 'required|in:solo,new_team,join_team',
            'team_name'   => 'nullable|string|max:40',
            'team_id'     => 'nullable|exists:jeopardy_teams,id',
        ]);

        $teamId = null;

        if ($data['mode'] === 'new_team') {
            $team   = $session->teams()->create(['name' => $data['team_name'] ?? $data['player_name']]);
            $teamId = $team->id;
        } elseif ($data['mode'] === 'join_team') {
            $teamId = $data['team_id'];
        }

        $player = $session->players()->create([
            'user_id' => Auth::id(),
            'name'    => $data['player_name'],
            'score'   => 0,
            'team_id' => $teamId,
        ]);

        // Store player ID in session so we can identify them later
        $request->session()->put("jeopardy_player_{$session->code}", $player->id);

        return redirect()->route('games.jeopardy.play', $session->code);
    }

    public function play(Request $request, string $code)
    {
        $session  = JeopardySession::where('code', $code)->firstOrFail();
        $playerId = $request->session()->get("jeopardy_player_{$session->code}");

        if (! $playerId) {
            return redirect()->route('games.jeopardy.join', $code);
        }

        return view('games.jeopardy.play', compact('session', 'playerId'));
    }
}
