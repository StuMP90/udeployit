<div
    wire:poll.{{ $this->pollIntervalSeconds }}s{{ $this->pollInBackground ? '.keep-alive' : '' }}="poll"
    class="hidden"
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
