import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
    }
}

type ChannelAuthorization = {
    auth: string;
    channel_data?: string;
    shared_secret?: string;
};

type AuthorizationCallback = (
    error: Error | null,
    data: ChannelAuthorization | null,
) => void;

let echo: Echo<'reverb'> | null = null;

const xsrfToken = () =>
    decodeURIComponent(
        document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
    );

/**
 * Whether realtime updates are set up. Without a Reverb app key the app
 * works as usual, but changes made by other people show up on the next
 * page load instead of live.
 */
export const isRealtimeEnabled = Boolean(import.meta.env.VITE_REVERB_APP_KEY);

/**
 * The shared Echo connection, created on first use, or null when realtime
 * isn't configured.
 *
 * Channel authorization reads the XSRF cookie on every request, so it
 * keeps working after the session token is regenerated.
 */
export function getEcho(): Echo<'reverb'> | null {
    if (!isRealtimeEnabled) {
        return null;
    }

    if (echo) {
        return echo;
    }

    window.Pusher = Pusher;

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY as string,
        wsHost: import.meta.env.VITE_REVERB_HOST as string,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel: { name: string }) => ({
            authorize: (socketId: string, callback: AuthorizationCallback) => {
                fetch('/broadcasting/auth', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': xsrfToken(),
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name,
                    }),
                })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(
                                `Channel authorization failed (${response.status})`,
                            );
                        }
                        return response.json();
                    })
                    .then((data: ChannelAuthorization) => callback(null, data))
                    .catch((error: Error) => callback(error, null));
            },
        }),
    });

    return echo;
}

/**
 * The current connection's socket id, if connected. Sent with requests so
 * the server doesn't broadcast a change back to the tab that made it.
 */
export function currentSocketId(): string | undefined {
    return echo?.socketId();
}
