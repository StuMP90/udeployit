<div
    wire:poll.{{ $this->pollIntervalSeconds }}s.visible{{ $this->pollInBackground ? '.keep-alive' : '' }}="poll"
    class="sr-only"
    data-browser-notifications="{{ $this->browserNotificationsEnabled ? '1' : '0' }}"
>
    @script
        <script>
            if (
                $el.dataset.browserNotifications === '1'
                && typeof Notification !== 'undefined'
                && Notification.permission === 'default'
            ) {
                Notification.requestPermission();
            }

            $wire.on('browser-notify', ({ messages }) => {
                if (typeof Notification === 'undefined' || Notification.permission !== 'granted') {
                    return;
                }

                messages.forEach((message) => {
                    new Notification(@json(config('app.name', 'uDeployIt')), {
                        body: message,
                        icon: '/apple-touch-icon.png',
                    });
                });
            });
        </script>
    @endscript
</div>
