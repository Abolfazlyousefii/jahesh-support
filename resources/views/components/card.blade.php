@props(['title' => null, 'description' => null, 'padding' => true])
<section {{ $attributes->class('ui-card') }}>
    @if($title || $description || isset($action))
        <header class="ui-card-header">
            <div>
                @if($title)<h2>{{ $title }}</h2>@endif
                @if($description)<p>{{ $description }}</p>@endif
            </div>
            @isset($action)<div class="shrink-0">{{ $action }}</div>@endisset
        </header>
    @endif
    <div @class(['p-4 sm:p-5' => $padding])>{{ $slot }}</div>
</section>
