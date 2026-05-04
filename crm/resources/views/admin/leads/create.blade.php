@extends('layouts.admin')

@section('title', 'New Lead')
@section('subtitle', 'Add a business website that can be queued for audit.')

@section('content')
    <form method="post" action="{{ route('admin.leads.store') }}" class="panel">
        @include('admin.leads._form')
    </form>
@endsection

