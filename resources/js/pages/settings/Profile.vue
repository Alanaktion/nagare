<script setup lang="ts">
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem, SharedData, User } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Ref, ref } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

interface FormData extends Record<string, FormDataConvertible> {
    name?: string;
    email?: string;
    photo: string | null;
}
const form = useForm<FormData>({
    _method: 'PATCH',
    name: user.name,
    email: user.email,
    photo: null,
});

const photoPreview: Ref<string | null> = ref(null);
const photoInput: Ref<HTMLFormElement | null> = ref(null);

const selectNewPhoto = () => {
    if (!photoInput.value) return;
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    if (!photoInput.value) return;
    const photo = photoInput.value.files[0];

    if (!photo) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('profile-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};

const submit = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name/photo and email address" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <input id="photo" ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" />
                        <Label for="photo">Photo</Label>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div v-show="!photoPreview" class="mt-2">
                                <Avatar class="size-20 overflow-hidden rounded-2xl">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback class="rounded-lg text-black dark:text-white">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </div>
                            <div v-show="photoPreview" class="mt-2">
                                <span
                                    class="block size-20 rounded-2xl bg-cover bg-center bg-no-repeat"
                                    :style="'background-image: url(\'' + photoPreview + '\');'"
                                />
                            </div>

                            <Button variant="outline" class="mt-2 lg:ms-6" type="button" @click.prevent="selectNewPhoto">Select A New Photo</Button>

                            <Button v-if="user.avatar" variant="link" type="button" class="mt-2" @click.prevent="deletePhoto">Remove Photo</Button>
                        </div>

                        <InputError class="mt-2" :message="form.errors.photo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="text-muted-foreground -mt-4 text-sm">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-zinc-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-zinc-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <transition enter="transition ease-in-out" enter-from="opacity-0" leave="transition ease-in-out" leave-to="opacity-0">
                            <p v-show="form.recentlySuccessful" class="text-sm text-zinc-600">Saved.</p>
                        </transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
