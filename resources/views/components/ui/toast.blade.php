<div
    x-data="{
        toasts: [],
        push(text, variant) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, text, variant });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id) }, 4000);
        },
    }"
    x-on:notify.window="push($event.detail.text, $event.detail.variant ?? 'success')"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 sm:items-end sm:pe-4"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition
            class="pointer-events-auto w-full max-w-sm rounded-lg px-4 py-3 text-sm font-medium shadow-lg ring-1 ring-black/5"
            :class="toast.variant === 'danger'
                ? 'bg-red-600 text-white'
                : 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'"
            x-text="toast.text"
        ></div>
    </template>
</div>
