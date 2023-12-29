@extends('layouts.master')


@section('css')
    <link href="{{ asset('assets/css/table-common.css') }}" rel="stylesheet" />
@endsection
@section('content')
    @livewire('admin.book-room.custom-room-booking', ['idRoom' => $id, 'bookingId' => $bookingId])
@endsection