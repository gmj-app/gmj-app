@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'labelledBy' => null,
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    data-modal-root="{{ $name }}"
    x-data="{
        modalName: @js($name),
        show: @js($show),
        returnFocus: null,
        syncBodyLock() {
            document.body.classList.toggle('overflow-y-hidden', this.show);
        },
        closeModal() {
            const wasOpen = this.show;

            this.show = false;
            this.syncBodyLock();

            if (wasOpen) {
                this.$dispatch('modal-closed', { name: this.modalName });
            }
        },
        resetModal() {
            this.show = false;
            this.returnFocus = null;
            this.syncBodyLock();
        },
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="
        syncBodyLock();
        $watch('show', value => {
            syncBodyLock();

            if (value) {
                {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable()?.focus(), 100)' : '' }}
            } else {
                setTimeout(() => returnFocus?.focus(), 100);
            }
        });
    "
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? (returnFocus = document.activeElement, show = true) : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? closeModal() : null"
    x-on:reset-modals.window="resetModal()"
    x-on:close.stop="closeModal()"
    x-on:keydown.escape.window="show ? closeModal() : null"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="show"
    x-bind:hidden="! show"
    x-bind:aria-hidden="(! show).toString()"
    x-bind:class="show ? 'pointer-events-auto visible' : 'pointer-events-none invisible'"
    x-cloak
    hidden
    role="dialog"
    aria-modal="true"
    @if ($labelledBy)
        aria-labelledby="{{ $labelledBy }}"
    @endif
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 {{ $show ? 'pointer-events-auto visible' : 'pointer-events-none invisible' }}"
    style="display: {{ $show ? 'block' : 'none' }};"
>
    <div
        data-modal-backdrop="{{ $name }}"
        x-show="show"
        x-bind:hidden="! show"
        x-bind:aria-hidden="(! show).toString()"
        x-bind:class="{ 'pointer-events-none': ! show }"
        class="fixed inset-0 transform transition-all"
        x-on:click="closeModal()"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]"></div>
    </div>

    <div
        x-show="show"
        class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl transform transition-all dark:border-slate-700 dark:bg-slate-900 sm:w-full {{ $maxWidth }} sm:mx-auto"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        {{ $slot }}
    </div>
</div>
