<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';
import { Calendar } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const currentTimeString = ref('');

const updateTime = () => {
    const options: Intl.DateTimeFormatOptions = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };
    currentTimeString.value = new Date().toLocaleDateString('id-ID', options);
};

let timer: any = null;

onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        
        <!-- Live Clock Display -->
        <div class="hidden sm:flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 shadow-xs">
            <Calendar class="h-3.5 w-3.5 text-indigo-500" />
            <span class="tabular-nums">{{ currentTimeString }}</span>
        </div>
    </header>
</template>

