@extends('layouts.admin')

@section('title', 'Editar cotización')
@section('heading', 'Editar borrador')
@section('subheading', $quote->number)

@section('content')
    @include('quotes._form')
@endsection
