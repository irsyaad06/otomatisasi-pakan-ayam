<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import api from '@/services/api';
import DataTable from '@/components/DataTable.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import {
    Plus,
    X,
    Info,
    Calendar,
    Edit2,
    Trash2,
    Check,
    AlertTriangle,
    Layers,
    Clock,
    TrendingUp
} from 'lucide-vue-next';

// Define layout option with page breadcrumbs
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Periode Pemeliharaan',
                href: '/periode-pemeliharaan',
            },
        ],
    },
});

// State Management
const periodeList = ref<any[]>([]);
const loading = ref(false);
const showModal = ref(false);
const editingId = ref<number | null>(null);
const actionLoading = ref(false);
const searchQuery = ref('');

// Form Bindings
const form = ref({
    nama_periode: '',
    tanggal_mulai: new Date().toISOString().split('T')[0],
    tanggal_selesai: '' as string | null,
    jumlah_ayam: 100,
    stok_gudang_kg: 25.0
});

const formErrors = ref<Record<string, string>>({});

// Columns definition for DataTable
const columns = [
    { key: 'nama_periode', label: 'Nama Batch / Periode' },
    { key: 'tanggal_mulai', label: 'Tgl Mulai' },
    { key: 'tanggal_selesai', label: 'Tgl Selesai' },
    { key: 'jumlah_ayam', label: 'Populasi Ayam', class: 'text-right' },
    { key: 'status', label: 'Status', class: 'text-center' },
    { key: 'aksi', label: 'Aksi', class: 'text-right' }
];

// Computed active period
const activePeriod = computed(() => {
    return periodeList.value.find(p => p.status === 'aktif') || null;
});

// Fetch all period records
const fetchPeriode = async () => {
    loading.value = true;
    try {
        const response = await api.get('/periode-pemeliharaan');
        if (response.data?.status) {
            periodeList.value = response.data.data;
        }
    } catch (err) {
        console.error(err);
        toast.error('Gagal mengambil data periode pemeliharaan');
    } finally {
        loading.value = false;
    }
};

// Filtered items based on search query
const filteredItems = computed(() => {
    if (!searchQuery.value) return periodeList.value;
    const q = searchQuery.value.toLowerCase();
    return periodeList.value.filter(item => 
        item.nama_periode.toLowerCase().includes(q) ||
        item.status.toLowerCase().includes(q)
    );
});

// Helper: Calculate age of chicken (for active period display)
const getAgeOfChicken = (startDateStr: string) => {
    if (!startDateStr) return 0;
    const start = new Date(startDateStr);
    start.setHours(0, 0, 0, 0);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffTime = Math.max(0, today.getTime() - start.getTime());
    return Math.floor(diffTime / (1000 * 60 * 60 * 24));
};

// Open Modals
const openCreate = () => {
    editingId.value = null;
    formErrors.value = {};
    form.value = {
        nama_periode: '',
        tanggal_mulai: new Date().toISOString().split('T')[0],
        tanggal_selesai: '',
        jumlah_ayam: 100,
        stok_gudang_kg: 25.0
    };
    showModal.value = true;
};

const openEdit = (item: any) => {
    editingId.value = item.id;
    formErrors.value = {};
    form.value = {
        nama_periode: item.nama_periode,
        tanggal_mulai: item.tanggal_mulai ? item.tanggal_mulai.split('T')[0] : '',
        tanggal_selesai: item.tanggal_selesai ? item.tanggal_selesai.split('T')[0] : '',
        jumlah_ayam: item.jumlah_ayam,
        stok_gudang_kg: 0
    };
    showModal.value = true;
};

// Validation
const validateForm = () => {
    const errors: Record<string, string> = {};
    
    if (!form.value.nama_periode || form.value.nama_periode.trim() === '') {
        errors.nama_periode = 'Nama periode wajib diisi.';
    }
    if (!form.value.tanggal_mulai) {
        errors.tanggal_mulai = 'Tanggal mulai wajib diisi.';
    }
    if (form.value.jumlah_ayam < 1) {
        errors.jumlah_ayam = 'Jumlah ayam minimal 1 ekor.';
    }
    if (!editingId.value && form.value.stok_gudang_kg < 0) {
        errors.stok_gudang_kg = 'Stok gudang awal minimal 0 Kg.';
    }
    if (form.value.tanggal_selesai && form.value.tanggal_mulai && new Date(form.value.tanggal_selesai) < new Date(form.value.tanggal_mulai)) {
        errors.tanggal_selesai = 'Tanggal selesai harus sesudah atau sama dengan tanggal mulai.';
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
            const response = await api.put(`/periode-pemeliharaan/${editingId.value}`, form.value);
            if (response.data?.status) {
                toast.success('Periode pemeliharaan berhasil diperbarui!');
                showModal.value = false;
                fetchPeriode();
            }
        } else {
            // Minta konfirmasi jika ada periode yang masih aktif
            if (activePeriod.value) {
                const confirmed = confirm(
                    `Peringatan: Saat ini ada periode pemeliharaan aktif "${activePeriod.value.nama_periode}".\n\nMemulai periode baru akan otomatis MENYELESAIKAN periode aktif tersebut hari ini.\n\nApakah Anda yakin ingin melanjutkan?`
                );
                if (!confirmed) {
                    actionLoading.value = false;
                    return;
                }
            }

            const response = await api.post('/periode-pemeliharaan', form.value);
            if (response.data?.status) {
                toast.success('Periode pemeliharaan baru berhasil dimulai!');
                showModal.value = false;
                fetchPeriode();
            }
        }
    } catch (err: any) {
        const msg = err.response?.data?.message || 'Gagal menyimpan periode pemeliharaan.';
        toast.error(msg);
    } finally {
        actionLoading.value = false;
    }
};

// Manually complete an active period
const handleCompletePeriod = async (id: number, name: string) => {
    const confirmed = confirm(`Apakah Anda yakin ingin menyelesaikan periode "${name}" ini sekarang?`);
    if (!confirmed) return;

    try {
        const response = await api.post(`/periode-pemeliharaan/${id}/selesai`);
        if (response.data?.status) {
            toast.success('Periode pemeliharaan berhasil diselesaikan!');
            fetchPeriode();
        }
    } catch (err) {
        console.error(err);
        toast.error('Gagal menyelesaikan periode pemeliharaan.');
    }
};

// Delete record
const handleDelete = async (id: number, name: string) => {
    const confirmed = confirm(`Apakah Anda yakin ingin menghapus data periode "${name}" secara permanen?`);
    if (!confirmed) return;

    try {
        const response = await api.delete(`/periode-pemeliharaan/${id}`);
        if (response.data?.status) {
            toast.success('Data periode pemeliharaan berhasil dihapus!');
            fetchPeriode();
        }
    } catch (err) {
        console.error(err);
        toast.error('Gagal menghapus data periode pemeliharaan.');
    }
};

// Format Date
const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
};

onMounted(() => {
    fetchPeriode();
});
</script>

<template>
    <Head title="Periode Pemeliharaan" />

    <div class="space-y-6 p-6">
        <!-- Topbar Info -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                    <span>Manajemen Periode Pemeliharaan</span>
                    <Layers class="h-5 w-5 text-indigo-500" />
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Kelola batch pemeliharaan ayam pedaging aktif, pantau umur pertumbuhan, dan inisialisasi persediaan pakan.
                </p>
            </div>
            
            <div class="flex items-center gap-2.5">
                <div class="relative w-48 sm:w-64">
                    <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Cari periode..." 
                        class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 shadow-xs" 
                    />
                </div>
                <button 
                    @click="openCreate" 
                    class="flex items-center gap-1.5 rounded-xl bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-purple-505 hover:bg-purple-500 transition-all cursor-pointer"
                >
                    <Plus class="h-4 w-4" />
                    Mulai Periode Baru
                </button>
            </div>
        </div>

        <!-- Panel Atas: Periode Aktif Saat Ini -->
        <div class="grid gap-6 md:grid-cols-3">
            <div class="md:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <Clock class="h-4 w-4 text-indigo-500" />
                            Periode Pemeliharaan Aktif Saat Ini
                        </h2>
                        <span v-if="activePeriod" class="px-2.5 py-0.5 rounded-full text-3xs font-extrabold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                            Sedang Berjalan
                        </span>
                        <span v-else class="px-2.5 py-0.5 rounded-full text-3xs font-extrabold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                            Kosong
                        </span>
                    </div>

                    <div v-if="activePeriod" class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-1">
                            <span class="text-2xs text-slate-400 block uppercase font-mono tracking-wider">Nama Batch / Periode</span>
                            <span class="text-lg font-extrabold text-slate-800 dark:text-slate-200">{{ activePeriod.nama_periode }}</span>
                            <div class="text-2xs text-slate-500 mt-1 space-y-0.5">
                                <div>Mulai: <span class="font-bold text-slate-700 dark:text-slate-300">{{ formatDate(activePeriod.tanggal_mulai) }}</span></div>
                                <div>Target Selesai: <span class="font-bold text-slate-700 dark:text-slate-300">{{ formatDate(activePeriod.tanggal_selesai) }}</span></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 pl-6 border-l border-slate-100 dark:border-slate-800/80">
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-[9px] font-mono text-slate-400 block mb-0.5 uppercase tracking-wider">Umur Ayam</span>
                                <span class="text-sm font-extrabold text-slate-800 dark:text-slate-200">{{ getAgeOfChicken(activePeriod.tanggal_mulai) }} hari</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-[9px] font-mono text-slate-400 block mb-0.5 uppercase tracking-wider">Populasi</span>
                                <span class="text-sm font-extrabold text-indigo-600 dark:text-indigo-400">{{ activePeriod.jumlah_ayam }} ekor</span>
                            </div>
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                                <span class="text-[9px] font-mono text-slate-400 block mb-0.5 uppercase tracking-wider">Status Batch</span>
                                <span class="text-3xs px-1.5 py-0.5 rounded font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 block text-center mt-1">Aktif</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center border border-dashed rounded-2xl border-slate-200 dark:border-slate-800/80">
                        <Info class="h-8 w-8 text-slate-400 mx-auto mb-2" />
                        <p class="text-xs text-slate-500 font-medium">Tidak ada periode pemeliharaan yang aktif saat ini.</p>
                        <p class="text-3xs text-slate-400 mt-1">Klik tombol "Mulai Periode Baru" di atas untuk memulai batch pemeliharaan ayam.</p>
                    </div>
                </div>

                <div v-if="activePeriod" class="border-t border-slate-100 dark:border-slate-800/80 pt-4 mt-6 flex justify-end">
                    <button 
                        @click="handleCompletePeriod(activePeriod.id, activePeriod.nama_periode)"
                        class="flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-50 px-4 py-2 text-xs font-bold text-rose-600 transition-all dark:border-rose-950/20 dark:bg-rose-950/10 dark:text-rose-455 cursor-pointer"
                    >
                        <Check class="h-4 w-4" />
                        Selesaikan Periode Batch Ini
                    </button>
                </div>
            </div>

            <!-- Panel Samping: Penjelasan & Alur -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950 flex flex-col justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
                        <Info class="h-4 w-4 text-indigo-500" />
                        Panduan Pengelolaan Periode
                    </h2>
                    <div class="text-3xs text-slate-500 space-y-3 leading-relaxed">
                        <p>
                            <strong>Periode Pemeliharaan</strong> merepresentasikan batch atau siklus pertumbuhan ayam pedaging (broiler) di dalam kandang.
                        </p>
                        <p>
                            <strong>Aturan Sistem:</strong>
                        </p>
                        <ul class="list-disc pl-4 space-y-1.5 mt-1">
                            <li>Hanya diperbolehkan ada <strong>satu periode aktif</strong> dalam satu waktu.</li>
                            <li>Memulai periode baru secara otomatis akan memicu penutupan periode lama.</li>
                            <li>Tanggal mulai digunakan untuk melacak umur ayam yang mempengaruhi target takaran pakan harian dinamis (Starter/Grower/Finisher).</li>
                            <li>Tanggal selesai bersifat opsional untuk target perencanaan estimasi masa panen ayam.</li>
                        </ul>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2 text-3xs text-slate-400">
                    <TrendingUp class="h-4 w-4 text-emerald-500" />
                    <span>Perubahan jumlah populasi ayam memengaruhi perhitungan pakan IoT harian.</span>
                </div>
            </div>
        </div>

        <!-- DataTable Riwayat Catatan Periode -->
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-slate-850 dark:text-slate-100">Riwayat Batch / Periode Pemeliharaan</h2>
            
            <DataTable :columns="columns" :items="filteredItems" :loading="loading" emptyMessage="Belum ada catatan histori periode pemeliharaan.">
                <template #nama_periode="{ item }">
                    <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">{{ item.nama_periode }}</span>
                </template>
                <template #tanggal_mulai="{ item }">
                    <span class="text-xs">{{ formatDate(item.tanggal_mulai) }}</span>
                </template>
                <template #tanggal_selesai="{ item }">
                    <span class="text-xs">{{ formatDate(item.tanggal_selesai) }}</span>
                </template>
                <template #jumlah_ayam="{ item }">
                    <span class="font-bold font-mono text-xs">{{ item.jumlah_ayam }} ekor</span>
                </template>
                <template #status="{ item }">
                    <div class="flex justify-center">
                        <span :class="
                            item.status === 'aktif' 
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-450 border border-emerald-100 dark:border-emerald-900/30' 
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-400 border border-slate-200/50 dark:border-slate-800'
                        " class="text-3xs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">
                            {{ item.status === 'aktif' ? 'Aktif' : 'Selesai' }}
                        </span>
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
                            v-if="item.status !== 'aktif'"
                            @click="handleDelete(item.id, item.nama_periode)" 
                            class="rounded-lg p-1.5 border border-rose-200 hover:bg-rose-50 dark:border-rose-950/20 dark:hover:bg-rose-950/40 text-rose-600 dark:text-rose-455 transition-colors cursor-pointer"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Slide Panel Modal Overlay (Create/Edit) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs transition-opacity duration-300">
            <!-- Backdrop click close -->
            <div class="absolute inset-0 cursor-pointer" @click="showModal = false"></div>

            <!-- Slide panel container -->
            <div class="relative w-full max-w-md bg-white dark:bg-slate-950 h-full p-6 shadow-2xl overflow-y-auto flex flex-col justify-between transition-transform duration-300 transform translate-x-0">
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 border-slate-100 dark:border-slate-800">
                        <h3 class="text-md font-bold text-slate-900 dark:text-slate-50 capitalize">
                            {{ editingId ? 'Edit Data Periode' : 'Mulai Periode Pemeliharaan Baru' }}
                        </h3>
                        <button @click="showModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900 cursor-pointer">
                            <X class="h-5 w-5 text-slate-555" />
                        </button>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4" id="periode-form">
                        <!-- Nama Periode -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Nama Periode / Batch</label>
                            <input 
                                type="text" 
                                v-model="form.nama_periode" 
                                placeholder="Contoh: Periode Broiler Batch 3" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.nama_periode" class="text-3xs text-rose-500">{{ formErrors.nama_periode }}</p>
                        </div>

                        <!-- Tanggal Mulai -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Tanggal Mulai (Wajib)</label>
                            <input 
                                type="date" 
                                v-model="form.tanggal_mulai" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.tanggal_mulai" class="text-3xs text-rose-500">{{ formErrors.tanggal_mulai }}</p>
                        </div>

                        <!-- Tanggal Selesai -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Tanggal Selesai (Opsional)</label>
                            <input 
                                type="date" 
                                v-model="form.tanggal_selesai" 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.tanggal_selesai" class="text-3xs text-rose-500">{{ formErrors.tanggal_selesai }}</p>
                        </div>

                        <!-- Jumlah Ayam -->
                        <div class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Jumlah Populasi Ayam (Ekor)</label>
                            <input 
                                type="number" 
                                v-model.number="form.jumlah_ayam" 
                                min="1" 
                                required 
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.jumlah_ayam" class="text-3xs text-rose-500">{{ formErrors.jumlah_ayam }}</p>
                        </div>

                        <!-- Stok Pakan Gudang Awal -->
                        <div v-if="!editingId" class="space-y-1">
                            <label class="text-xs font-semibold text-slate-500">Stok Awal di Gudang (Kg)</label>
                            <input 
                                type="number" 
                                step="0.1" 
                                v-model.number="form.stok_gudang_kg" 
                                min="0" 
                                required 
                                placeholder="Contoh: 25.0 Kg"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
                            />
                            <p v-if="formErrors.stok_gudang_kg" class="text-3xs text-rose-500">{{ formErrors.stok_gudang_kg }}</p>
                        </div>
                        <div v-if="!editingId" class="text-3xs text-slate-500 leading-normal">
                            *Sistem otomatis menginisialisasi level persediaan pakan awal batch pada database sesuai stok gudang penyimpanan, sedangkan stok silo dideteksi dinamis menggunakan sensor ultrasonik.
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
                        form="periode-form" 
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
