<script module lang="ts">
    import { edit } from '@/routes/notifications-settings';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Notification settings',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { update } from '@/actions/App/Http/Controllers/Settings/NotificationSettingsController';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';

    let { emailNotifications }: { emailNotifications: boolean } = $props();
</script>

<AppHead title="Notification settings" />

<h1 class="sr-only">Notification settings</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Notification settings"
        description="You're notified in the app, as it happens, when an issue you watch has a status change, a comment, a new description or a new assignee. You watch the issues you create, are assigned and comment on, and can watch or stop watching any issue from its page."
    />

    <Form {...update.form()} class="space-y-4" options={{ preserveScroll: true }}>
        {#snippet children({ errors, processing })}
            <input type="hidden" name="email_notifications" value="0" />
            <label class="flex items-start gap-3 text-sm">
                <input
                    type="checkbox"
                    name="email_notifications"
                    value="1"
                    checked={emailNotifications}
                    class="mt-0.5 size-4 accent-primary"
                />
                <span>
                    <span class="font-medium">Email me about these changes</span>
                    <span class="block text-muted-foreground">
                        An email is sent as soon as the change is made.
                    </span>
                </span>
            </label>
            <InputError message={errors.email_notifications} />
            <Button type="submit" disabled={processing}>Save</Button>
        {/snippet}
    </Form>
</div>
