@extends('layouts.app')

@section('content')
    @if($session->status === 'lobby')
        @livewire('jeopardy.game-lobby', ['code' => $session->code])
    @else
        @livewire('jeopardy.game-player', ['code' => $session->code, 'playerId' => $playerId])
    @endif
@endsection
