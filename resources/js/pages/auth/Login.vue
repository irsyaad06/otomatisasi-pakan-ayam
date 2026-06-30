<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
/* @chisel-registration */
import { register } from '@/routes';
/* @end-chisel-registration */
import { store } from '@/routes/login';
import { request } from '@/routes/password';
/* @chisel-passkeys */
import PasskeyVerify from '@/components/PasskeyVerify.vue';
/* @end-chisel-passkeys */

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <!-- @chisel-passkeys -->
    <PasskeyVerify />
    <!-- @end-chisel-passkeys -->

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-5">
            <div class="grid gap-1.5">
                <Label for="email" class="text-xs font-bold text-slate-500 dark:text-slate-400">Alamat Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    class="rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900"
                    placeholder="nama@email.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-xs font-bold text-slate-500 dark:text-slate-400">Kata Sandi</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                        :tabindex="5"
                    >
                        Lupa kata sandi?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    class="rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-800 dark:bg-slate-900"
                    placeholder="Masukkan Kata Sandi"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3 cursor-pointer">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Ingat saya di perangkat ini</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-3 w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 text-white font-extrabold rounded-xl py-2.5 shadow-lg shadow-indigo-600/15 hover:shadow-indigo-600/25 transition-all hover:scale-[1.01] cursor-pointer disabled:opacity-50"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2" />
                Masuk ke Dashboard
            </Button>
        </div>

        <!-- @chisel-registration -->
        <div class="text-center text-xs text-slate-500 dark:text-slate-400">
            Belum memiliki akun?
            <TextLink :href="register()" class="font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 ml-1" :tabindex="5">Daftar sekarang</TextLink>
        </div>
        <!-- @end-chisel-registration -->
    </Form>
</template>
