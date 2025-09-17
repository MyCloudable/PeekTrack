@extends('layouts.app')
@section('content')
  <div id="app">
    <profile-settings :authuser='@json(auth()->user())'></profile-settings>
  </div>
@endsection
