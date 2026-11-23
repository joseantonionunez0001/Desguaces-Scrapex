{{--
    Botón de borrado seguro: formulario POST con @method('DELETE'), token CSRF y confirmación previa.
    Nunca se borra con un enlace GET.
--}}
@props([
    'action',
    'message' => '¿Seguro que quieres eliminar este registro? Esta acción no se puede deshacer.',
    'label' => 'Eliminar',
    'iconOnly' => false,
])

<form method="POST" action="{{ $action }}" data-confirm="{{ $message }}" {{ $attributes->merge(['class' => 'inline']) }}>
    @csrf
    @method('DELETE')
    <x-ui.button type="submit" variant="danger" size="sm" icon="trash" :aria-label="$label" :title="$label">
        @unless ($iconOnly)
            {{ $label }}
        @endunless
    </x-ui.button>
</form>
