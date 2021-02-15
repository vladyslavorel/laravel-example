@extends('layouts.app')
@section('additionalStyles')
    <style>
        .home-content {
            padding: 32px 0;
            color: white;
            background-color: #313848;
        }
    </style>
@endsection
@section('content')
    <div class="home-content">
        <fuel></fuel>
    </div>
@endsection