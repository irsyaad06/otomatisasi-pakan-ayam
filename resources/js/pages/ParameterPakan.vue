<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import api from '@/services/api';
import DataTable from '@/components/DataTable.vue';
import {
    Plus,
    Search,
    Edit2,
    Trash2,
    X,
    Info,
    SlidersHorizontal,
    TrendingUp,
    Clock,
    Scale
} from 'lucide-vue-next';

// Set page breadcrumbs for AppLayout
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Parameter Pakan',
                href: '/parameter-pakan',
            },
        ],
    },
});

// State Management
const kebutuhanList = ref<any[]>([]);
const loading = ref(false);
const showModal = ref(false);
const editingId = ref<number | null>(null);
const actionLoading = ref(false);
const searchQuery = ref('');

// Form Fields
const form = ref({
    fase_umur: 'Starter',
    umur_mulai_hari: 0,
    umur_selesai_hari: 10,
    gram_per_ekor_per_hari: 25,
    frekuensi_pemberian_per_hari: 4
});

const formErrors = ref<Record<string, string>>({});

// Table columns
const columns = [
    { key: 'fase_umur', label: 'Fase Umur' },
    { key: 'rentang_umur', label: 'Rentang Umur' },
    { key: 'gram_per_ekor_per_hari', label: 'Gram/Ekor/Hari', class: 'text-right' },
    { key: 'frekuensi_pemberian_per_hari', label: 'Frekuensi/Hari', class: 'text-right' },
    { key: 'porsi_per_jadwal', label: 'Porsi Per Ekor Per Jadwal', class: 'text-right' },
    { key: 'aksi', label: 'Aksi', class: 'text-right' }
];

// Fetch all kebutuhan pakan records
const fetchKebutuhan = async () => {
    loading.value = true;
    try {
        const response = await api.get('/kebutuhan-pakan');
        if (response.data?.status) {
            kebutuhanList.value = response.data.data;
        }
    } catch (err) {
        console.error(err);
        toast.error('Gagal mengambil data parameter pakan');
    } finally {
        loading.value = false;
    }
};

// Filtered items based on search query
const filteredItems = computed(() => {
    if (!searchQuery.value) return kebutuhanList.value;
    const q = searchQuery.value.toLowerCase();
    return kebutuhanList.value.filter(item => 
        item.fase_umur.toLowerCase().includes(q) ||
        item.umur_mulai_hari.toString().includes(q) ||
        item.umur_selesai_hari.toString().includes(q)
    );
});

// Helper: Calculate Portion per Feed
const getPorsiPerEkorJadwal = (gram: number, frekuensi: number) => {
    if (frekuensi <= 0) return 0;
    return (gram / frekuensi).toFixed(1);
};

// Modal Triggers
const openCreate = () => {
    editingId.value = null;
    formErrors.value = {};
    form.value = {
        fase_umur: 'Starter',
        umur_mulai_hari: 0,
        umur_selesai_hari: 10,
        gram_per_ekor_per_hari: 25,
        frekuensi_pemberian_per_hari: 4
    };
    showModal.value = true;
};

const openEdit = (item: any) => {
    editingId.value = item.id;
    formErrors.value = {};
    form.value = {
        fase_umur: item.fase_umur,
        umur_mulai_hari: item.umur_mulai_hari,
        umur_selesai_hari: item.umur_selesai_hari,
        gram_per_ekor_per_hari: item.gram_per_ekor_per_hari,
        frekuensi_pemberian_per_hari: item.frekuensi_pemberian_per_hari
    };
    showModal.value = true;
};

// Local validation
const validateForm = () => {
    const errors: Record<string, string> = {};
    
    if (!form.value.fase_umur || form.value.fase_umur.trim() === '') {
        errors.fase_umur = 'Fase umur wajib diisi.';
    }
    
    if (form.value.umur_mulai_hari < 0) {
        errors.umur_mulai_hari = 'Umur mulai minimal 0 hari.';
    }
    
    if (form.value.umur_selesai_hari < form.value.umur_mulai_hari) {
        errors.umur_selesai_hari = 'Umur selesai harus lebih besar atau sama dengan umur mulai.';
    }
    
    if (form.value.gram_per_ekor_per_hari < 1) {
        errors.gram_per_ekor_per_hari = 'Kebutuhan pakan minimal 1 gram.';
    }
    
    if (form.value.frekuensi_pemberian_per_hari < 1) {
        errors.frekuensi_pemberian_per_hari = 'Frekuensi pemberian minimal 1 kali.';
    }
    
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Form submit handler
const handleSubmit = async () => {
    if (!validateForm()) return;
    
    actionLoading.value = true;
    try {
        if (editingId.value) {
            const response = await api.put(`/kebutuhan-pakan/${editingId.value}`, form.value);
            if (response.data?.status) {
                toast.success('Parameter pakan berhasil diperbarui!');
                showModal.value = false;
                fetchKebutuhan();
            }
        } else {
            const response = await api.post('/kebutuhan-pakan', form.value);
            if (response.data?.status) {
                toast.success('Parameter pakan baru berhasil ditambahkan!');
                showModal.value = false;
                fetchKebutuhan();
            }
        }
    } catch (err: any) {
        const msg = err.response?.data?.message || 'Gagal menyimpan parameter pakan.';
        toast.error(msg);
    } finally {
        actionLoading.value = false;
    }
};

// Delete record handler
const deleteItem = async (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus parameter pakan ini secara permanen?')) {
        try {
            const response = await api.delete(`/kebutuhan-pakan/${id}`);
            if (response.data?.status) {
                toast.success('Parameter pakan berhasil dihapus!');
                fetchKebutuhan();
            }
        } catch (err) {
            console.error(err);
            toast.error('Gagal menghapus parameter pakan.');
        }
    }
};

onMounted(() => {
    fetchKebutuhan();
});
</script>

<template>
    <Head title="Parameter Pakan" />

    <div class="space-y-6 p-6">
        <!-- Topbar Info -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                    <span>Master Parameter Pakan</span>
                    <SlidersHorizontal class="h-5 w-5 text-indigo-500" />
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Kelola data kebutuhan pakan harian dan frekuensi pemberian berdasarkan fase umur ayam.
                </p>
            </div>
            
            <div class="flex items-center gap-2.5">
                <div class="relative w-48 sm:w-64">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Cari fase / umur..." 
                        class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-xs" 
                    />
                </div>
                <button 
                    @click="openCreate" 
                    class="flex items-center gap-1.5 rounded-xl bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-purple-500 transition-all cursor-pointer"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Parameter
                </button>
            </div>
        </div>

        <!-- Analytical Context Summary Cards -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-950 flex items-center gap-4">
                <div class="p-3 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                    <Info class="h-5 w-5" />
                </div>
                <div>
                    <span class="text-2xs uppercase font-mono text-slate-400 block">Total Fase Terdaftar</span>
                    <span class="text-md font-bold text-slate-800 dark:text-slate-100">{{ kebutuhanList.length }} Fase</span>
                </div>
            </div>
        </div>

        <!-- DataTable Container -->
        <DataTable :columns="columns" :items="filteredItems" :loading="loading" emptyMessage="Belum ada data parameter kebutuhan pakan.">
            <template #fase_umur="{ item }">
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full" :class="
                        item.fase_umur === 'Starter' ? 'bg-indigo-500' :
                        item.fase_umur === 'Grower' ? 'bg-emerald-500' : 'bg-amber-500'
                    "></span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ item.fase_umur }}</span>
                </div>
            </template>

            <template #rentang_umur="{ item }">
                <span class="text-xs font-semibold text-slate-650 dark:text-slate-350 bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded-lg border border-slate-200/50 dark:border-slate-800">
                    {{ item.umur_mulai_hari }} - {{ item.umur_selesai_hari }} hari
                </span>
            </template>

            <template #gram_per_ekor_per_hari="{ item }">
                <div class="text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                    {{ item.gram_per_ekor_per_hari }} g
                </div>
            </template>

            <template #frekuensi_pemberian_per_hari="{ item }">
                <div class="text-right font-mono font-semibold text-slate-700 dark:text-slate-300">
                    {{ item.frekuensi_pemberian_per_hari }}x / hari
                </div>
            </template>

            <template #porsi_per_jadwal="{ item }">
                <div class="text-right font-mono font-bold text-purple-600 dark:text-purple-400">
                    {{ getPorsiPerEkorJadwal(item.gram_per_ekor_per_hari, item.frekuensi_pemberian_per_hari) }} g / porsi
                </div>
            </template>

            <template #aksi="{ item }">
                <div class="flex justify-end gap-2">
                    <button 
                        @click="openEdit(item)" 
                        class="rounded-lg p-1.5 border border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-400 transition-colors cursor-pointer"
                    >
                        <Edit2 class="h-3.5 w-3.5" />
                    </button>
                    <button 
                        @click="deleteItem(item.id)" 
                        class="rounded-lg p-1.5 border border-rose-200 hover:bg-rose-50 dark:border-rose-950/20 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-455 transition-colors cursor-pointer"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Slide Panel Modal Overlay (Create/Edit) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs transition-opacity duration-300">
            <!-- Backdrop click close -->
            <div class="absolute inset-0 cursor-pointer" @click="showModal = false"></div>

            <!-- Slide panel container -->
            <div class="relative w-full max-w-md bg-white dark:bg-slate-950 h-full p-6 shadow-2xl overflow-y-auto flex flex-col justify-between transition-transform duration-300 transform translate-x-0">
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 border-slate-100 dark:border-slate-800">
                        <h3 class="text-md font-bold text-slate-900 dark:text-slate-50 capitalize">
                            {{ editingId ? 'Edit Parameter Pakan' : 'Tambah Parameter Pakan' }}
                        </h3>
                        <button @click="showModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900 cursor-pointer">
                            <X class="h-5 w-5 text-slate-500" />
                        </button>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4" id="parameter-form">
                        <!-- Fase Umur -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Fase Umur Ayam</label>
                            <input 
                                type="text" 
                                v-model="form.fase_umur" 
                                placeholder="Contoh: Starter, Grower, Finisher" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.fase_umur" class="text-3xs text-rose-500">{{ formErrors.fase_umur }}</p>
                        </div>

                        <!-- Rentang Umur Mulai -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Umur Mulai (Hari)</label>
                            <input 
                                type="number" 
                                v-model.number="form.umur_mulai_hari" 
                                min="0" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.umur_mulai_hari" class="text-3xs text-rose-500">{{ formErrors.umur_mulai_hari }}</p>
                        </div>

                        <!-- Rentang Umur Selesai -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Umur Selesai (Hari)</label>
                            <input 
                                type="number" 
                                v-model.number="form.umur_selesai_hari" 
                                min="0" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.umur_selesai_hari" class="text-3xs text-rose-500">{{ formErrors.umur_selesai_hari }}</p>
                        </div>

                        <!-- Gram Per Ekor Per Hari -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Gram / Ekor / Hari (Dosis Harian)</label>
                            <input 
                                type="number" 
                                v-model.number="form.gram_per_ekor_per_hari" 
                                min="1" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.gram_per_ekor_per_hari" class="text-3xs text-rose-500">{{ formErrors.gram_per_ekor_per_hari }}</p>
                        </div>

                        <!-- Frekuensi Pemberian -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Frekuensi Pemberian Pakan (Kali/Hari)</label>
                            <input 
                                type="number" 
                                v-model.number="form.frekuensi_pemberian_per_hari" 
                                min="1" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.frekuensi_pemberian_per_hari" class="text-3xs text-rose-500">{{ formErrors.frekuensi_pemberian_per_hari }}</p>
                        </div>

                        <!-- Live Calculation Preview -->
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-900/50 p-3 border border-slate-100 dark:border-slate-800 space-y-1">
                            <span class="text-3xs uppercase font-mono text-slate-400 block">Kalkulasi Porsi Per Ekor Per Jadwal</span>
                            <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                {{ getPorsiPerEkorJadwal(form.gram_per_ekor_per_hari, form.frekuensi_pemberian_per_hari) }} gram / jadwal
                            </div>
                            <span class="text-3xs text-slate-500 dark:text-slate-450 block">Formula: dosis harian / frekuensi</span>
                        </div>
                    </form>
                </div>

                <div class="border-t pt-4 border-slate-100 dark:border-slate-800 flex justify-end gap-2.5">
                    <button 
                        @click="showModal = false" 
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-350 cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        form="parameter-form" 
                        :disabled="actionLoading" 
                        class="rounded-xl bg-purple-600 text-white px-4 py-2 text-xs font-semibold hover:bg-purple-500 transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        {{ actionLoading ? 'Menyimpan...' : 'Simpan Data' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
