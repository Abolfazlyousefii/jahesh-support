@props([
    'name',
    'label',
    'icon',
    'active' => false,
])
<section
    class="sidebar-group"
    @mouseenter="if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) hoverMenu = @js($name)"
    @mouseleave="hoverMenu = hoverMenu === @js($name) ? null : hoverMenu"
>
    <button
        type="button"
        class="sidebar-item sidebar-parent {{ $active ? 'active' : '' }}"
        @click="toggle(@js($name))"
        :aria-expanded="isOpen(@js($name)).toString()"
    >
        <span class="sidebar-item-main">
            <x-icon :name="$icon" />
            <span class="sidebar-item-label">{{ $label }}</span>
        </span>

        <span class="sidebar-item-actions">
            @isset($badge){{ $badge }}@endisset
            <span class="sidebar-chevron" :class="{ 'open': isOpen(@js($name)) }">
                <x-icon name="chevron" />
            </span>
        </span>
    </button>

    <div
        x-cloak
        x-show="isOpen(@js($name))"
        x-transition.opacity.duration.150ms
        class="sidebar-submenu"
    >
        {{ $slot }}
    </div>
</section>
