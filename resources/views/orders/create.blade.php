@extends('layouts.admin')

@section('title', 'Nuevo pedido')
@section('heading', 'Nuevo pedido')
@section('subheading', 'Registra un pedido pendiente sin afectar existencias')

@section('content')
    @include('orders._form')
@endsection
