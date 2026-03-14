@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    @livewire('jeopardy.board-builder', ['board' => $board ?? null])
</div>
@endsection
