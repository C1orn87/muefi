@extends('layouts.app')

@section('content')
    {{-- Lobby view until host starts the game --}}
    @if($session->status === 'lobby')
        @livewire('jeopardy.game-lobby', ['code' => $session->code])
    @else
        @livewire('jeopardy.game-host', ['code' => $session->code])
    @endif
@endsection
