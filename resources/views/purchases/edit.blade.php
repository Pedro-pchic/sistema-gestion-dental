@extends('layouts.admin')

@section('title', 'Editar borrador')
@section('heading', 'Editar borrador')
@section('subheading', $purchase->number)

@section('content')
    @include('purchases._form')
@endsection
