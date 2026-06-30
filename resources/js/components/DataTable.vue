<script setup lang="ts">
interface Column {
  key: string;
  label: string;
  class?: string;
}

interface Props {
  columns: Column[];
  items: any[];
  loading?: boolean;
  emptyMessage?: string;
}

withDefaults(defineProps<Props>(), {
  loading: false,
  emptyMessage: 'Tidak ada data tersedia.',
});
</script>

<template>
  <div class="w-full overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950">
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-left text-sm text-slate-500 dark:text-slate-400">
        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-700 dark:bg-slate-900/50 dark:text-slate-300">
          <tr>
            <th 
              v-for="col in columns" 
              :key="col.key" 
              scope="col" 
              :class="`px-6 py-4 font-semibold ${col.class || ''}`"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
          <!-- Loading state with Skeleton Loader -->
          <template v-if="loading">
            <tr v-for="n in 5" :key="'skeleton-' + n" class="animate-pulse">
              <td 
                v-for="col in columns" 
                :key="'skeleton-col-' + col.key" 
                class="px-6 py-4"
              >
                <div class="h-3.5 bg-slate-200 dark:bg-slate-800 rounded-lg w-2/3"></div>
              </td>
            </tr>
          </template>

          <!-- Empty state -->
          <tr v-else-if="items.length === 0">
            <td :colspan="columns.length" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
              <div class="flex flex-col items-center justify-center gap-2">
                <!-- Simple empty box icon -->
                <svg class="h-10 w-10 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span class="text-sm font-medium">{{ emptyMessage }}</span>
              </div>
            </td>
          </tr>

          <!-- Data rows -->
          <tr 
            v-else 
            v-for="(item, idx) in items" 
            :key="item.id || idx" 
            class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-900/30"
          >
            <td 
              v-for="col in columns" 
              :key="col.key" 
              :class="`px-6 py-4 text-slate-700 dark:text-slate-300 ${col.class || ''}`"
            >
              <slot :name="col.key" :item="item" :index="idx">
                {{ item[col.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
