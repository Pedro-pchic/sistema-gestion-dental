@extends('layouts.admin')

@section('title', 'Crear orden de compra')
@section('heading', 'Crear orden de compra')
@section('subheading', 'La orden se guardará como borrador')

@section('content')
    @include('purchases._form')
@endsection
