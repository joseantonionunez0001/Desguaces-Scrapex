{{--
    Formulario reutilizable de vehículo (crear y editar).
    Variables: $vehiculo, $desguaces (id => nombre), $estados (valores existentes), $action, $method ('POST' | 'PUT'), $submit, $cancel.
--}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.select name="id_desguace" label="Desguace" :options="$desguaces" :value="$vehiculo->id_desguace"
            placeholder="Selecciona un desguace" required class="sm:col-span-2" />

        <x-form.input name="matricula" label="Matrícula" :value="$vehiculo->matricula" maxlength="20" required
            autocomplete="off" />

        <x-form.input name="numero_bastidor" label="Número de bastidor" :value="$vehiculo->numero_bastidor"
            maxlength="17" required autocomplete="off" hint="17 caracteres exactos (VIN)." />

        <x-form.input name="marca" label="Marca" :value="$vehiculo->marca" maxlength="255" required />

        <x-form.input name="modelo" label="Modelo" :value="$vehiculo->modelo" maxlength="255" required />

        <x-form.input name="fecha_llegada" label="Fecha de llegada" type="date"
            :value="$vehiculo->fecha_llegada?->format('Y-m-d')" required />

        <x-form.input name="estado_tratamiento" label="Estado de tratamiento" :value="$vehiculo->estado_tratamiento"
            maxlength="100" required list="estados-tratamiento" autocomplete="off"
            hint="Escribe uno nuevo o elige uno de los ya utilizados." />
    </div>

    <datalist id="estados-tratamiento">
        @foreach ($estados as $estado)
            <option value="{{ $estado }}"></option>
        @endforeach
    </datalist>

    <x-form.actions :submit="$submit" :cancel="$cancel" />
</form>
