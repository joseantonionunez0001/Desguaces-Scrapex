@extends('layouts.app')

@section('title', 'Almacén interior')

@section('content')
    <x-ui.page-header
        title="Almacén interior"
        subtitle="Estructura del almacén cubierto: desguace, módulos, pasillos, estanterías y etiquetas."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Almacén interior']]"
    />

    @include('almacen.partials.jerarquia', ['zona' => 'interior'])
@endsection
