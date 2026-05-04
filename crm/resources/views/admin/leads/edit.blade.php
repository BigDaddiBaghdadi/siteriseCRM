@extends('layouts.admin')

@section('title', 'Edit Lead')
@section('subtitle', $lead->business_name)

@section('content')
    <form method="post" action="{{ route('admin.leads.update', $lead) }}" class="panel">
        @method('put')
        @include('admin.leads._form')
    </form>
@endsection

