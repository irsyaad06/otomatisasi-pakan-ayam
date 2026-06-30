<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const appName = page.props.name || 'Kandang Ayam IoT';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-slate-50 dark:bg-slate-950 p-4 sm:p-6 overflow-hidden">
        <!-- Floating Ambient Glows -->
        <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-gradient-to-br from-indigo-500/20 to-purple-500/20 opacity-40 blur-3xl dark:from-indigo-600/10 dark:to-purple-600/10"></div>
        <div class="absolute -right-20 -bottom-20 h-80 w-80 rounded-full bg-gradient-to-tr from-emerald-500/20 to-teal-500/20 opacity-40 blur-3xl dark:from-emerald-600/10 dark:to-teal-600/10"></div>
        <div class="absolute left-1/3 top-1/2 -translate-y-1/2 h-96 w-96 rounded-full bg-indigo-400/5 opacity-30 blur-3xl dark:bg-indigo-900/5"></div>

        <div class="relative w-full max-w-[420px] flex flex-col gap-6 z-10">
            <!-- App Logo & Branding Header -->
            <div class="flex flex-col items-center text-center">
                <Link
                    :href="home()"
                    class="group flex flex-col items-center gap-3 transition-transform hover:scale-[1.02]"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-650 to-indigo-500 p-2.5 shadow-lg shadow-indigo-650/20 dark:shadow-indigo-950/30 text-white transform group-hover:rotate-6 transition-all duration-300">
                        <AppLogoIcon class="h-7 w-7 fill-current" />
                    </div>
                    <div class="space-y-1">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">
                            Smart Kandang IoT
                        </h2>
                        <h1 class="text-xl font-black tracking-tight text-slate-800 dark:text-slate-100">
                            {{ appName }}
                        </h1>
                    </div>
                </Link>
            </div>

            <!-- Login Glassmorphism Card -->
            <div class="rounded-3xl border border-white/50 bg-white/80 p-7 sm:p-9 shadow-2xl shadow-slate-200/50 dark:border-slate-800/40 dark:bg-slate-900/70 dark:shadow-black/40 backdrop-blur-xl">
                <!-- Inner Title -->
                <div class="mb-6 text-center">
                    <h3 class="text-md font-extrabold text-slate-800 dark:text-slate-150">
                        {{ title }}
                    </h3>
                    <p class="text-2xs text-slate-500 dark:text-slate-400 mt-1 leading-normal">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>

            <!-- Footer Meta -->
            <div class="text-center text-3xs text-slate-450 dark:text-slate-550 leading-relaxed px-4">
                Control panel smart-farm berbasis IoT untuk efisiensi pemeliharaan ayam pedaging otomatis.
            </div>
        </div>
    </div>
</template>
