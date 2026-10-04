<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { destroy, store } from '@/routes/profile-photo';

    const user = $derived(page.props.auth.user);

    let fileInput: HTMLInputElement | undefined = $state();
    let processing = $state(false);
    let error = $state<string>();

    const upload = (event: Event) => {
        const input = event.currentTarget as HTMLInputElement;
        const photo = input.files?.[0];
        if (!photo) {
            return;
        }

        error = undefined;
        router.post(
            store.url(),
            { photo },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => (processing = true),
                onError: (errors) => (error = errors.photo),
                onFinish: () => {
                    processing = false;
                    input.value = '';
                },
            },
        );
    };

    const remove = () => {
        error = undefined;
        router.delete(destroy.url(), {
            preserveScroll: true,
            onStart: () => (processing = true),
            onFinish: () => (processing = false),
        });
    };
</script>

<div class="space-y-4">
    <Heading
        variant="small"
        title="Profile photo"
        description="Shown next to your name on boards and issues. Images are cropped to a square."
    />

    <div class="flex items-center gap-4">
        <UserAvatar user={{ name: user.name, avatar_url: user.avatar ?? null }} class="size-16 text-xl" />

        <div class="flex flex-wrap gap-2">
            <input
                bind:this={fileInput}
                type="file"
                accept="image/jpeg,image/png,image/gif,image/webp"
                class="sr-only"
                aria-label="Choose a profile photo"
                onchange={upload}
            />
            <Button type="button" variant="outline" disabled={processing} onclick={() => fileInput?.click()}>
                {user.avatar ? 'Change photo' : 'Upload photo'}
            </Button>
            {#if user.avatar}
                <Button type="button" variant="ghost" disabled={processing} onclick={remove}>Remove</Button>
            {/if}
        </div>
    </div>
    <InputError message={error} />
</div>
