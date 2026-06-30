<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  status: string;
}

const props = defineProps<Props>();

const config = computed(() => {
  const s = props.status.toLowerCase().replace(/_/g, ' ');
  switch (props.status.toLowerCase()) {
    // Stok statuses
    case 'aman':
      return { label: 'Aman', class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' };
    case 'hampir_habis':
    case 'hampir habis':
      return { label: 'Hampir Habis', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20' };
    case 'habis':
      return { label: 'Habis', class: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20' };

    // Koneksi & Sensor statuses
    case 'online':
    case 'aktif':
    case 'normal':
      return { label: s.charAt(0).toUpperCase() + s.slice(1), class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' };
    case 'offline':
    case 'mati':
      return { label: s.charAt(0).toUpperCase() + s.slice(1), class: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700' };
    case 'rusak':
    case 'error':
    case 'gagal':
      return { label: s.charAt(0).toUpperCase() + s.slice(1), class: 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20' };
    case 'berhasil':
      return { label: 'Berhasil', class: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20' };
    
    // Fallback
    default:
      return { label: s.charAt(0).toUpperCase() + s.slice(1), class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20' };
  }
});
</script>

<template>
  <span :class="`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold ${config.class}`">
    <!-- Pulse dot for active/online statuses -->
    <span v-if="['online', 'aktif', 'normal', 'berhasil', 'aman'].includes(status.toLowerCase())" class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
    {{ config.label }}
  </span>
</template>
