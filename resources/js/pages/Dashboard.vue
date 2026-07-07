<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useDashboardStore } from '@/stores/dashboardStore';
import { toast } from 'vue-sonner';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import DataTable from '@/components/DataTable.vue';
import { 
  Database, Cpu, Clock, TrendingUp, Plus, Trash2, Edit2, Eye,
  Play, Send, AlertTriangle, Zap, Calendar, Wifi, Settings, 
  RefreshCw, Check, X, ShieldAlert, ChevronRight, Search, 
  ChevronLeft, ArrowRight, Activity, Info, LogOut
} from 'lucide-vue-next';

// Define the active tab prop from Inertia routing
const props = defineProps<{
  tab?: string;
}>();

const currentTab = computed(() => props.tab || 'dashboard');

// Instantiate Pinia store
const store = useDashboardStore();

// --- STATE MANAGEMENT ---
// Polling interval
let pollingInterval: any = null;

// Search & Pagination States
const queryJadwal = ref('');
const pageJadwal = ref(1);

const queryStok = ref('');
const pageStok = ref(1);

const queryLog = ref('');
const pageLog = ref(1);

const queryStatus = ref('');
const pageStatus = ref(1);

const limitPerPage = 6;

// Pagination for Daily Proyeksi
const currentPageProyeksi = ref(1);
const itemsPerPageProyeksi = 7;
const activeProyeksiTab = ref<'silo' | 'gudang'>('silo');

const paginatedProyeksi = computed(() => {
  const source = activeProyeksiTab.value === 'silo' ? store.ramalanStok?.silo : store.ramalanStok?.gudang;
  if (!source || !source.detail_harian) return [];
  const start = (currentPageProyeksi.value - 1) * itemsPerPageProyeksi;
  const end = start + itemsPerPageProyeksi;
  return source.detail_harian.slice(start, end);
});

const totalPagesProyeksi = computed(() => {
  const source = activeProyeksiTab.value === 'silo' ? store.ramalanStok?.silo : store.ramalanStok?.gudang;
  if (!source || !source.detail_harian) return 0;
  return Math.ceil(source.detail_harian.length / itemsPerPageProyeksi);
});

// Watch ramalanStok changes to reset page
watch(() => store.ramalanStok, () => {
  currentPageProyeksi.value = 1;
});

// Modals / Slide Panels visibility
const showCrudModal = ref(false);
const activeModalType = ref<'jadwal' | 'stok' | 'log' | 'status' | null>(null);
const editingId = ref<number | null>(null);

// Detail Modals visibility
const showDetailModal = ref(false);
const detailType = ref<'jadwal' | 'status' | null>(null);
const detailItem = ref<any>(null);
const detailHistory = ref<any[]>([]);
const detailSensorHistory = ref<any[]>([]);
const loadingDetail = ref(false);

// Form Bindings
const formJadwal = ref({
  waktu_pakan: '',
  fase_umur: 'Grower',
  target_pakan_gram: 1000 as number | null,
  durasi_motor_detik: 20 as number | null,
  status_aktif: true,
  target_otomatis: true
});

const formStok = ref({
  berat_pakan_gram: 4000,
  persentase_stok: 65,
  status_stok: 'aman',
  waktu_pembacaan: ''
});

const formLog = ref({
  jadwal_pakan_id: null as number | null,
  sumber: 'manual',
  waktu_mulai: '',
  waktu_selesai: '',
  durasi_motor_detik: 15,
  jumlah_pakan_keluar_gram: 500,
  status: 'berhasil',
  keterangan: ''
});

const formStatus = ref({
  nama_perangkat: 'ESP32-Feeder',
  status_koneksi: 'online',
  status_motor: 'mati',
  status_sensor: 'normal',
  mode_operasi: 'otomatis',
  terakhir_online: ''
});

// --- DATA FETCHING & POLLING ---
const loadAllData = () => {
  store.fetchDashboard();
  store.fetchCharts();
  
  if (currentTab.value === 'jadwal') {
    store.fetchJadwal();
  } else if (currentTab.value === 'stok') {
    store.fetchStok();
  } else if (currentTab.value === 'riwayat') {
    store.fetchLogs();
  } else if (currentTab.value === 'status') {
    store.fetchStatus();
  }
};

onMounted(() => {
  loadAllData();
  // 10 seconds polling
  pollingInterval = setInterval(() => {
    loadAllData();
  }, 10000);
});

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
});

// Watch tab changes to reload and reset pagination
watch(currentTab, () => {
  loadAllData();
  pageJadwal.value = 1;
  pageStok.value = 1;
  pageLog.value = 1;
  pageStatus.value = 1;
  currentPageProyeksi.value = 1;
  activeProyeksiTab.value = 'silo';
  showCrudModal.value = false;
  showDetailModal.value = false;
});

// --- APEXCHARTS CONFIGURATIONS ---
const stokChartOptions = computed(() => {
  const categories = store.stokChartData.map(item => {
    const d = new Date(item.waktu_pembacaan);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  });

  return {
    chart: {
      type: 'area',
      toolbar: { show: false },
      zoom: { enabled: false }
    },
    colors: ['#10B981'], // Emerald
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.45,
        opacityTo: 0.05,
        stops: [0, 100]
      }
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
      categories: categories,
      labels: { style: { colors: '#94A3B8', fontSize: '9px' } }
    },
    yaxis: {
      min: 0,
      max: 100,
      labels: { style: { colors: '#94A3B8', fontSize: '9px' } }
    },
    grid: { borderColor: '#F1F5F9', strokeDashArray: 4 }
  };
});

const stokChartSeries = computed(() => {
  return [{
    name: 'Silo Level (%)',
    data: store.stokChartData.map(item => item.persentase_stok)
  }];
});

const logChartOptions = computed(() => {
  const categories = store.logChartData.map(item => item.hari);
  return {
    chart: {
      type: 'bar',
      toolbar: { show: false }
    },
    colors: ['#4F46E5'], // Indigo
    plotOptions: {
      bar: {
        borderRadius: 5,
        columnWidth: '45%'
      }
    },
    dataLabels: { enabled: false },
    xaxis: {
      categories: categories,
      labels: { style: { colors: '#94A3B8', fontSize: '9px' } }
    },
    yaxis: {
      labels: { 
        style: { colors: '#94A3B8', fontSize: '9px' },
        formatter: (val: number) => `${(val / 1000).toFixed(1)} kg`
      }
    },
    grid: { borderColor: '#F1F5F9', strokeDashArray: 4 }
  };
});

const logChartSeries = computed(() => {
  return [{
    name: 'Total Pakan Keluar (gram)',
    data: store.logChartData.map(item => item.total_gram)
  }];
});

// --- FILTER & PAGINATION CALCULATIONS ---
// 1. Jadwal Pakan
const filteredJadwal = computed(() => {
  const query = queryJadwal.value.toLowerCase();
  let items = store.jadwalList;
  if (query) {
    items = items.filter(item => 
      item.fase_umur.toLowerCase().includes(query) ||
      item.waktu_pakan.includes(query)
    );
  }
  return items;
});
const paginatedJadwal = computed(() => {
  const start = (pageJadwal.value - 1) * limitPerPage;
  return filteredJadwal.value.slice(start, start + limitPerPage);
});
const totalPagesJadwal = computed(() => Math.ceil(filteredJadwal.value.length / limitPerPage) || 1);

// 2. Stok Pakan
const filteredStok = computed(() => {
  const query = queryStok.value.toLowerCase();
  let items = store.stokList;
  if (query) {
    items = items.filter(item => 
      item.status_stok.toLowerCase().includes(query) ||
      item.berat_pakan_gram.toString().includes(query)
    );
  }
  return items;
});
const paginatedStok = computed(() => {
  const start = (pageStok.value - 1) * limitPerPage;
  return filteredStok.value.slice(start, start + limitPerPage);
});
const totalPagesStok = computed(() => Math.ceil(filteredStok.value.length / limitPerPage) || 1);

// 3. Riwayat Pakan
const filteredLogs = computed(() => {
  const query = queryLog.value.toLowerCase();
  let items = store.logList;
  if (query) {
    items = items.filter(item => 
      item.sumber.toLowerCase().includes(query) ||
      item.status.toLowerCase().includes(query) ||
      (item.keterangan && item.keterangan.toLowerCase().includes(query))
    );
  }
  return items;
});
const paginatedLogs = computed(() => {
  const start = (pageLog.value - 1) * limitPerPage;
  return filteredLogs.value.slice(start, start + limitPerPage);
});
const totalPagesLogs = computed(() => Math.ceil(filteredLogs.value.length / limitPerPage) || 1);

// 4. Status Alat
const filteredStatus = computed(() => {
  const query = queryStatus.value.toLowerCase();
  let items = store.statusList;
  if (query) {
    items = items.filter(item => 
      item.nama_perangkat.toLowerCase().includes(query) ||
      item.status_koneksi.toLowerCase().includes(query) ||
      item.mode_operasi.toLowerCase().includes(query)
    );
  }
  return items;
});
const paginatedStatus = computed(() => {
  const start = (pageStatus.value - 1) * limitPerPage;
  return filteredStatus.value.slice(start, start + limitPerPage);
});
const totalPagesStatus = computed(() => Math.ceil(filteredStatus.value.length / limitPerPage) || 1);
const logColumns = [
  { key: 'waktu_mulai', label: 'Waktu' },
  { key: 'sumber', label: 'Sumber' },
  { key: 'durasi_motor_detik', label: 'Durasi Motor' },
  { key: 'jumlah_pakan_keluar_gram', label: 'Pakan Keluar' },
  { key: 'status', label: 'Status' },
  { key: 'keterangan', label: 'Keterangan' },
  { key: 'aksi', label: 'Aksi', class: 'text-right' }
];

const jadwalColumns = [
  { key: 'waktu_pakan', label: 'Jam Jadwal' },
  { key: 'fase_umur', label: 'Fase Umur' },
  { key: 'target_pakan_gram', label: 'Target Porsi' },
  { key: 'status_aktif', label: 'Status Aktif' },
  { key: 'aksi', label: 'Aksi', class: 'text-right' }
];

const stokColumns = [
  { key: 'waktu_pembacaan', label: 'Waktu Pembacaan' },
  { key: 'berat_pakan_gram', label: 'Berat Pakan' },
  { key: 'persentase_stok', label: 'Persentase Silo' },
  { key: 'status_stok', label: 'Keterangan' },
  { key: 'aksi', label: 'Aksi', class: 'text-right' }
];

const statusColumns = [
  { key: 'nama_perangkat', label: 'Nama Perangkat' },
  { key: 'status_koneksi', label: 'Koneksi WiFi' },
  { key: 'status_sensor', label: 'Sensor' },
  { key: 'status_motor_gabungan', label: 'Motor' },
  { key: 'terakhir_online', label: 'Terakhir Online' }
];

// --- HELPERS ---
const formatGramToKg = (gram: number | null | undefined): string => {
  if (gram === null || gram === undefined) return '0 kg';
  if (gram >= 1000) {
    return `${(gram / 1000).toFixed(2)} kg`;
  }
  return `${gram} gram`;
};

const formatTime = (timeStr: string | null | undefined): string => {
  if (!timeStr) return '--:--:--';
  return timeStr.length === 5 ? `${timeStr}:00` : timeStr;
};

const formatDate = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatDatetimeLocal = (dateStr: string | null | undefined): string => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
  return d.toISOString().slice(0, 16);
};

// --- SIMULATION TRIGGERS ---
const triggerSimulasiKirimData = async () => {
  await store.simulasiKirimData();
  if (!store.error) toast.success('Simulasi data ESP32 berhasil dikirim!');
  else toast.error('Simulasi gagal: ' + store.error);
};

const triggerSimulasiPakan = async () => {
  await store.simulasiPakan();
  if (!store.error) toast.success('Simulasi pemberian pakan berhasil!');
  else toast.error('Simulasi gagal: ' + store.error);
};

const triggerSimulasiStokMenipis = async () => {
  await store.simulasiStokMenipis();
  if (!store.error) toast.warning('Simulasi stok pakan menipis dipicu!');
  else toast.error('Simulasi gagal: ' + store.error);
};

const triggerSimulasiAutoCut = async () => {
  await store.simulasiAutoCut();
  if (!store.error) toast.success('Simulasi Auto-Cut motor auger dipicu!');
  else toast.error('Simulasi gagal: ' + store.error);
};

// --- CRUD MODAL OPERATIONS ---
const openCreateModal = (type: 'jadwal' | 'stok' | 'log' | 'status') => {
  activeModalType.value = type;
  editingId.value = null;
  
  if (type === 'jadwal') {
    formJadwal.value = { waktu_pakan: '', fase_umur: 'Grower', target_pakan_gram: 1500, durasi_motor_detik: 30, status_aktif: true, target_otomatis: true };
  } else if (type === 'stok') {
    formStok.value = { berat_pakan_gram: 4000, persentase_stok: 65, status_stok: 'aman', waktu_pembacaan: formatDatetimeLocal(new Date().toISOString()) };
  } else if (type === 'log') {
    formLog.value = { jadwal_pakan_id: null, sumber: 'manual', waktu_mulai: formatDatetimeLocal(new Date().toISOString()), waktu_selesai: formatDatetimeLocal(new Date().toISOString()), durasi_motor_detik: 15, jumlah_pakan_keluar_gram: 500, status: 'berhasil', keterangan: 'Log manual didaftarkan lewat panel' };
  } else if (type === 'status') {
    formStatus.value = { nama_perangkat: 'ESP32-Feeder', status_koneksi: 'online', status_motor: 'mati', status_sensor: 'normal', mode_operasi: 'otomatis', terakhir_online: formatDatetimeLocal(new Date().toISOString()) };
  }
  showCrudModal.value = true;
};

const openEditModal = (type: 'jadwal' | 'stok' | 'log' | 'status', item: any) => {
  activeModalType.value = type;
  editingId.value = item.id;

  if (type === 'jadwal') {
    formJadwal.value = { waktu_pakan: item.waktu_pakan, fase_umur: item.fase_umur, target_pakan_gram: item.target_pakan_gram, durasi_motor_detik: item.durasi_motor_detik, status_aktif: !!item.status_aktif, target_otomatis: !!item.target_otomatis };
  } else if (type === 'stok') {
    formStok.value = { berat_pakan_gram: item.berat_pakan_gram, persentase_stok: item.persentase_stok, status_stok: item.status_stok, waktu_pembacaan: formatDatetimeLocal(item.waktu_pembacaan) };
  } else if (type === 'log') {
    formLog.value = { jadwal_pakan_id: item.jadwal_pakan_id, sumber: item.sumber, waktu_mulai: formatDatetimeLocal(item.waktu_mulai), waktu_selesai: formatDatetimeLocal(item.waktu_selesai), durasi_motor_detik: item.durasi_motor_detik, jumlah_pakan_keluar_gram: item.jumlah_pakan_keluar_gram, status: item.status, keterangan: item.keterangan || '' };
  } else if (type === 'status') {
    formStatus.value = { nama_perangkat: item.nama_perangkat, status_koneksi: item.status_koneksi, status_motor: item.status_motor, status_sensor: item.status_sensor, mode_operasi: item.mode_operasi, terakhir_online: formatDatetimeLocal(item.terakhir_online) };
  }
  showCrudModal.value = true;
};

const handleFormSubmit = async () => {
  try {
    const type = activeModalType.value;
    const id = editingId.value;

    if (type === 'jadwal') {
      const payload = { 
        ...formJadwal.value,
        target_otomatis: true,
        target_pakan_gram: null,
        durasi_motor_detik: null
      };
      if (id) await store.updateJadwal(id, payload);
      else await store.tambahJadwal(payload);
    } else if (type === 'stok') {
      if (id) await store.updateStok(id, formStok.value);
      else await store.tambahStok(formStok.value);
    } else if (type === 'log') {
      if (id) await store.updateLog(id, formLog.value);
      else await store.tambahLog(formLog.value);
    } else if (type === 'status') {
      if (id) await store.updateStatus(id, formStatus.value);
      else await store.tambahStatus(formStatus.value);
    }

    toast.success('Penyimpanan data berhasil dilakukan!');
    showCrudModal.value = false;
  } catch (err: any) {
    const errorMsg = err.response?.data?.message || 'Validasi gagal, silakan periksa input data Anda.';
    toast.error(errorMsg);
  }
};

const handleDeleteItem = async (type: 'jadwal' | 'stok' | 'log' | 'status', id: number) => {
  if (confirm('Apakah Anda yakin ingin menghapus data ini secara permanen?')) {
    try {
      if (type === 'jadwal') await store.hapusJadwal(id);
      else if (type === 'stok') await store.hapusStok(id);
      else if (type === 'log') await store.hapusLog(id);
      else if (type === 'status') await store.hapusStatus(id);
      toast.success('Data berhasil dihapus!');
    } catch (err: any) {
      toast.error('Gagal menghapus data.');
    }
  }
};

// Toggle status switch in list view directly
const handleToggleJadwal = async (item: any) => {
  try {
    await store.updateJadwal(item.id, { ...item, status_aktif: !item.status_aktif });
    toast.success('Status aktif jadwal berhasil diperbarui!');
  } catch (err) {
    toast.error('Gagal memperbarui status aktif.');
  }
};

// Toggle motor status manually
const toggleMotorStatus = async (item: any) => {
  const newStatus = item.status_motor === 'aktif' ? 'mati' : 'aktif';
  try {
    const response = await api.put(`/status-alat/${item.id}`, {
      status_motor: newStatus,
    });
    if (response.data?.status) {
      toast.success(`Motor berhasil diubah menjadi ${newStatus}!`);
      fetchDashboard();
    } else {
      toast.error('Gagal memperbarui status motor.');
    }
  } catch (err) {
    toast.error('Gagal menghubungi server.');
  }
};

const toggleGlobalMotor = async () => {
    if (!confirm('Apakah anda yakin akan menyalakan conveyor secara manual?')) {
        return;
    }
    const item = store.dashboard.status_alat || (paginatedStatus.value.length > 0 ? paginatedStatus.value[0] : null);
    if (!item) {
        toast.error('Perangkat IoT tidak ditemukan.');
        return;
    }
    await toggleMotorStatus(item);
};

// --- DETAIL MODAL OVERLAYS ---
const openDetail = async (type: 'jadwal' | 'status', item: any) => {
  detailType.value = type;
  detailItem.value = item;
  showDetailModal.value = true;
  loadingDetail.value = true;

  try {
    if (type === 'jadwal') {
      const response = await api.get(`/jadwal-pakan/${item.id}`);
      if (response.data?.status) {
        detailItem.value = response.data.data;
        detailHistory.value = response.data.data.log_pemberian_pakan || [];
      }
    } else if (type === 'status') {
      const response = await api.get(`/status-alat/${item.id}`);
      if (response.data?.status) {
        detailItem.value = response.data.data.status_alat;
        detailHistory.value = response.data.data.histori_koneksi || [];
        detailSensorHistory.value = response.data.data.histori_sensor || [];
      }
    }
  } catch (err) {
    console.error(err);
    toast.error('Gagal memuat detail data.');
  } finally {
    loadingDetail.value = false;
  }
};

// Axios import from services
import api from '../services/api';
</script>

<template>
  <!-- Main layout and sidebar wrapped inside AppSidebarLayout -->
  <Head title="Monitoring Kandang" />

  <div class="space-y-6 p-6">
    <!-- Topbar info -->
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
          <span>Kandang Ayam Feeder IoT</span>
          <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
          Umpan Balik Cepat & Monitoring Pakan Real-Time
        </p>
      </div>

      <!-- User info & simple Logout -->
      <div class="flex items-center gap-4">
        <!-- Live Connection status indicator -->
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 shadow-xs">
          <Wifi class="h-4 w-4" :class="store.dashboard.status_alat?.status_koneksi === 'online' ? 'text-emerald-500' : 'text-rose-500'" />
          <span>Sistem: {{ store.dashboard.status_alat?.status_koneksi === 'online' ? 'Online' : 'Offline' }}</span>
        </div>
        
        <Link 
          href="/logout" 
          method="post" 
          as="button" 
          class="flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50/50 hover:bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-600 transition-all dark:border-rose-955/20 dark:bg-rose-955/10 dark:text-rose-455"
        >
          <LogOut class="h-3.5 w-3.5" />
          Logout
        </Link>
      </div>
    </div>

    <!-- 1. KPI Cards Row (Always shown for instant analytics readout) -->
    <div v-if="currentTab === 'stok'" class="grid gap-6 sm:grid-cols-3 lg:grid-cols-3 animate-fade-in">
      <StatCard
        title="Stok Pakan di Gudang"
        :value="store.dashboard.stok_pakan_terbaru ? formatGramToKg(store.dashboard.stok_pakan_terbaru.berat_gudang_gram) : '0 Kg'"
        :subtitle="'Cadangan' + (store.dashboard.ramalan_stok?.sisa_hari_gudang !== undefined ? ` (Cukup ~${store.dashboard.ramalan_stok.sisa_hari_gudang} hari)` : '')"
        :icon="Database"
        color-class="from-indigo-500/10 to-blue-500/10 border-indigo-500/20 text-indigo-600 dark:text-indigo-400"
        :loading="store.loading && !store.dashboard.stok_pakan_terbaru"
      />

      <StatCard
        title="Stok Pakan di Silo"
        :value="store.dashboard.stok_pakan_terbaru ? `${store.dashboard.stok_pakan_terbaru.persentase_stok}%` : '0%'"
        :subtitle="formatGramToKg(store.dashboard.stok_pakan_terbaru?.berat_pakan_gram) + ' Tersisa' + (store.dashboard.ramalan_stok?.sisa_hari_silo !== undefined ? ` (Cukup ~${store.dashboard.ramalan_stok.sisa_hari_silo} hari)` : '')"
        :icon="Database"
        :trend="store.dashboard.stok_pakan_terbaru ? {
          value: store.dashboard.stok_pakan_terbaru.status_stok === 'aman' ? 'Aman' : 'Hampir Habis',
          type: store.dashboard.stok_pakan_terbaru.status_stok === 'aman' ? 'up' : 'down'
        } : undefined"
        color-class="from-emerald-500/10 to-teal-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400"
        :loading="store.loading && !store.dashboard.stok_pakan_terbaru"
      />

      <StatCard
        title="Umpan Pakan Hari Ini"
        :value="formatGramToKg(store.dashboard.total_pakan_hari_ini)"
        subtitle="Total porsi pakan keluar"
        :icon="TrendingUp"
        color-class="from-indigo-500/10 to-purple-500/10 border-indigo-500/20 text-indigo-600 dark:text-indigo-400"
        :loading="store.loading"
      />
    </div>

    <div v-else-if="currentTab !== 'status'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        title="Persentase Stok Silo"
        :value="store.dashboard.stok_pakan_terbaru ? `${store.dashboard.stok_pakan_terbaru.persentase_stok}%` : '0%'"
        :subtitle="formatGramToKg(store.dashboard.stok_pakan_terbaru?.berat_pakan_gram) + ' Tersisa' + (store.dashboard.ramalan_stok?.sisa_hari_silo !== undefined ? ` (Cukup ~${store.dashboard.ramalan_stok.sisa_hari_silo} hari)` : '')"
        :icon="Database"
        :trend="store.dashboard.stok_pakan_terbaru ? {
          value: store.dashboard.stok_pakan_terbaru.status_stok === 'aman' ? 'Aman' : 'Hampir Habis',
          type: store.dashboard.stok_pakan_terbaru.status_stok === 'aman' ? 'up' : 'down'
        } : undefined"
        color-class="from-emerald-500/10 to-teal-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400"
        :loading="store.loading && !store.dashboard.stok_pakan_terbaru"
      />

      <StatCard
        title="Koneksi Alat"
        :value="store.dashboard.status_alat?.status_koneksi === 'online' ? 'Terhubung' : 'Terputus'"
        :subtitle="store.dashboard.status_alat ? `Motor: ${store.dashboard.status_alat.status_motor} | Sensor: ${store.dashboard.status_alat.status_sensor}` : 'Tidak ada telemetri'"
        :icon="Cpu"
        :trend="store.dashboard.status_alat ? {
          value: store.dashboard.status_alat.mode_operasi === 'otomatis' ? 'Otomatis' : 'Manual',
          type: store.dashboard.status_alat.status_koneksi === 'online' ? 'up' : 'neutral'
        } : undefined"
        color-class="from-blue-500/10 to-indigo-500/10 border-blue-500/20 text-blue-600 dark:text-blue-400"
        :loading="store.loading && !store.dashboard.status_alat"
      />

      <StatCard
        title="Jadwal Pakan Terdekat"
        :value="formatTime(store.dashboard.jadwal_pakan_berikutnya?.waktu_pakan)"
        :subtitle="store.dashboard.jadwal_pakan_berikutnya ? `${store.dashboard.jadwal_pakan_berikutnya.fase_umur} | ${formatGramToKg(store.dashboard.jadwal_pakan_berikutnya.target_pakan_gram)}` : 'Tidak ada jadwal'"
        :icon="Clock"
        color-class="from-amber-500/10 to-orange-500/10 border-amber-500/20 text-amber-600 dark:text-amber-400"
        :loading="store.loading && !store.dashboard.jadwal_pakan_berikutnya"
      />

      <StatCard
        title="Umpan Pakan Hari Ini"
        :value="formatGramToKg(store.dashboard.total_pakan_hari_ini)"
        subtitle="Total porsi pakan keluar"
        :icon="TrendingUp"
        color-class="from-indigo-500/10 to-purple-500/10 border-indigo-500/20 text-indigo-600 dark:text-indigo-400"
        :loading="store.loading"
      />
    </div>

    <!-- ==================== TAB 1: MAIN DASHBOARD ==================== -->
    <div v-if="currentTab === 'dashboard'" class="space-y-6">
      <!-- ApexCharts Graphs -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Stock Area Chart -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Grafik Pemantauan Stok Silo</h3>
              <p class="text-2xs text-slate-500">Histori persentase level pakan silo</p>
            </div>
            <Activity class="h-4 w-4 text-emerald-500" />
          </div>
          <div class="h-64">
            <!-- Area Chart Skeleton -->
            <div v-if="store.loading && store.stokChartData.length === 0" class="h-full flex flex-col justify-end gap-3 pb-2 animate-pulse">
              <div class="flex items-end gap-2.5 h-44 px-2">
                <div class="bg-emerald-100/50 dark:bg-emerald-950/20 rounded-md w-full h-[20%]"></div>
                <div class="bg-emerald-100/50 dark:bg-emerald-950/20 rounded-md w-full h-[40%]"></div>
                <div class="bg-emerald-200/50 dark:bg-emerald-900/30 rounded-md w-full h-[35%]"></div>
                <div class="bg-emerald-100/50 dark:bg-emerald-950/20 rounded-md w-full h-[60%]"></div>
                <div class="bg-emerald-200/50 dark:bg-emerald-900/30 rounded-md w-full h-[75%]"></div>
                <div class="bg-emerald-300/50 dark:bg-emerald-800/40 rounded-md w-full h-[90%]"></div>
              </div>
              <div class="h-3 bg-slate-100 dark:bg-slate-900 rounded-md w-full"></div>
            </div>
            <apexchart 
              v-else-if="store.stokChartData.length > 0" 
              type="area" 
              height="250" 
              :options="stokChartOptions" 
              :series="stokChartSeries" 
            />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-xs">
              Belum ada pembacaan grafik stok pakan.
            </div>
          </div>
        </div>

        <!-- Feeding Bar Chart -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Aktivitas Pemberian Pakan Harian</h3>
              <p class="text-2xs text-slate-500">Volume berat pakan keluar 7 hari terakhir</p>
            </div>
            <TrendingUp class="h-4 w-4 text-indigo-500" />
          </div>
          <div class="h-64">
            <!-- Bar Chart Skeleton -->
            <div v-if="store.loading && store.logChartData.length === 0" class="h-full flex flex-col justify-end gap-3 pb-2 animate-pulse">
              <div class="flex items-end gap-3 h-44 px-4">
                <div class="bg-indigo-100/50 dark:bg-indigo-950/20 rounded-md w-full h-[30%]"></div>
                <div class="bg-indigo-150/50 dark:bg-indigo-900/20 rounded-md w-full h-[15%]"></div>
                <div class="bg-indigo-200/50 dark:bg-indigo-900/30 rounded-md w-full h-[55%]"></div>
                <div class="bg-indigo-150/50 dark:bg-indigo-900/20 rounded-md w-full h-[40%]"></div>
                <div class="bg-indigo-200/50 dark:bg-indigo-900/30 rounded-md w-full h-[70%]"></div>
                <div class="bg-indigo-250/50 dark:bg-indigo-850/40 rounded-md w-full h-[50%]"></div>
                <div class="bg-indigo-300/50 dark:bg-indigo-800/40 rounded-md w-full h-[85%]"></div>
              </div>
              <div class="h-3 bg-slate-100 dark:bg-slate-900 rounded-md w-full"></div>
            </div>
            <apexchart 
              v-else-if="store.logChartData.length > 0" 
              type="bar" 
              height="250" 
              :options="logChartOptions" 
              :series="logChartSeries" 
            />
            <div v-else class="h-full flex items-center justify-center text-slate-400 text-xs">
              Belum ada log aktivitas pakan mingguan.
            </div>
          </div>
        </div>
      </div>

      <!-- Telemetry Simulator & Recent Logs -->
      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Recent Activities Feed (Col span 2) -->
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-md font-bold text-slate-850 dark:text-slate-100">Riwayat Pemberian Pakan Terbaru</h3>
            <button 
              @click="loadAllData" 
              class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-2xs font-semibold text-slate-650 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-350 dark:hover:bg-slate-850"
            >
              <RefreshCw class="h-3 w-3" />
              Refresh
            </button>
          </div>

          <DataTable :columns="logColumns" :items="store.dashboard.log_terbaru" :loading="store.loading">
            <template #waktu_mulai="{ item }">
              <span class="font-medium text-slate-800 dark:text-slate-200 text-xs">{{ formatDate(item.waktu_mulai) }}</span>
            </template>
            <template #sumber="{ item }">
              <span class="capitalize text-xs">{{ item.sumber }}</span>
            </template>
            <template #durasi_motor_detik="{ item }">
              <span class="text-xs">{{ item.durasi_motor_detik }} dtk</span>
            </template>
            <template #jumlah_pakan_keluar_gram="{ item }">
              <span class="font-semibold text-xs">{{ formatGramToKg(item.jumlah_pakan_keluar_gram) }}</span>
            </template>
            <template #status="{ item }">
              <StatusBadge :status="item.status" />
            </template>
            <template #keterangan="{ item }">
              <span class="text-2xs text-slate-550 dark:text-slate-450 block truncate max-w-[150px]">{{ item.keterangan || '-' }}</span>
            </template>
          </DataTable>
        </div>

        <!-- Simulations Control Panel (Col span 1) -->
        <div class="space-y-4">
          <!-- Active Period Card -->
          <h3 class="text-md font-bold text-slate-850 dark:text-slate-100">Periode Pemeliharaan</h3>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-950 space-y-4">
            <div v-if="store.dashboard.periode_aktif" class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-850 dark:text-slate-200">
                  {{ store.dashboard.periode_aktif.nama_periode }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                  Aktif
                </span>
              </div>
              
              <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                  <span class="text-[9px] font-mono text-slate-400 block mb-0.5">Umur Ayam</span>
                  <span class="font-bold text-slate-800 dark:text-slate-250">{{ store.dashboard.umur_hari }} hari</span>
                </div>
                <div class="p-2 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                  <span class="text-[9px] font-mono text-slate-400 block mb-0.5">Fase Umur</span>
                  <span class="font-bold text-slate-800 dark:text-slate-250">{{ store.dashboard.fase_umur }}</span>
                </div>
              </div>

              <div class="pt-2">
                <Link 
                  href="/periode-pemeliharaan"
                  class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-650 hover:bg-indigo-700 text-white px-4 py-2 text-xs font-bold shadow-xs transition-all cursor-pointer"
                >
                  <Settings class="h-3.5 w-3.5" />
                  Kelola Batch Pemeliharaan
                </Link>
              </div>
            </div>
            
            <div v-else class="text-center py-4 space-y-3">
              <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Tidak ada periode pemeliharaan aktif</p>
              <Link 
                href="/periode-pemeliharaan" 
                class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-650 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-all cursor-pointer"
              >
                Mulai Periode Baru
              </Link>
            </div>
          </div>

          <h3 class="text-md font-bold text-slate-850 dark:text-slate-100">Kontrol Simulasi IoT</h3>
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-950 space-y-3">
            <p class="text-2xs text-slate-500 leading-relaxed">
              Mensimulasikan pembacaan telemetri sensor ESP32 untuk pemaparan demo sistem otomatisasi pakan.
            </p>

            <div class="grid gap-2.5">
              <button @click="triggerSimulasiKirimData" class="w-full flex items-center justify-between text-left p-3 border border-slate-100 dark:border-slate-900 bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-900/40 dark:hover:bg-slate-900/60 rounded-xl transition-all">
                <div class="flex items-center gap-2.5">
                  <div class="p-2 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-lg"><Send class="h-3.5 w-3.5" /></div>
                  <div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Kirim Telemetri</div>
                    <div class="text-3xs text-slate-500">Kirim telemetri update sensor acak</div>
                  </div>
                </div>
                <ChevronRight class="h-3.5 w-3.5 text-slate-400" />
              </button>

              <button @click="triggerSimulasiPakan" class="w-full flex items-center justify-between text-left p-3 border border-slate-100 dark:border-slate-900 bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-900/40 dark:hover:bg-slate-900/60 rounded-xl transition-all">
                <div class="flex items-center gap-2.5">
                  <div class="p-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg"><Play class="h-3.5 w-3.5" /></div>
                  <div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Simulasi Beri Pakan</div>
                    <div class="text-3xs text-slate-500">Luncurkan motor pengumpan auger</div>
                  </div>
                </div>
                <ChevronRight class="h-3.5 w-3.5 text-slate-400" />
              </button>

              <button @click="triggerSimulasiStokMenipis" class="w-full flex items-center justify-between text-left p-3 border border-slate-100 dark:border-slate-900 bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-900/40 dark:hover:bg-slate-900/60 rounded-xl transition-all">
                <div class="flex items-center gap-2.5">
                  <div class="p-2 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-lg"><AlertTriangle class="h-3.5 w-3.5" /></div>
                  <div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Simulasi Stok Kritis</div>
                    <div class="text-3xs text-slate-500">Turunkan persentase stok silo (15%)</div>
                  </div>
                </div>
                <ChevronRight class="h-3.5 w-3.5 text-slate-400" />
              </button>

              <button @click="triggerSimulasiAutoCut" class="w-full flex items-center justify-between text-left p-3 border border-slate-100 dark:border-slate-900 bg-slate-50/50 hover:bg-slate-50 dark:bg-slate-900/40 dark:hover:bg-slate-900/60 rounded-xl transition-all">
                <div class="flex items-center gap-2.5">
                  <div class="p-2 bg-rose-500/10 text-rose-600 dark:text-rose-400 rounded-lg"><Zap class="h-3.5 w-3.5" /></div>
                  <div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Pemicu Auto-Cut</div>
                    <div class="text-3xs text-slate-500">Hentikan auger karena porsi penuh</div>
                  </div>
                </div>
                <ChevronRight class="h-3.5 w-3.5 text-slate-400" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== TAB 2: STOK PAKAN (CRUD) ==================== -->
    <div v-else-if="currentTab === 'stok'" class="space-y-6">
      
      <!-- Card Peramalan Stok Pakan -->
      <div class="grid gap-6 md:grid-cols-3">
        <!-- Panel Utama Peramalan -->
        <div v-if="false" class="md:col-span-2 rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <TrendingUp class="h-4 w-4 text-indigo-500" />
                Analisis & Peramalan Sisa Stok Pakan
              </h3>
              <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-3xs font-semibold text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400">
                Prediksi IoT Dinamis
              </span>
            </div>
            
            <div v-if="store.ramalanStok" class="grid gap-6 sm:grid-cols-2">
              <div class="space-y-4">
                <!-- Forecast Silo -->
                <div class="p-3 bg-emerald-50/30 dark:bg-emerald-950/10 rounded-2xl border border-emerald-100/50 dark:border-emerald-900/30 border-emerald-250">
                  <span class="text-3xs font-bold text-emerald-800 dark:text-emerald-400 uppercase tracking-wider block">Prediksi Pakan di Silo</span>
                  <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                      {{ store.ramalanStok.sisa_hari_silo }}
                    </span>
                    <span class="text-2xs font-semibold text-slate-600 dark:text-slate-400">Hari ke Depan</span>
                  </div>
                  <p class="text-3xs text-slate-400 mt-1.5 leading-normal">
                    Habis: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ store.ramalanStok.silo?.tanggal_habis_formatted }}</span>
                  </p>
                </div>

                <!-- Forecast Gudang -->
                <div class="p-3 bg-indigo-50/30 dark:bg-indigo-950/10 rounded-2xl border border-indigo-100/50 dark:border-indigo-900/30 border-indigo-250">
                  <span class="text-3xs font-bold text-indigo-800 dark:text-indigo-400 uppercase tracking-wider block">Prediksi Pakan di Gudang</span>
                  <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">
                      {{ store.ramalanStok.sisa_hari_gudang }}
                    </span>
                    <span class="text-2xs font-semibold text-slate-600 dark:text-slate-400">Hari ke Depan</span>
                  </div>
                  <p class="text-3xs text-slate-400 mt-1.5 leading-normal">
                    Habis: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ store.ramalanStok.gudang?.tanggal_habis_formatted }}</span>
                  </p>
                </div>
              </div>

              <div class="space-y-4 border-l border-slate-100 pl-6 dark:border-slate-800/80 flex flex-col justify-center">
                <div>
                  <span class="text-3xs font-semibold text-slate-400 uppercase tracking-wider block">Informasi Kandang</span>
                  <div class="mt-2 space-y-1.5">
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                      Populasi Ayam: <span class="font-bold text-slate-800 dark:text-slate-200">{{ store.ramalanStok.jumlah_ayam }} ekor</span>
                    </p>
                    <p class="text-xs text-slate-600 dark:text-slate-300">
                      Umur Ayam: <span class="font-bold text-slate-800 dark:text-slate-200">{{ store.ramalanStok.umur_sekarang }} hari</span>
                    </p>
                    <p class="text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                      Fase Umur: 
                      <span class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100/50 dark:border-indigo-900/30 px-1.5 py-0.5 text-3xs font-medium text-indigo-600 dark:text-indigo-400">
                        {{ store.ramalanStok.fase_sekarang }}
                      </span>
                    </p>
                  </div>
                </div>
                <p class="text-3xs text-slate-400 leading-normal">
                  *Prediksi dihitung real-time berdasarkan grafik kenaikan kebutuhan pakan harian dinamis per fase umur.
                </p>
              </div>
            </div>
            
            <div v-else class="flex h-32 items-center justify-center text-slate-400 text-xs">
              Sedang memuat analisis stok pakan...
            </div>
          </div>
          
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/80">
            <h4 class="text-2xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Penjelasan Rumus Prediksi:</h4>
            <p class="text-3xs text-slate-500 leading-relaxed">
              Sistem menggunakan metode proyeksi harian dinamis (<strong>Day-by-Day Dynamic Projection</strong>). Sisa hari dihitung dengan mensimulasikan konsumsi pakan hari demi hari:
              <code class="block bg-slate-50 dark:bg-slate-900 p-1.5 rounded-lg mt-1 text-slate-700 dark:text-slate-300">
                Sisa Hari = Hari Penuh + (Sisa Pakan Hari Terakhir / Kebutuhan Hari Terakhir)
              </code>
              Setiap harinya, kebutuhan konsumsi pakan dihitung berdasarkan rumus: 
              <code class="block bg-slate-50 dark:bg-slate-900 p-1.5 rounded-lg mt-1 text-slate-700 dark:text-slate-300">
                Kebutuhan Harian = Jumlah Ayam × Gram Pakan per Ekor (berdasarkan Fase Umur)
              </code>
            </p>
          </div>
        </div>

        <!-- Tabel Parameter Master & Rincian -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950 flex flex-col justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 mb-3">
              <Database class="h-4 w-4 text-emerald-500" />
              Master Parameter Pakan
            </h3>
            
            <div class="space-y-2 text-2xs">
              <div v-if="!store.dashboard.kebutuhan_pakan_master || store.dashboard.kebutuhan_pakan_master.length === 0" class="text-3xs text-slate-400 p-4 text-center">
                Tidak ada data parameter pakan.
              </div>
              <div 
                v-else
                v-for="(item, index) in store.dashboard.kebutuhan_pakan_master" 
                :key="item.id"
                class="flex justify-between items-center p-2 rounded-xl border transition-all hover:scale-[1.01]"
                :class="[
                  index === 0 ? 'bg-emerald-50/50 dark:bg-emerald-950/20 border-emerald-100/50 dark:border-emerald-900/30 border-emerald-200' : '',
                  index === 1 ? 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-100/50 dark:border-blue-900/30 border-blue-200' : '',
                  index === 2 ? 'bg-amber-50/50 dark:bg-amber-950/20 border-amber-100/50 dark:border-amber-900/30 border-amber-200' : '',
                  index > 2 ? 'bg-purple-50/50 dark:bg-purple-950/20 border-purple-100/50 dark:border-purple-900/30 border-purple-200' : ''
                ]"
              >
                <div>
                  <div class="font-bold" :class="[
                    index === 0 ? 'text-emerald-800 dark:text-emerald-400' : '',
                    index === 1 ? 'text-blue-800 dark:text-blue-400' : '',
                    index === 2 ? 'text-amber-800 dark:text-amber-400' : '',
                    index > 2 ? 'text-purple-800 dark:text-purple-400' : ''
                  ]">{{ item.fase_umur }} (Umur {{ item.umur_mulai_hari }}-{{ item.umur_selesai_hari }})</div>
                  <div class="text-3xs text-slate-500">{{ item.gram_per_ekor_per_hari }} gram / ekor / hari</div>
                </div>
                <div class="text-xs font-extrabold" :class="[
                  index === 0 ? 'text-emerald-600 dark:text-emerald-400' : '',
                  index === 1 ? 'text-blue-600 dark:text-blue-400' : '',
                  index === 2 ? 'text-amber-600 dark:text-amber-400' : '',
                  index > 2 ? 'text-purple-600 dark:text-purple-400' : ''
                ]">{{ item.gram_per_ekor_per_hari }}g</div>
              </div>
            </div>
          </div>
          
          <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/80">
            <div class="flex items-center gap-2 text-3xs text-slate-500">
              <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
              <span>Kebutuhan pakan otomatis menyesuaikan saat ayam bertumbuh.</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel Rincian Proyeksi Harian (jika ada data) -->
      <div v-if="store.ramalanStok && ((activeProyeksiTab === 'silo' && store.ramalanStok.silo?.detail_harian?.length > 0) || (activeProyeksiTab === 'gudang' && store.ramalanStok.gudang?.detail_harian?.length > 0))" class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
          <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <Clock class="h-4 w-4 text-indigo-500" />
            Detail Proyeksi Konsumsi Harian
          </h3>
          <!-- Toggle Button Group -->
          <div class="flex rounded-xl bg-slate-100 p-0.5 dark:bg-slate-900 border border-slate-200/50 dark:border-slate-800 text-3xs font-semibold">
            <button 
              @click="activeProyeksiTab = 'silo'; currentPageProyeksi = 1;"
              :class="[
                activeProyeksiTab === 'silo' 
                  ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-800 dark:text-indigo-400' 
                  : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
              ]"
              class="rounded-lg px-3 py-1 cursor-pointer transition-all"
            >
              Stok di Silo
            </button>
            <button 
              @click="activeProyeksiTab = 'gudang'; currentPageProyeksi = 1;"
              :class="[
                activeProyeksiTab === 'gudang' 
                  ? 'bg-white text-indigo-600 shadow-xs dark:bg-slate-800 dark:text-indigo-400' 
                  : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
              ]"
              class="rounded-lg px-3 py-1 cursor-pointer transition-all"
            >
              Stok di Gudang
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-100 dark:border-slate-800 text-3xs uppercase tracking-wider text-slate-450">
                <th class="py-2.5 px-3">Tanggal / Hari</th>
                <th class="py-2.5 px-3">Umur Ayam</th>
                <th class="py-2.5 px-3">Fase</th>
                <th class="py-2.5 px-3 text-right">Kebutuhan Harian</th>
                <th class="py-2.5 px-3 text-right">Sisa Stok Akhir Hari</th>
                <th class="py-2.5 px-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100/60 dark:divide-slate-900/60 text-2xs">
              <tr v-for="hari in paginatedProyeksi" :key="hari.hari_ke" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/40">
                <td class="py-2.5 px-3 font-medium text-slate-800 dark:text-slate-200">
                  {{ hari.hari_formatted }}
                </td>
                <td class="py-2.5 px-3">
                  {{ hari.umur_ayam }} hari
                </td>
                <td class="py-2.5 px-3">
                  <span :class="{
                    'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-950/20': hari.fase_umur === 'Starter',
                    'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-950/20': hari.fase_umur === 'Grower',
                    'text-amber-600 bg-amber-50 dark:text-amber-400 dark:bg-amber-950/20': hari.fase_umur === 'Finisher'
                  }" class="px-2 py-0.5 rounded text-3xs font-bold">
                    {{ hari.fase_umur }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right font-semibold">
                  {{ formatGramToKg(hari.kebutuhan_harian_flock_gram) }}
                </td>
                <td class="py-2.5 px-3 text-right font-medium text-slate-500">
                  {{ hari.stok_tersisa_akhir_hari_gram > 0 ? formatGramToKg(hari.stok_tersisa_akhir_hari_gram) : 'Habis' }}
                </td>
                <td class="py-2.5 px-3 text-center">
                  <span v-if="hari.cukup_penuh" class="inline-flex items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.5 text-3xs font-medium text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                    Cukup Penuh
                  </span>
                  <span v-else class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-3xs font-medium text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">
                    Habis Terpakai ({{ hari.persentase_terpakai_hari_ini }}%)
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls for Daily Proyeksi -->
        <div v-if="totalPagesProyeksi > 1" class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3.5 dark:border-slate-800">
          <div class="text-3xs text-slate-500">
            Menampilkan halaman {{ currentPageProyeksi }} dari {{ totalPagesProyeksi }} (Total {{ activeProyeksiTab === 'silo' ? store.ramalanStok?.silo?.detail_harian?.length : store.ramalanStok?.gudang?.detail_harian?.length }} hari proyeksi)
          </div>
          <div class="flex items-center gap-1">
            <button 
              @click="currentPageProyeksi = Math.max(1, currentPageProyeksi - 1)"
              :disabled="currentPageProyeksi === 1"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-3xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:text-slate-350 cursor-pointer"
            >
              Sebelumnya
            </button>
            
            <button 
              v-for="page in totalPagesProyeksi" 
              :key="page"
              @click="currentPageProyeksi = page"
              :class="[
                currentPageProyeksi === page 
                  ? 'bg-indigo-600 text-white border-indigo-600' 
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-350'
              ]"
              class="rounded-lg border px-2.5 py-1 text-3xs font-semibold cursor-pointer"
            >
              {{ page }}
            </button>

            <button 
              @click="currentPageProyeksi = Math.min(totalPagesProyeksi, currentPageProyeksi + 1)"
              :disabled="currentPageProyeksi === totalPagesProyeksi"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-3xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 disabled:hover:bg-white dark:border-slate-800 dark:bg-slate-950 dark:text-slate-350 cursor-pointer"
            >
              Selanjutnya
            </button>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-4">
        <h2 class="text-lg font-bold text-slate-850 dark:text-slate-100">Riwayat Catatan Volume Silo</h2>
        
        <div class="flex items-center gap-2.5">
          <!-- Search box -->
          <div class="relative w-48 sm:w-64">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input 
              type="text" 
              v-model="queryStok" 
              placeholder="Cari status / berat..." 
              class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" 
            />
          </div>
          <!-- Create button -->
          <button @click="openCreateModal('stok')" class="flex items-center gap-1 rounded-xl bg-indigo-650 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-all">
            <Plus class="h-3.5 w-3.5" />
            Tambah Catatan
          </button>
        </div>
      </div>

      <DataTable :columns="stokColumns" :items="paginatedStok" :loading="store.loading">
        <template #waktu_pembacaan="{ item }">
          <span class="font-medium text-slate-800 dark:text-slate-200 text-xs">{{ formatDate(item.waktu_pembacaan) }}</span>
        </template>
        <template #berat_pakan_gram="{ item }">
          <span class="font-bold text-xs">{{ formatGramToKg(item.berat_pakan_gram) }}</span>
        </template>
        <template #persentase_stok="{ item }">
          <div class="flex items-center gap-2">
            <div class="w-16 bg-slate-200 dark:bg-slate-800 rounded-full h-1.5">
              <div class="bg-emerald-500 h-1.5 rounded-full" :style="{ width: `${item.persentase_stok}%` }"></div>
            </div>
            <span class="text-xs">{{ item.persentase_stok }}%</span>
          </div>
        </template>
        <template #status_stok="{ item }">
          <StatusBadge :status="item.status_stok" />
        </template>
        <template #aksi="{ item }">
          <div class="flex justify-end gap-1.5">
            <button @click="openEditModal('stok', item)" class="rounded-lg p-1 border border-slate-250 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-400">
              <Edit2 class="h-3.5 w-3.5" />
            </button>
            <button @click="handleDeleteItem('stok', item.id)" class="rounded-lg p-1 border border-rose-250 hover:bg-rose-50 dark:border-rose-955/20 dark:hover:bg-rose-955/10 text-rose-600 dark:text-rose-455">
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Pagination Footer -->
      <div v-if="totalPagesStok > 1" class="flex items-center justify-between pt-2">
        <span class="text-2xs text-slate-500">Halaman {{ pageStok }} dari {{ totalPagesStok }} ({{ filteredStok.length }} data)</span>
        <div class="flex gap-1">
          <button :disabled="pageStok === 1" @click="pageStok--" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronLeft class="h-4 w-4" /></button>
          <button :disabled="pageStok === totalPagesStok" @click="pageStok++" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronRight class="h-4 w-4" /></button>
        </div>
      </div>
    </div>

    <!-- ==================== TAB 3: JADWAL PAKAN (CRUD & DETAIL) ==================== -->
    <div v-else-if="currentTab === 'jadwal'" class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-lg font-bold text-slate-850 dark:text-slate-100">Daftar Jadwal Pengumpan Otomatis</h2>
        
        <div class="flex items-center gap-2.5">
          <!-- Search box -->
          <div class="relative w-48 sm:w-64">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input 
              type="text" 
              v-model="queryJadwal" 
              placeholder="Cari fase / jam..." 
              class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" 
            />
          </div>
          <!-- Create button -->
          <button @click="openCreateModal('jadwal')" class="flex items-center gap-1 rounded-xl bg-indigo-650 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-all">
            <Plus class="h-3.5 w-3.5" />
            Tambah Jadwal
          </button>
        </div>
      </div>

      <DataTable :columns="jadwalColumns" :items="paginatedJadwal" :loading="store.loading">
        <template #waktu_pakan="{ item }">
          <div class="flex items-center gap-2 text-xs">
            <Clock class="h-3.5 w-3.5 text-slate-400" />
            <span class="font-bold text-slate-900 dark:text-slate-50">{{ formatTime(item.waktu_pakan) }}</span>
          </div>
        </template>
        <template #target_pakan_gram="{ item }">
          <span class="font-semibold text-xs text-indigo-650 dark:text-indigo-400">Otomatis (Fase)</span>
        </template>
        <template #status_aktif="{ item }">
          <button 
            @click="handleToggleJadwal(item)" 
            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
            :class="item.status_aktif ? 'bg-indigo-650' : 'bg-slate-200 dark:bg-slate-800'"
          >
            <span 
              class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs transition duration-200 ease-in-out"
              :class="item.status_aktif ? 'translate-x-4' : 'translate-x-0'"
            ></span>
          </button>
        </template>
        <template #aksi="{ item }">
          <div class="flex justify-end gap-1.5">
            <button @click="openDetail('jadwal', item)" class="rounded-lg p-1 border border-slate-250 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900 text-slate-650 dark:text-slate-350">
              <Eye class="h-3.5 w-3.5" />
            </button>
            <button @click="openEditModal('jadwal', item)" class="rounded-lg p-1 border border-slate-250 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-400">
              <Edit2 class="h-3.5 w-3.5" />
            </button>
            <button @click="handleDeleteItem('jadwal', item.id)" class="rounded-lg p-1 border border-rose-250 hover:bg-rose-50 dark:border-rose-955/20 dark:hover:bg-rose-955/10 text-rose-600 dark:text-rose-455">
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Pagination Footer -->
      <div v-if="totalPagesJadwal > 1" class="flex items-center justify-between pt-2">
        <span class="text-2xs text-slate-500">Halaman {{ pageJadwal }} dari {{ totalPagesJadwal }} ({{ filteredJadwal.length }} data)</span>
        <div class="flex gap-1">
          <button :disabled="pageJadwal === 1" @click="pageJadwal--" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronLeft class="h-4 w-4" /></button>
          <button :disabled="pageJadwal === totalPagesJadwal" @click="pageJadwal++" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronRight class="h-4 w-4" /></button>
        </div>
      </div>
    </div>

    <!-- ==================== TAB 4: RIWAYAT PAKAN (CRUD) ==================== -->
    <div v-else-if="currentTab === 'riwayat'" class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-lg font-bold text-slate-850 dark:text-slate-100">Log Aktivitas Pemberian Pakan</h2>
        
        <div class="flex items-center gap-2.5">
          <!-- Search box -->
          <div class="relative w-48 sm:w-64">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input 
              type="text" 
              v-model="queryLog" 
              placeholder="Cari status / sumber / ket..." 
              class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 py-2 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200" 
            />
          </div>
          <!-- Create button -->
          <!-- <button @click="openCreateModal('log')" class="flex items-center gap-1 rounded-xl bg-indigo-650 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-all">
            <Plus class="h-3.5 w-3.5" />
            Tambah Log
          </button> -->
        </div>
      </div>

      <DataTable :columns="logColumns" :items="paginatedLogs" :loading="store.loading">
        <template #waktu_mulai="{ item }">
          <span class="font-medium text-slate-800 dark:text-slate-200 text-xs">{{ formatDate(item.waktu_mulai) }}</span>
        </template>
        <template #sumber="{ item }">
          <span class="capitalize text-xs font-semibold">{{ item.sumber }}</span>
        </template>
        <template #durasi_motor_detik="{ item }">
          <span class="text-xs">{{ item.durasi_motor_detik }} detik</span>
        </template>
        <template #jumlah_pakan_keluar_gram="{ item }">
          <span class="font-bold text-xs">{{ formatGramToKg(item.jumlah_pakan_keluar_gram) }}</span>
        </template>
        <template #status="{ item }">
          <StatusBadge :status="item.status" />
        </template>
        <template #keterangan="{ item }">
          <span class="text-2xs text-slate-500 dark:text-slate-400">{{ item.keterangan || '-' }}</span>
        </template>
        <template #aksi="{ item }">
          <div class="flex justify-end gap-1.5">
            <button @click="openEditModal('log', item)" class="rounded-lg p-1 border border-slate-250 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900 text-slate-600 dark:text-slate-400">
              <Edit2 class="h-3.5 w-3.5" />
            </button>
            <button @click="handleDeleteItem('log', item.id)" class="rounded-lg p-1 border border-rose-250 hover:bg-rose-50 dark:border-rose-955/20 dark:hover:bg-rose-955/10 text-rose-600 dark:text-rose-455">
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
      </DataTable>

      <!-- Pagination Footer -->
      <div v-if="totalPagesLogs > 1" class="flex items-center justify-between pt-2">
        <span class="text-2xs text-slate-500">Halaman {{ pageLog }} dari {{ totalPagesLogs }} ({{ filteredLogs.length }} data)</span>
        <div class="flex gap-1">
          <button :disabled="pageLog === 1" @click="pageLog--" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronLeft class="h-4 w-4" /></button>
          <button :disabled="pageLog === totalPagesLogs" @click="pageLog++" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronRight class="h-4 w-4" /></button>
        </div>
      </div>
    </div>

    <!-- ==================== TAB 5: STATUS ALAT (CRUD & DETAIL) ==================== -->
    <div v-else-if="currentTab === 'status'" class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-lg font-bold text-slate-850 dark:text-slate-100">Diagnosis & Hubungan Perangkat IoT</h2>
      </div>

      <!-- Card untuk tombol menyalakan motor -->
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-950 max-w-md mt-4 flex flex-col items-center justify-center space-y-4">
          <h3 class="font-bold text-slate-800 dark:text-slate-200 text-center">Kontrol Manual Motor Conveyor</h3>
          <p class="text-xs text-slate-500 text-center -mt-2">Gunakan tombol di bawah untuk memicu perputaran motor secara manual pada perangkat IoT.</p>
          <button 
            @click="toggleGlobalMotor" 
            class="bg-rose-500 hover:bg-rose-600 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-all cursor-pointer w-full mt-2"
          >
            Nyalakan Motor Conveyor
          </button>
      </div>

      <DataTable :columns="statusColumns" :items="paginatedStatus" :loading="store.loading">
        <template #nama_perangkat="{ item }">
          <span class="font-bold text-xs">{{ item.nama_perangkat }}</span>
        </template>
        <template #status_koneksi="{ item }">
          <StatusBadge :status="item.status_koneksi" />
        </template>
        <template #status_sensor="{ item }">
          <StatusBadge :status="item.status_sensor" />
        </template>
        <template #status_motor_gabungan="{ item }">
          <div class="flex items-center gap-1.5">
            <span class="font-bold text-xs capitalize" :class="item.status_motor === 'aktif' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'">
              {{ item.status_motor }}
            </span>
            <span class="text-[10px] text-slate-500 capitalize">
              ({{ item.mode_operasi }})
            </span>
          </div>
        </template>
        <template #terakhir_online="{ item }">
          <span class="text-xs">{{ formatDate(item.terakhir_online) }}</span>
        </template>
      </DataTable>

      <!-- Pagination Footer -->
      <div v-if="totalPagesStatus > 1" class="flex items-center justify-between pt-2">
        <span class="text-2xs text-slate-500">Halaman {{ pageStatus }} dari {{ totalPagesStatus }} ({{ filteredStatus.length }} data)</span>
        <div class="flex gap-1">
          <button :disabled="pageStatus === 1" @click="pageStatus--" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronLeft class="h-4 w-4" /></button>
          <button :disabled="pageStatus === totalPagesStatus" @click="pageStatus++" class="p-1.5 rounded-lg border border-slate-200 disabled:opacity-40 hover:bg-slate-50"><ChevronRight class="h-4 w-4" /></button>
        </div>
      </div>
    </div>

    <!-- ==================== DIALOG 1: CREATION & UPDATE SLIDE PANEL ==================== -->
    <div v-if="showCrudModal" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs transition-opacity duration-300">
      <!-- Backdrop click to close -->
      <div class="absolute inset-0 cursor-pointer" @click="showCrudModal = false"></div>
      
      <!-- Slide pane -->
      <div class="relative w-full max-w-md bg-white dark:bg-slate-950 h-full p-6 shadow-2xl overflow-y-auto flex flex-col justify-between transition-transform duration-300 transform translate-x-0">
        <div class="space-y-6">
          <div class="flex items-center justify-between border-b pb-4 border-slate-100 dark:border-slate-800">
            <h3 class="text-md font-bold text-slate-900 dark:text-slate-50 capitalize">
              {{ editingId ? 'Edit' : 'Tambah' }} {{ activeModalType }}
            </h3>
            <button @click="showCrudModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900"><X class="h-5 w-5 text-slate-550" /></button>
          </div>

          <form @submit.prevent="handleFormSubmit" class="space-y-4" id="crud-form">
            <!-- 1. Jadwal Pakan Fields -->
            <div v-if="activeModalType === 'jadwal'" class="space-y-4">
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Waktu Pengumpanan (HH:MM)</label>
                <input type="time" v-model="formJadwal.waktu_pakan" step="1" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Fase Umur Ayam</label>
                <select v-model="formJadwal.fase_umur" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option v-if="!store.dashboard.kebutuhan_pakan_master || store.dashboard.kebutuhan_pakan_master.length === 0" disabled value="">
                    Memuat fase...
                  </option>
                  <option 
                    v-else
                    v-for="item in store.dashboard.kebutuhan_pakan_master" 
                    :key="item.id" 
                    :value="item.fase_umur"
                  >
                    {{ item.fase_umur }} ({{ item.umur_mulai_hari }}-{{ item.umur_selesai_hari }} hari)
                  </option>
                </select>
              </div>
              <label class="flex items-center gap-2.5 cursor-pointer pt-1">
                <input type="checkbox" v-model="formJadwal.status_aktif" class="rounded border-slate-300 text-indigo-600 h-4 w-4" />
                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Set Aktif Sekarang</span>
              </label>
            </div>

            <!-- 2. Stok Pakan Fields -->
            <div v-if="activeModalType === 'stok'" class="space-y-4">
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Berat Pakan (Gram)</label>
                <input type="number" v-model="formStok.berat_pakan_gram" min="0" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Persentase Silo (%)</label>
                <input type="number" v-model="formStok.persentase_stok" min="0" max="100" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Status Stok</label>
                <select v-model="formStok.status_stok" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="aman">Aman</option>
                  <option value="hampir_habis">Hampir Habis</option>
                  <option value="habis">Habis</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Waktu Pembacaan</label>
                <input type="datetime-local" v-model="formStok.waktu_pembacaan" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
            </div>

            <!-- 3. Log Pemberian Pakan Fields -->
            <div v-if="activeModalType === 'log'" class="space-y-4">
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Kaitkan ke Jadwal (Optional)</label>
                <select v-model="formLog.jadwal_pakan_id" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option :value="null">Pemberian Manual (Tanpa Jadwal)</option>
                  <option v-for="j in store.jadwalList" :key="j.id" :value="j.id">
                    Jam: {{ formatTime(j.waktu_pakan) }} (Fase: {{ j.fase_umur }})
                  </option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Sumber Aktivitas</label>
                <select v-model="formLog.sumber" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="otomatis">Otomatis</option>
                  <option value="manual">Manual</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Waktu Mulai</label>
                <input type="datetime-local" v-model="formLog.waktu_mulai" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Waktu Selesai</label>
                <input type="datetime-local" v-model="formLog.waktu_selesai" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Durasi Motor Auger (Detik)</label>
                <input type="number" v-model="formLog.durasi_motor_detik" min="0" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Pakan Keluar (Gram)</label>
                <input type="number" v-model="formLog.jumlah_pakan_keluar_gram" min="0" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Status Pakan</label>
                <select v-model="formLog.status" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="berhasil">Berhasil</option>
                  <option value="gagal">Gagal</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Keterangan / Memo</label>
                <textarea v-model="formLog.keterangan" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"></textarea>
              </div>
            </div>

            <!-- 4. Status Perangkat Fields -->
            <div v-if="activeModalType === 'status'" class="space-y-4">
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Nama Perangkat IoT</label>
                <input type="text" v-model="formStatus.nama_perangkat" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Status Koneksi WiFi</label>
                <select v-model="formStatus.status_koneksi" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="online">Online</option>
                  <option value="offline">Offline</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Status Motor Dinamo</label>
                <select v-model="formStatus.status_motor" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="aktif">Aktif</option>
                  <option value="mati">Mati</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Status Sensor Timbangan</label>
                <select v-model="formStatus.status_sensor" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="normal">Normal</option>
                  <option value="rusak">Rusak</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Mode Operasi Alat</label>
                <select v-model="formStatus.mode_operasi" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                  <option value="otomatis">Otomatis</option>
                  <option value="manual">Manual</option>
                </select>
              </div>
              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500">Waktu Heartbeat Terakhir</label>
                <input type="datetime-local" v-model="formStatus.terakhir_online" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200" />
              </div>
            </div>
          </form>
        </div>

        <div class="border-t pt-4 border-slate-100 dark:border-slate-800 flex justify-end gap-2.5">
          <button @click="showCrudModal = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-655 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            Batal
          </button>
          <button type="submit" form="crud-form" :disabled="store.actionLoading" class="rounded-xl bg-indigo-650 text-white px-4 py-2 text-xs font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50">
            {{ store.actionLoading ? 'Menyimpan...' : 'Simpan Data' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== DIALOG 2: DETAIL VIEW PANEL OVERLAY ==================== -->
    <div v-if="showDetailModal" class="fixed inset-0 z-50 flex justify-end bg-black/50 backdrop-blur-xs transition-opacity duration-300">
      <div class="absolute inset-0 cursor-pointer" @click="showDetailModal = false"></div>
      
      <div class="relative w-full max-w-xl bg-white dark:bg-slate-950 h-full p-6 shadow-2xl overflow-y-auto flex flex-col justify-between transition-transform duration-300 transform translate-x-0">
        <div class="space-y-6">
          <div class="flex items-center justify-between border-b pb-4 border-slate-100 dark:border-slate-800">
            <h3 class="text-md font-bold text-slate-900 dark:text-slate-50 flex items-center gap-2">
              <Info class="h-5 w-5 text-indigo-500" />
              <span>Detail Informasi {{ detailType === 'jadwal' ? 'Jadwal Pakan' : 'Perangkat IoT' }}</span>
            </h3>
            <button @click="showDetailModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-900"><X class="h-5 w-5 text-slate-550" /></button>
          </div>

          <!-- Loading state with Skeleton -->
          <div v-if="loadingDetail" class="space-y-6">
            <!-- Summary card skeleton -->
            <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl grid grid-cols-2 gap-4 border border-slate-100 dark:border-slate-850 animate-pulse">
              <div v-for="i in 4" :key="'sum-skel-' + i" class="space-y-2">
                <div class="h-2.5 bg-slate-200 dark:bg-slate-800 rounded-md w-1/2"></div>
                <div class="h-4 bg-slate-300 dark:bg-slate-700 rounded-md w-3/4"></div>
              </div>
            </div>
            <!-- Histori list skeleton -->
            <div class="space-y-3 animate-pulse">
              <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded-md w-1/3 mb-4"></div>
              <div class="border border-slate-150 dark:border-slate-850 rounded-xl p-3 space-y-4">
                <div v-for="i in 4" :key="'list-skel-' + i" class="flex justify-between items-center">
                  <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded-md w-1/3"></div>
                  <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded-md w-1/4"></div>
                  <div class="h-5 bg-slate-300 dark:bg-slate-700 rounded-md w-16"></div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="space-y-6">
            <!-- 1. DETAIL JADWAL PAKAN -->
            <div v-if="detailType === 'jadwal' && detailItem" class="space-y-6">
              <!-- Summary Card details -->
              <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl grid grid-cols-2 gap-4 border border-slate-100 dark:border-slate-850">
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Jam Jadwal</div>
                  <div class="text-base font-extrabold text-slate-900 dark:text-slate-50">{{ formatTime(detailItem.waktu_pakan) }}</div>
                </div>
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Fase Umur</div>
                  <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ detailItem.fase_umur }}</div>
                </div>
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Target Porsi</div>
                  <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ formatGramToKg(detailItem.target_pakan_gram) }}</div>
                </div>
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Status</div>
                  <StatusBadge :status="detailItem.status_aktif ? 'aktif' : 'offline'" />
                </div>
              </div>

              <!-- Executions History list -->
              <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Histori Eksekusi Jadwal Ini</h4>
                <div class="max-h-80 overflow-y-auto rounded-xl border border-slate-150 dark:border-slate-850">
                  <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 sticky top-0">
                      <tr>
                        <th class="p-3 font-semibold">Waktu Mulai</th>
                        <th class="p-3 font-semibold">Pakan Keluar</th>
                        <th class="p-3 font-semibold">Status</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                      <tr v-if="detailHistory.length === 0">
                        <td colspan="3" class="p-4 text-center text-slate-400">Belum pernah dijalankan.</td>
                      </tr>
                      <tr v-else v-for="log in detailHistory" :key="log.id" class="hover:bg-slate-50/50">
                        <td class="p-3">{{ formatDate(log.waktu_mulai) }}</td>
                        <td class="p-3 font-semibold">{{ formatGramToKg(log.jumlah_pakan_keluar_gram) }}</td>
                        <td class="p-3"><StatusBadge :status="log.status" /></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- 2. DETAIL STATUS PERANGKAT -->
            <div v-if="detailType === 'status' && detailItem" class="space-y-6">
              <!-- Summary Card details -->
              <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl grid grid-cols-2 gap-4 border border-slate-100 dark:border-slate-850">
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Nama Perangkat</div>
                  <div class="text-sm font-bold text-slate-900 dark:text-slate-50">{{ detailItem.nama_perangkat }}</div>
                </div>
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Koneksi WiFi</div>
                  <StatusBadge :status="detailItem.status_koneksi" />
                </div>
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Status Sensor</div>
                  <StatusBadge :status="detailItem.status_sensor" />
                </div>
                <div>
                  <div class="text-3xs font-semibold text-slate-450 uppercase">Status Motor</div>
                  <StatusBadge :status="detailItem.status_motor" />
                </div>
              </div>

              <!-- Connection Logs -->
              <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Histori Koneksi WiFi Perangkat</h4>
                <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-150 dark:border-slate-850">
                  <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 sticky top-0">
                      <tr>
                        <th class="p-2.5 font-semibold">Tercatat</th>
                        <th class="p-2.5 font-semibold">Koneksi</th>
                        <th class="p-2.5 font-semibold">Motor</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                      <tr v-for="conn in detailHistory" :key="conn.id" class="hover:bg-slate-50/50">
                        <td class="p-2.5">{{ formatDate(conn.updated_at) }}</td>
                        <td class="p-2.5"><StatusBadge :status="conn.status_koneksi" /></td>
                        <td class="p-2.5"><StatusBadge :status="conn.status_motor" /></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Sensor Histori -->
              <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Histori Pembacaan Sensor Silo</h4>
                <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-150 dark:border-slate-850">
                  <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 sticky top-0">
                      <tr>
                        <th class="p-2.5 font-semibold">Waktu Baca</th>
                        <th class="p-2.5 font-semibold">Level Silo</th>
                        <th class="p-2.5 font-semibold">Status Pakan</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                      <tr v-for="stk in detailSensorHistory" :key="stk.id" class="hover:bg-slate-50/50">
                        <td class="p-2.5">{{ formatDate(stk.waktu_pembacaan) }}</td>
                        <td class="p-2.5 font-bold">{{ formatGramToKg(stk.berat_pakan_gram) }} ({{ stk.persentase_stok }}%)</td>
                        <td class="p-2.5"><StatusBadge :status="stk.status_stok" /></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="border-t pt-4 border-slate-100 dark:border-slate-800 flex justify-end">
          <button @click="showDetailModal = false" class="rounded-xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 px-5 py-2 text-xs font-bold hover:bg-slate-800 dark:hover:bg-slate-200 transition-all">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>>

<style scoped>
.bg-indigo-650 {
  background-color: #4f46e5;
}
.bg-indigo-650:hover {
  background-color: #4338ca;
}
.text-rose-455 {
  color: #f43f5e;
}
.text-3xs {
  font-size: 0.6rem;
}
</style>
