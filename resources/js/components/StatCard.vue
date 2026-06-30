<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  title: string;
  value: string | number;
  subtitle?: string;
  icon?: any;
  trend?: {
    value: string;
    type: 'up' | 'down' | 'neutral';
  };
  colorClass?: string;
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  colorClass: 'from-blue-500/10 to-indigo-500/10 border-blue-500/20 text-blue-600 dark:text-blue-400',
  loading: false,
});

const trendClass = computed(() => {
  if (!props.trend) return '';
  switch (props.trend.type) {
    case 'up':
      return 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border border-emerald-500/20';
    case 'down':
      return 'text-rose-600 dark:text-rose-400 bg-rose-500/10 border border-rose-500/20';
    default:
      return 'text-slate-500 bg-slate-500/10 border border-slate-500/20';
  }
});
</script>

<template>
  <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-950">
    <!-- Gradient background accent -->
    <div :class="`absolute -right-16 -top-16 h-36 w-36 rounded-full bg-gradient-to-br opacity-20 blur-2xl ${colorClass}`"></div>

    <div class="flex items-center justify-between">
      <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ title }}</span>
      <div v-if="icon" :class="`flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br p-2.5 ${colorClass}`">
        <component :is="icon" class="h-5 w-5" />
      </div>
    </div>

    <div class="mt-4">
      <template v-if="loading">
        <!-- Value skeleton -->
        <div class="h-9 w-28 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-850"></div>
        <!-- Subtitle skeleton -->
        <div class="mt-3 h-3 w-40 animate-pulse rounded-md bg-slate-100 dark:bg-slate-900"></div>
      </template>
      <template v-else>
        <h3 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50">{{ value }}</h3>

        <div class="mt-2 flex items-center gap-2">
          <span v-if="trend" :class="`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ${trendClass}`">
            {{ trend.value }}
          </span>
          <span v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400">{{ subtitle }}</span>
        </div>
      </template>
    </div>
  </div>
</template>
