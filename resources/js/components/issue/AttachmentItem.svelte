<script lang="ts">
    import File from '@lucide/svelte/icons/file';
    import { toast } from 'svelte-sonner';
    import { Button } from '@/components/ui/button';
    import { formatBytes } from '@/lib/bytes';
    import { timeAgo } from '@/lib/time';
    import type { Attachment } from '@/types';

    let {
        attachment,
        showUploader = true,
        ondelete,
    }: {
        attachment: Attachment;
        showUploader?: boolean;
        ondelete?: (attachment: Attachment) => void;
    } = $props();

    // A Markdown link can be pasted into a comment to point at the file.
    const copyLink = async () => {
        const link = `[${attachment.name}](${new URL(attachment.url, window.location.origin).href})`;

        try {
            await navigator.clipboard.writeText(link);
            toast('Link copied. Paste it into a comment.');
        } catch {
            toast.error('Could not copy the link.');
        }
    };
</script>

<div class="flex items-center gap-3 rounded-lg border p-2">
    <a
        href={attachment.url}
        target="_blank"
        rel="noopener"
        class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted focus-visible:ring-2 focus-visible:ring-ring"
        aria-label={attachment.is_image ? `View ${attachment.name}` : `Download ${attachment.name}`}
    >
        {#if attachment.thumbnail_url}
            <img src={attachment.thumbnail_url} alt="" class="size-full object-cover" loading="lazy" />
        {:else}
            <File class="size-6 text-muted-foreground" aria-hidden="true" />
        {/if}
    </a>
    <div class="min-w-0 flex-1">
        <a href={attachment.url} target="_blank" rel="noopener" class="block truncate text-sm font-medium hover:underline">
            {attachment.name}
        </a>
        <p class="truncate text-xs text-muted-foreground">
            {formatBytes(attachment.size)}
            {#if showUploader && attachment.user}· {attachment.user.name}{/if}
            · {timeAgo(attachment.created_at)}
        </p>
    </div>
    <div class="flex shrink-0 gap-1">
        <Button variant="ghost" size="sm" class="h-7" title="Copy a Markdown link to paste into a comment" onclick={copyLink}>
            Copy link
        </Button>
        {#if attachment.can_delete && ondelete}
            <Button variant="ghost" size="sm" class="h-7" onclick={() => ondelete(attachment)}>Delete</Button>
        {/if}
    </div>
</div>
