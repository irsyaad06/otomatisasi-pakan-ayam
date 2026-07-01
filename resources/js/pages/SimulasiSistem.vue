<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useDashboardStore } from '@/stores/dashboardStore';
import { toast } from 'vue-sonner';
import api from '@/services/api';
import {
    Play,
    Pause,
    Square,
    RotateCcw,
    AlertTriangle,
    Zap,
    Wifi,
    Clock,
    Database,
    Cpu,
    Activity,
    Info,
    Calendar,
    Settings,
    Edit2,
    Check,
    X,
} from 'lucide-vue-next';

// Register layout options with Inertia breadcrumbs
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Simulasi Sistem',
                href: '/simulasi-sistem',
            },
        ],
    },
});

// Initialize Pinia store
const store = useDashboardStore();

// Simulation State Flags
const isSimulating = ref(false);
const isPaused = ref(false);
const isAutoCutActive = ref(false);

// Local simulation reactive values
const simWeight = ref(4800);
const simPercent = ref(80);
const simDuration = ref(0);
const simPakanWadah = ref(0);
const simMotorActive = ref(false);

// API Loading states
const apiLoading = ref(false);

// Telemetry status state
const simStatus = ref<any>(null);
const targetSchedule = ref<any>(null);

// Inline editing states for ayam population
const isEditingAyam = ref(false);
const tempJumlahAyam = ref(100);
const updatingAyam = ref(false);

const startEditAyam = () => {
    tempJumlahAyam.value = simStatus.value?.periode_aktif?.jumlah_ayam ?? 100;
    isEditingAyam.value = true;
};

const saveJumlahAyam = async () => {
    if (tempJumlahAyam.value < 1) {
        toast.error('Jumlah ayam minimal 1 ekor.');
        return;
    }
    updatingAyam.value = true;
    try {
        const response = await api.post('/periode-aktif/update-ayam', {
            jumlah_ayam: tempJumlahAyam.value
        });
        if (response.data?.status) {
            toast.success('Jumlah ayam berhasil diperbarui!');
            if (simStatus.value?.periode_aktif) {
                simStatus.value.periode_aktif.jumlah_ayam = response.data.data.jumlah_ayam;
            }
            isEditingAyam.value = false;
            fetchSimStatus(); // Refresh telemetry/parameter data
        } else {
            toast.error(response.data?.message || 'Gagal memperbarui jumlah ayam.');
        }
    } catch (err: any) {
        const msg = err.response?.data?.message || 'Gagal memperbarui jumlah ayam.';
        toast.error(msg);
    } finally {
        updatingAyam.value = false;
    }
};

const cancelEditAyam = () => {
    isEditingAyam.value = false;
};

// Local log items
interface LogItem {
    waktu: string;
    komponen: string;
    peristiwa: string;
    keterangan: string;
}
const localLogs = ref<LogItem[]>([]);

// Helper to push a new entry to the logs table
const addLog = (komponen: string, peristiwa: string, keterangan: string) => {
    localLogs.value.unshift({
        waktu: new Date().toLocaleTimeString('id-ID'),
        komponen,
        peristiwa,
        keterangan,
    });
};

// Fetch Simulation Status from API
const fetchSimStatus = async () => {
    try {
        const response = await api.get('/simulasi/status');
        if (response.data?.status) {
            simStatus.value = response.data.data;
            
            // Sync visual data from database if not currently simulating
            if (!isSimulating.value) {
                simWeight.value = response.data.data.stok_pakan?.berat_pakan_gram ?? 4800;
                simPercent.value = response.data.data.stok_pakan?.persentase_stok ?? 80;
                simPakanWadah.value = 0;
            }
        }
    } catch (err) {
        console.error("Gagal memuat status simulasi:", err);
    }
};

// Target porsi calculation
const computedTargetPakan = computed(() => {
    if (targetSchedule.value) {
        if (targetSchedule.value.target_otomatis) {
            return simStatus.value?.target_pakan_per_jadwal ?? 1750;
        } else {
            return targetSchedule.value.target_pakan_gram ?? 1500;
        }
    }
    const nextJadwal = simStatus.value?.jadwal_berikutnya;
    if (nextJadwal) {
        if (nextJadwal.target_otomatis) {
            return simStatus.value?.target_pakan_per_jadwal ?? 1750;
        } else {
            return nextJadwal.target_pakan_gram ?? 1500;
        }
    }
    return simStatus.value?.target_pakan_per_jadwal ?? 1750;
});

// CSS dynamic values based on stock percentage
const currentPercent = computed(() => {
    return isSimulating.value ? simPercent.value : (simStatus.value?.stok_pakan?.persentase_stok ?? 80);
});

const currentWeightG = computed(() => {
    return isSimulating.value ? simWeight.value : (simStatus.value?.stok_pakan?.berat_pakan_gram ?? 4800);
});

// Height is 30 cm when empty (0%), 5 cm when full (100%)
const currentHeightCm = computed(() => {
    return 30 - (currentPercent.value * 25) / 100;
});

// Compute Silo Fill Y-coordinate for the SVG element (height 80 to 240, difference 160)
const siloFillY = computed(() => {
    return 80 + (1 - currentPercent.value / 100) * 160;
});

// Ry of the ellipse in the food dish (from 0 to 22)
const dishPakanRy = computed(() => {
    return Math.min(22, (simPakanWadah.value / computedTargetPakan.value) * 22);
});

// Progress percentage towards feeding target
const progressPercent = computed(() => {
    if (computedTargetPakan.value <= 0) return 0;
    return Math.min(100, Math.round((simPakanWadah.value / computedTargetPakan.value) * 100));
});

// Format date helper
const formatWaktu = (time: string | null | undefined) => {
    if (!time) return '-';
    return time;
};

// Simulation Loop reference
let simTimer: any = null;

// Start Simulation
const startSimulation = () => {
    if (isSimulating.value && !isPaused.value) return;

    // Check if active period exists
    if (!simStatus.value?.periode_aktif) {
        toast.error('Tidak ada periode pemeliharaan aktif! Silakan buat periode terlebih dahulu.');
        return;
    }

    // Check if stock is sufficient
    if (simWeight.value < computedTargetPakan.value) {
        addLog('Silo Ketersediaan', 'Peringatan', 'Stok pakan tidak mencukupi untuk menjalankan pemberian pakan.');
        toast.error('Stok pakan tidak mencukupi!');
        return;
    }

    if (!isSimulating.value) {
        // Fresh start
        simDuration.value = 0;
        simPakanWadah.value = 0;
        isAutoCutActive.value = false;

        addLog('Sistem', 'Memulai Simulasi', targetSchedule.value 
            ? `Menjalankan jadwal otomatis pada pukul ${targetSchedule.value.waktu_pakan}`
            : 'Menjalankan pemberian pakan secara manual.');
        addLog('Sensor Ultrasonik', 'Membaca Tinggi', `Tinggi pakan awal: ${currentHeightCm.value.toFixed(1)} cm`);
        addLog('Load Cell', 'Membaca Berat', `Berat pakan awal: ${(currentWeightG.value / 1000).toFixed(2)} kg`);
        addLog('Motor Auger', 'Mulai Aktif', 'Motor pengumpan berputar (status: aktif)');
        toast.info('Simulasi pemberian pakan dimulai!');
    } else {
        // Resuming from pause
        addLog('Motor Auger', 'Aktif Kembali', 'Melanjutkan putaran motor auger');
        toast.info('Simulasi dilanjutkan.');
    }

    isSimulating.value = true;
    isPaused.value = false;
    simMotorActive.value = true;

    // Tick loop every 200ms for smooth animations (increments 10g per tick -> 50g per second)
    const outputRate = 10;
    simTimer = setInterval(() => {
        // 5 ticks = 1 second
        simDuration.value += 1;

        // Decrease feed in silo
        simWeight.value = Math.max(0, simWeight.value - outputRate);
        simPercent.value = Math.max(0, Math.round((simWeight.value / 50000) * 100));

        // Increase feed in dish
        simPakanWadah.value += outputRate;

        // Periodic logging (every 2 seconds -> 10 ticks)
        if (simDuration.value % 10 === 0) {
            addLog('Pipa Auger', 'Mengalirkan Pakan', `Pakan terdistribusi: ${simPakanWadah.value} gram`);
        }

        // Auto-cut check once target is reached
        if (simPakanWadah.value >= computedTargetPakan.value) {
            clearInterval(simTimer);
            triggerAutoCutLocal();
        }

        // Hopper empty condition
        if (simWeight.value <= 0) {
            clearInterval(simTimer);
            stopSimulationDueToEmpty();
        }
    }, 200);
};

// Pause Simulation
const pauseSimulation = () => {
    if (!isSimulating.value || isPaused.value) return;

    clearInterval(simTimer);
    isPaused.value = true;
    simMotorActive.value = false;
    addLog('Motor Auger', 'Jeda', 'Motor dihentikan sementara (status: mati)');
    toast.warning('Simulasi dijeda.');
};

// Stop and Save Simulation
const stopSimulation = async () => {
    if (!isSimulating.value) return;

    clearInterval(simTimer);
    simMotorActive.value = false;
    isSimulating.value = false;
    isPaused.value = false;

    addLog('Simulasi', 'Dihentikan', 'Menyelesaikan proses pemberian pakan secara manual.');
    addLog('API Gateway', 'Mengirim Data', 'Mengirim hasil telemetry ke server Laravel...');

    apiLoading.value = true;
    try {
        const payload = targetSchedule.value ? { jadwal_pakan_id: targetSchedule.value.id } : {};
        const response = await api.post('/simulasi/jalankan-pakan', payload);
        if (response.data?.status) {
            simStatus.value = response.data.data;
            toast.success('Pemberian pakan berhasil disimpan ke database!');
        }
    } catch (err: any) {
        console.error(err);
        addLog('API Gateway', 'Gagal', 'Gagal menyinkronkan data ke server.');
        toast.error(err.response?.data?.message || 'Terjadi kesalahan saat menyimpan data.');
    } finally {
        apiLoading.value = false;
        targetSchedule.value = null;
        fetchSimStatus();
    }
};

// Reset visualizer and state
const resetSimulation = () => {
    clearInterval(simTimer);
    isSimulating.value = false;
    isPaused.value = false;
    simMotorActive.value = false;
    isAutoCutActive.value = false;
    simDuration.value = 0;
    simPakanWadah.value = 0;
    targetSchedule.value = null;

    apiLoading.value = true;
    fetchSimStatus().then(() => {
        localLogs.value = [];
        addLog('Sistem', 'Reset', 'Visualisasi simulasi di-reset ke data sensor aktual.');
        toast.success('Visualisasi berhasil di-reset.');
    }).catch(() => {
        toast.error('Gagal memuat ulang data status.');
    }).finally(() => {
        apiLoading.value = false;
    });
};

// Trigger Auto-Cut locally
const triggerAutoCutLocal = async () => {
    simMotorActive.value = false;
    isSimulating.value = false;
    isPaused.value = false;
    isAutoCutActive.value = true;

    addLog('Sistem Auto-Cut', 'Batas Tercapai', `Batas target porsi pakan tercapai (${computedTargetPakan.value} g).`);
    addLog('Motor Auger', 'Berhenti Otomatis', 'Motor berhenti otomatis karena target porsi pakan tercapai');
    addLog('API Gateway', 'Mengirim Data', 'Mengirim status auto-cut ke server Laravel...');

    apiLoading.value = true;
    try {
        const payload = targetSchedule.value ? { jadwal_pakan_id: targetSchedule.value.id } : {};
        const response = await api.post('/simulasi/auto-cut', payload);
        if (response.data?.status) {
            simStatus.value = response.data.data;
            addLog('Database', 'Data Disimpan', 'Data auto-cut dan log pemberian pakan diperbarui di server.');
            toast.success('Motor berhenti otomatis karena target porsi pakan tercapai');
        }
    } catch (err) {
        console.error(err);
        toast.error('Gagal mengirim data auto-cut ke server.');
    } finally {
        apiLoading.value = false;
        targetSchedule.value = null;
        fetchSimStatus();
    }
};

// Stop because hopper is empty
const stopSimulationDueToEmpty = () => {
    simMotorActive.value = false;
    isSimulating.value = false;
    isPaused.value = false;
    addLog('Silo Ketersediaan', 'Stok Kosong', 'Stok pakan di dalam silo habis.');
    addLog('Motor Auger', 'Berhenti', 'Motor dimatikan karena hopper kosong.');
    toast.error('Hopper kosong! Silakan isi stok pakan terlebih dahulu.');
};

// Trigger Stok Menipis (Direct backend trigger)
const triggerStokMenipis = async () => {
    apiLoading.value = true;
    addLog('API Gateway', 'Mengirim Data', 'Mengirim simulasi stok menipis (15%) ke server...');
    try {
        await api.post('/iot/simulasi-stok-menipis');
        await fetchSimStatus();
        isAutoCutActive.value = false;

        addLog('Sensor Ultrasonik', 'Membaca Tinggi', `Jarak kritis dibaca: ${currentHeightCm.value.toFixed(1)} cm (Stok Menipis)`);
        addLog('Load Cell', 'Membaca Berat', `Berat pakan kritis dibaca: ${(currentWeightG.value / 1000).toFixed(2)} kg`);
        addLog('Database', 'Data Disimpan', 'Status stok pakan berhasil diset ke hampir_habis (15%).');
        toast.warning('Simulasi Stok Menipis Berhasil Dijalankan!');
    } catch (err) {
        console.error(err);
        toast.error('Gagal menjalankan simulasi stok menipis.');
    } finally {
        apiLoading.value = false;
    }
};

// Trigger Auto-Cut Direct (Direct backend trigger)
const triggerAutoCutDirect = async () => {
    apiLoading.value = true;
    addLog('API Gateway', 'Mengirim Data', 'Mengirim trigger auto-cut ke server...');
    try {
        const payload = targetSchedule.value ? { jadwal_pakan_id: targetSchedule.value.id } : {};
        const response = await api.post('/simulasi/auto-cut', payload);
        if (response.data?.status) {
            simStatus.value = response.data.data;
            isAutoCutActive.value = true;
            simMotorActive.value = false;
            simPakanWadah.value = computedTargetPakan.value;

            addLog('Sistem Auto-Cut', 'Sensor Aktif', 'Sinyal batas tampung piring mendeteksi porsi penuh.');
            addLog('Motor Auger', 'Berhenti Otomatis', 'Motor berhenti karena batas porsi tercapai');
            addLog('Database', 'Data Disimpan', 'Status alat terupdate ke mati dengan log tersimpan.');
            toast.success('Simulasi Auto-Cut berhasil diaktifkan secara langsung!');
        }
    } catch (err) {
        console.error(err);
        toast.error('Gagal mengaktifkan simulasi auto-cut.');
    } finally {
        apiLoading.value = false;
        targetSchedule.value = null;
    }
};

// Toggle physical motor status manually on the backend
const togglePhysicalMotor = async () => {
    if (!simStatus.value?.status_alat) {
        toast.error('Data perangkat tidak ditemukan.');
        return;
    }
    const currentStatus = simStatus.value.status_alat.status_motor;
    const newStatus = currentStatus === 'aktif' ? 'mati' : 'aktif';
    apiLoading.value = true;
    try {
        const response = await api.put(`/status-alat/${simStatus.value.status_alat.id}`, {
            status_motor: newStatus,
            mode_operasi: 'manual'
        });
        if (response.data?.status) {
            toast.success(`Motor berhasil diubah menjadi ${newStatus}!`);
            await fetchSimStatus();
            await store.fetchDashboard();
        } else {
            toast.error('Gagal memperbarui status motor.');
        }
    } catch (err: any) {
        toast.error(err.response?.data?.message || 'Terjadi kesalahan saat menghubungi server.');
    } finally {
        apiLoading.value = false;
    }
};

// Event classes helper for color coding
const getPeristiwaClass = (p: string) => {
    const lower = p.toLowerCase();
    if (lower.includes('aktif') || lower.includes('mulai') || lower.includes('berhasil') || lower.includes('simpan')) {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20';
    }
    if (lower.includes('jeda') || lower.includes('peringatan') || lower.includes('menipis') || lower.includes('kritis') || lower.includes('terdeteksi')) {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20';
    }
    if (lower.includes('mati') || lower.includes('nonaktif') || lower.includes('henti') || lower.includes('cut') || lower.includes('kosong') || lower.includes('kurang')) {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20';
    }
    return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
};

// Automatic Schedule Polling
let cekJadwalTimer: any = null;

const startCekJadwalPolling = () => {
    cekJadwalTimer = setInterval(async () => {
        // Only check schedules if we are not currently simulating
        if (isSimulating.value) return;
        
        try {
            const response = await api.get('/simulasi/cek-jadwal');
            if (response.data?.status && response.data.data) {
                const schedule = response.data.data;
                targetSchedule.value = schedule;
                
                addLog('Sistem Otomatis', 'Jadwal Terdeteksi', `Jadwal aktif terdeteksi pukul ${schedule.waktu_pakan}. Memulai pemberian pakan...`);
                toast.info(`Jadwal Terdeteksi: Pakan otomatis jam ${schedule.waktu_pakan} dimulai!`);
                
                // Launch feeding simulation flow
                startSimulation();
            }
        } catch (err) {
            console.error("Gagal memeriksa jadwal otomatis:", err);
        }
    }, 5000);
};

// Lifecycle hooks
onMounted(() => {
    fetchSimStatus();
    startCekJadwalPolling();
    addLog('Sistem', 'Inisialisasi', 'Menghubungkan ke simulasi hardware IoT...');
});

onUnmounted(() => {
    if (simTimer) clearInterval(simTimer);
    if (cekJadwalTimer) clearInterval(cekJadwalTimer);
});
</script>

<template>
    <Head title="Simulasi Sistem IoT" />

    <div class="space-y-6 p-6">
        <!-- Topbar Info -->
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50 flex items-center gap-2">
                    <span>Simulasi Sistem IoT Pemberi Pakan</span>
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Visualisasi interaktif pergerakan aktuator motor auger, sensor berat, dan tinggi stok secara real-time.
                </p>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300 shadow-xs">
                <Wifi class="h-4 w-4" :class="store.dashboard.status_alat?.status_koneksi === 'online' ? 'text-emerald-500' : 'text-rose-500'" />
                <span>Status IoT: {{ store.dashboard.status_alat?.status_koneksi === 'online' ? 'Terhubung' : 'Offline' }}</span>
            </div>
        </div>

        <!-- Empty State if no active period -->
        <div v-if="simStatus && !simStatus.periode_aktif" class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 p-12 text-center max-w-xl mx-auto shadow-xs my-10">
            <Calendar class="mx-auto h-12 w-12 text-slate-400 mb-4 animate-pulse" />
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Belum Ada Periode Pemeliharaan Aktif</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 mb-6">
                Sistem memerlukan periode pemeliharaan aktif untuk menghitung fase umur ayam dan porsi kebutuhan pakan harian secara otomatis.
            </p>
            <Link href="/dashboard" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 text-sm transition shadow-sm">
                Buat Periode Pemeliharaan
            </Link>
        </div>

        <!-- Main Workspace: Visualizer + Panel -->
        <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            
            <!-- Left Side: Interactive SVG Component Visualizer -->
            <div class="lg:col-span-7 flex flex-col">
                <div class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-xs relative overflow-hidden flex flex-col justify-between">
                    <!-- Title Bar -->
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-mono tracking-widest text-slate-500 uppercase">Hardware Schematic Visualizer</span>
                        </div>
                        <div class="flex gap-2">
                            <span v-if="simMotorActive" class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-emerald-500/20 text-emerald-600 border border-emerald-500/30 animate-pulse">
                                MOTOR ACTIVE
                            </span>
                            <span v-if="isAutoCutActive" class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-rose-500/20 text-rose-600 border border-rose-500/30">
                                AUTO-CUT TRIGGERED
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-mono text-slate-500 bg-slate-200/50 border border-slate-300">
                                SCALE: 1:10
                            </span>
                        </div>
                    </div>

                    <!-- Schematic SVG Content -->
                    <div class="flex items-center justify-center py-2 flex-grow">
                        <svg viewBox="0 0 800 480" width="100%" class="max-h-[380px] w-full text-slate-700 select-none">
                            <defs>
                                <!-- Grid Blueprint pattern -->
                                <pattern id="blueprintGrid" width="40" height="40" patternUnits="userSpaceOnUse">
                                    <path d="M 40 0 L 0 0 0 40" fill="none" class="stroke-slate-200/80" stroke-width="1" />
                                </pattern>
                                <!-- Grain Pellets texture pattern -->
                                <pattern id="grainPattern" width="8" height="8" patternUnits="userSpaceOnUse">
                                    <rect width="8" height="8" fill="#d97706" fill-opacity="0.12" />
                                    <circle cx="2" cy="2" r="1.5" fill="#f59e0b" />
                                    <circle cx="6" cy="5" r="1.2" fill="#fbbf24" />
                                    <circle cx="3" cy="6" r="1" fill="#fbbf24" />
                                </pattern>
                                <!-- Linear Gradients for 3D Cylinder tube -->
                                <linearGradient id="pipeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#475569" stop-opacity="0.2" />
                                    <stop offset="50%" stop-color="#64748b" stop-opacity="0.5" />
                                    <stop offset="100%" stop-color="#334155" stop-opacity="0.2" />
                                </linearGradient>
                            </defs>

                            <!-- Background Grid -->
                            <rect width="800" height="480" fill="url(#blueprintGrid)" rx="10" />

                            <!-- Flow Paths Glowing / Animating (Hopper to pipe, pipe to dish) -->
                            <g opacity="0.8">
                                <path d="M 200 240 L 200 280 L 460 380" fill="none" stroke="#10b981" stroke-width="2.5" 
                                    stroke-dasharray="6,6" :class="{ 'flow-arrow-active': simMotorActive }" />
                            </g>

                            <!-- 1. Mini Silo/Hopper Shape & Grain Level inside -->
                            <g id="silo">
                                <!-- Background Inside Silo -->
                                <polygon points="110,80 290,80 240,240 160,240" class="fill-slate-100/90 stroke-slate-300" stroke-width="1.5" />
                                
                                <!-- Clipping path for hopper grain fill level -->
                                <clipPath id="siloClip">
                                    <polygon points="110,80 290,80 240,240 160,240" />
                                </clipPath>
                                
                                <!-- Dynamic Grain Fill Rectangle masked inside Silhouette -->
                                <rect x="90" :y="siloFillY" width="220" height="200" fill="url(#grainPattern)" clip-path="url(#siloClip)" />

                                <!-- Glass outline border -->
                                <polygon points="110,80 290,80 240,240 160,240" fill="none" class="stroke-slate-400" stroke-width="3" stroke-linejoin="round" />
                                
                                <!-- Front Glass reflections -->
                                <line x1="125" y1="90" x2="175" y2="230" stroke="rgba(255, 255, 255, 0.4)" stroke-width="4" stroke-linecap="round" />
                                
                                <!-- Level Indicator Markings -->
                                <line x1="270" y1="100" x2="280" y2="100" class="stroke-slate-300" stroke-width="1" />
                                <text x="260" y="103" class="fill-slate-500 font-mono" font-size="9">80%</text>
                                <line x1="255" y1="160" x2="265" y2="160" class="stroke-slate-300" stroke-width="1" />
                                <text x="245" y="163" class="fill-slate-500 font-mono" font-size="9">50%</text>
                                <line x1="240" y1="220" x2="250" y2="220" class="stroke-slate-300" stroke-width="1" />
                                <text x="230" y="223" class="fill-slate-500 font-mono" font-size="9">20%</text>

                                <!-- Silo Label -->
                                <rect x="165" y="130" width="70" height="18" rx="3" class="fill-slate-200/90 stroke-slate-300" stroke-width="1" />
                                <text x="200" y="142" class="fill-slate-600 text-xs font-bold" text-anchor="middle">MINI SILO</text>
                            </g>

                            <!-- 2. Ultrasonic Sensor (top of Silo) -->
                            <g id="ultrasonic">
                                <!-- Sound Transducers -->
                                <rect x="180" y="30" width="40" height="18" rx="2" class="fill-slate-300 stroke-slate-400" stroke-width="1.5" />
                                <circle cx="190" cy="39" r="6" class="fill-slate-100 stroke-slate-500" stroke-width="1.5" />
                                <circle cx="210" cy="39" r="6" class="fill-slate-100 stroke-slate-500" stroke-width="1.5" />
                                <rect x="194" y="22" width="12" height="8" rx="1" class="fill-slate-400" />

                                <!-- Waves pointing downwards -->
                                <g>
                                    <path class="ultrasonic-wave wave-1" d="M 185 58 Q 200 66 215 58" fill="none" stroke="#10b981" stroke-width="1.5" />
                                    <path class="ultrasonic-wave wave-2" d="M 175 70 Q 200 80 225 70" fill="none" stroke="#10b981" stroke-width="1.5" />
                                    <path class="ultrasonic-wave wave-3" d="M 165 82 Q 200 94 235 82" fill="none" stroke="#10b981" stroke-width="1.5" />
                                </g>

                                <!-- Dotted Measurement Line -->
                                <line x1="200" y1="48" x2="200" :y2="siloFillY" stroke="#10b981" stroke-width="1.5" stroke-dasharray="4,4" opacity="0.6" />

                                <!-- Sensor label & Readout -->
                                <rect x="145" y="2" width="110" height="22" rx="4" class="fill-white stroke-emerald-500" stroke-width="1.2" />
                                <text x="200" y="16" fill="#10b981" font-size="10" font-family="monospace" font-weight="bold" text-anchor="middle">
                                    Ultrasonik: {{ currentHeightCm.toFixed(1) }} cm
                                </text>
                            </g>

                            <!-- 3. Load Cell (Bottom of Hopper Silo) -->
                            <g id="loadcell">
                                <!-- Metal scale bracket -->
                                <rect x="150" y="240" width="100" height="8" class="fill-slate-300 stroke-slate-400" stroke-width="1" />
                                <rect x="180" y="248" width="40" height="14" class="fill-slate-200 stroke-slate-300" stroke-width="1" />
                                <!-- Strain Gauges dots -->
                                <circle cx="190" cy="255" r="2" class="fill-slate-500" />
                                <circle cx="210" cy="255" r="2" class="fill-slate-500" />
                                <!-- Measurement box -->
                                <rect x="255" y="240" width="125" height="22" rx="4" class="fill-white stroke-amber-500" stroke-width="1.2" />
                                <text x="317" y="254" fill="#f59e0b" font-size="9" font-family="monospace" font-weight="bold" text-anchor="middle">
                                    Load Cell: {{ (currentWeightG / 1000).toFixed(2) }} kg
                                </text>
                            </g>

                            <!-- 4. Motor Auger -->
                            <g id="motor">
                                <!-- Motor body with fins -->
                                <rect x="100" y="270" width="45" height="40" rx="3" class="fill-slate-300 stroke-slate-400" stroke-width="2" />
                                <line x1="105" y1="270" x2="105" y2="310" class="stroke-slate-400" stroke-width="2" />
                                <line x1="113" y1="270" x2="113" y2="310" class="stroke-slate-400" stroke-width="2" />
                                <line x1="121" y1="270" x2="121" y2="310" class="stroke-slate-400" stroke-width="2" />
                                <line x1="129" y1="270" x2="129" y2="310" class="stroke-slate-400" stroke-width="2" />
                                <line x1="137" y1="270" x2="137" y2="310" class="stroke-slate-400" stroke-width="2" />

                                <!-- Shaft / fan cover -->
                                <rect x="90" y="275" width="10" height="30" rx="1" class="fill-slate-400" />
                                <circle cx="95" cy="290" r="8" class="fill-slate-200 stroke-slate-400" stroke-width="1" />
                                <!-- Rotating wheel inside the cover -->
                                <path d="M 95 282 L 95 298 M 87 290 L 103 290" stroke="#ef4444" stroke-width="1.5" 
                                    :class="{ 'motor-spin': simMotorActive }" />
                                
                                <text x="122" y="324" class="fill-slate-500 text-xs font-bold" font-size="9" text-anchor="middle">MOTOR</text>
                            </g>

                            <!-- 5. Pipa Auger (Slanted Tube) -->
                            <g id="auger-pipe">
                                <!-- Background metal base connecting Silo Outlet to Pipe -->
                                <path d="M 180 255 L 220 255 L 220 285 L 180 285 Z" class="fill-slate-300 stroke-slate-400" />
                                
                                <!-- Slanted transparent glass pipe tube -->
                                <line x1="190" y1="285" x2="470" y2="375" stroke="url(#pipeGrad)" stroke-width="28" stroke-linecap="round" />
                                <line x1="190" y1="285" x2="470" y2="375" class="stroke-slate-200/50" stroke-width="24" stroke-linecap="round" />

                                <!-- Inside Auger Spiral / Screw Thread -->
                                <line x1="205" y1="290" x2="455" y2="370" class="stroke-slate-400" stroke-width="4" 
                                    stroke-dasharray="14,10" :class="{ 'auger-screw-active': simMotorActive }" stroke-linecap="round" />

                                <!-- Inside Grains flowing down -->
                                <line x1="205" y1="290" x2="455" y2="370" stroke="#f59e0b" stroke-width="8" 
                                    stroke-dasharray="5,15" :class="{ 'grain-flow-active': simMotorActive }" opacity="0.9" />

                                <!-- Pipe structural support brackets -->
                                <line x1="320" y1="315" x2="320" y2="380" class="stroke-slate-400" stroke-width="3" />
                                <rect x="305" y="380" width="30" height="6" class="fill-slate-300" />
                                
                                <text x="335" y="310" class="fill-slate-500 font-bold" font-size="9">PIPA AUGER</text>
                            </g>

                            <!-- 6. Panci / Wadah Pakan (Food Dish) -->
                            <g id="food-dish">
                                <!-- Food Dish Base -->
                                <path d="M 430 380 C 430 435, 590 435, 590 380 Z" class="fill-slate-200 stroke-slate-400" stroke-width="3" stroke-linejoin="round" />
                                <path d="M 430 380 Q 510 395 590 380" fill="none" class="stroke-slate-300" stroke-width="2" />

                                <!-- Clipping path to keep grain heap within dish boundaries -->
                                <clipPath id="dishClip">
                                    <path d="M 432 381 C 432 433, 588 433, 588 381 Z" />
                                </clipPath>

                                <!-- Growing Food Pile Heap inside Dish -->
                                <ellipse cx="510" cy="415" rx="72" :ry="dishPakanRy" fill="url(#grainPattern)" clip-path="url(#dishClip)" />

                                <!-- Food Dish Label -->
                                <text x="510" y="446" class="fill-slate-500 text-xs font-bold" text-anchor="middle">WADAH / PANCI</text>
                                
                                <!-- Active Feed Level indicator text -->
                                <text v-if="simPakanWadah > 0" x="510" y="372" fill="#10b981" font-size="11" font-weight="bold" text-anchor="middle">
                                    +{{ simPakanWadah }}g
                                </text>
                            </g>

                            <!-- Flow Labels -->
                            <g opacity="0.85">
                                <!-- Flow direction markers -->
                                <polygon points="204,260 200,268 196,260" fill="#10b981" />
                                <polygon points="314,312 322,316 319,308" fill="#10b981" :class="{ 'pulse-wave': simMotorActive }" />
                            </g>
                        </svg>
                    </div>

                    <!-- Footer Legend -->
                    <div class="grid grid-cols-3 gap-2 border-t border-slate-200 pt-3 text-[10px] font-mono text-slate-600">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>Sensor Ultrasonik (Tinggi)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            <span>Load Cell (Berat Pakan)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            <span>Auto-Cut Sensor (Batas)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Panel Kontrol + Informasi -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                
                <!-- 1. Simulasi Kontrol Card -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-50 mb-4 flex items-center gap-2">
                        <Settings class="h-4 w-4 text-indigo-500" />
                        <span>Kontrol Simulasi IoT</span>
                    </h2>

                    <!-- Control Buttons Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button 
                            @click="startSimulation" 
                            :disabled="isSimulating && !isPaused || apiLoading"
                            class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 text-xs transition disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
                        >
                            <Play class="h-4 w-4 fill-current" />
                            Mulai
                        </button>
                        
                        <button 
                            @click="pauseSimulation" 
                            :disabled="!isSimulating || isPaused || apiLoading"
                            class="flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2.5 px-4 text-xs transition disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
                        >
                            <Pause class="h-4 w-4 fill-current" />
                            Jeda
                        </button>

                        <button 
                            @click="stopSimulation" 
                            :disabled="!isSimulating || apiLoading"
                            class="flex items-center justify-center gap-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2.5 px-4 text-xs transition disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
                        >
                            <Square class="h-4 w-4 fill-current" />
                            Stop & Simpan
                        </button>

                        <button 
                            @click="resetSimulation" 
                            :disabled="apiLoading"
                            class="flex items-center justify-center gap-2 rounded-xl bg-slate-600 hover:bg-slate-700 text-white font-semibold py-2.5 px-4 text-xs transition disabled:opacity-50 shadow-xs"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Reset
                        </button>

                        <button 
                            @click="togglePhysicalMotor" 
                            :disabled="apiLoading"
                            :class="simStatus?.status_alat?.status_motor === 'aktif' ? 'bg-rose-500 hover:bg-rose-600' : 'bg-emerald-500 hover:bg-emerald-600'"
                            class="flex items-center justify-center gap-2 rounded-xl text-white font-semibold py-2.5 px-4 text-xs transition col-span-2 shadow-xs"
                        >
                            <Cpu class="h-4 w-4 animate-pulse" v-if="simStatus?.status_alat?.status_motor === 'aktif'" />
                            <Cpu class="h-4 w-4" v-else />
                            {{ simStatus?.status_alat?.status_motor === 'aktif' ? 'Matikan Motor IoT (Manual)' : 'Aktifkan Motor IoT (Manual)' }}
                        </button>
                    </div>

                    <!-- Hardware Simulators / Trigger actions -->
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-4 space-y-2">
                        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Simulasi Kondisi Kritis</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button 
                                @click="triggerStokMenipis" 
                                :disabled="apiLoading"
                                class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-amber-500/30 bg-amber-500/5 hover:bg-amber-500/10 text-amber-700 dark:text-amber-400 font-medium text-xs transition disabled:opacity-50"
                            >
                                <AlertTriangle class="h-4 w-4 shrink-0" />
                                Stok Menipis (15%)
                            </button>

                            <button 
                                @click="triggerAutoCutDirect" 
                                :disabled="apiLoading"
                                class="flex items-center gap-2 px-3.5 py-2 rounded-xl border border-indigo-500/30 bg-indigo-500/5 hover:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 font-medium text-xs transition disabled:opacity-50"
                            >
                                <Zap class="h-4 w-4 shrink-0" />
                                Aktifkan Auto-Cut
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. Panel Informasi Telemetry -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs flex-grow flex flex-col justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-slate-50 mb-4 flex items-center gap-2">
                            <Activity class="h-4 w-4 text-emerald-500" />
                            <span>Parameter & Telemetry Ternak</span>
                        </h2>

                        <!-- Master & Dynamic data cards grid -->
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            
                            <!-- Card 1: Jumlah Ayam -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3 relative group">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Jumlah Ayam</span>
                                
                                <div v-if="isEditingAyam" class="flex items-center gap-1.5 mt-0.5">
                                    <input 
                                        type="number" 
                                        v-model.number="tempJumlahAyam" 
                                        min="1" 
                                        class="w-20 px-1.5 py-0.5 text-xs rounded border border-indigo-300 dark:border-indigo-800 dark:bg-slate-950 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        :disabled="updatingAyam"
                                    />
                                    <button 
                                        @click="saveJumlahAyam" 
                                        :disabled="updatingAyam"
                                        class="p-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded transition disabled:opacity-50"
                                        title="Simpan"
                                    >
                                        <Check class="h-3 w-3" />
                                    </button>
                                    <button 
                                        @click="cancelEditAyam" 
                                        :disabled="updatingAyam"
                                        class="p-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded transition disabled:opacity-50"
                                        title="Batal"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </div>
                                <div v-else class="flex items-center justify-between mt-0.5">
                                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                        {{ simStatus?.periode_aktif?.jumlah_ayam ?? '-' }} ekor
                                    </span>
                                    <button 
                                        v-if="simStatus?.periode_aktif"
                                        @click="startEditAyam"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1 text-slate-450 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-900 rounded cursor-pointer"
                                        title="Ubah populasi"
                                    >
                                        <Edit2 class="h-3 w-3" />
                                    </button>
                                </div>
                            </div>

                            <!-- Card 2: Umur Ayam -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Umur Ayam</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                    {{ simStatus?.periode_aktif ? simStatus.umur_hari + ' hari' : '-' }}
                                </span>
                            </div>

                            <!-- Card 3: Fase Umur -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Fase Umur</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                    {{ simStatus?.fase_umur ?? '-' }}
                                </span>
                            </div>

                            <!-- Card 4: Kebutuhan Gram -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Dosis Harian</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                    {{ simStatus?.kebutuhan_pakan?.gram_per_ekor_per_hari ?? '-' }} g/ekor
                                </span>
                            </div>

                            <!-- Card 5: Frekuensi Pakan -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Frekuensi</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                    {{ simStatus?.kebutuhan_pakan?.frekuensi_pemberian_per_hari ?? '-' }}x / hari
                                </span>
                            </div>

                            <!-- Card 6: Target per Jadwal -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Target Porsi</span>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ computedTargetPakan }} gram
                                </span>
                            </div>

                            <!-- Card 7: Pakan Keluar -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Pakan Keluar</span>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ simPakanWadah }} gram
                                </span>
                            </div>

                            <!-- Card 8: Sisa Stok Estimasi -->
                            <div class="rounded-xl border border-slate-100 dark:border-slate-850 bg-slate-50/50 dark:bg-slate-950/20 p-3">
                                <span class="text-[10px] uppercase font-mono text-slate-400 dark:text-slate-500 block mb-1">Sisa Stok Silo</span>
                                <span class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                    {{ (currentWeightG / 1000).toFixed(2) }} kg
                                </span>
                            </div>

                        </div>

                        <!-- Real-time Progress Bar towards Target Portion -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-4 mb-4">
                            <div class="flex justify-between items-center text-xs text-slate-500 dark:text-slate-400 mb-2">
                                <span class="font-medium">Progress Pemberian Pakan:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ simPakanWadah }} g / {{ computedTargetPakan }} g ({{ progressPercent }}%)
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-850 h-2.5 rounded-full overflow-hidden">
                                <div 
                                    class="bg-indigo-600 h-full rounded-full transition-all duration-200 shadow-sm"
                                    :style="{ width: `${progressPercent}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actuator statuses & warnings -->
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Status Motor:</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" 
                                :class="simMotorActive ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700'">
                                <span v-if="simMotorActive" class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse mr-1"></span>
                                {{ simMotorActive ? 'AKTIF' : 'MATI' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400">Status Auto-Cut:</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" 
                                :class="isAutoCutActive ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700'">
                                <span v-if="isAutoCutActive" class="inline-block h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse mr-1"></span>
                                {{ isAutoCutActive ? 'AKTIF (CUT-OFF)' : 'MATI' }}
                            </span>
                        </div>

                        <!-- Warnings & Auto-Cut info -->
                        <div v-if="isAutoCutActive" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 text-rose-700 dark:text-rose-400 text-xs flex gap-2">
                            <Info class="h-4.5 w-4.5 shrink-0 text-rose-500" />
                            <div>
                                <p class="font-bold">Motor Berhenti Otomatis</p>
                                <p class="text-[11px] opacity-80 mt-0.5">Motor berhenti otomatis karena target porsi pakan tercapai.</p>
                            </div>
                        </div>

                        <div v-if="simStatus?.periode_aktif && !simStatus?.kebutuhan_pakan" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/30 text-amber-700 dark:text-amber-400 text-xs flex gap-2">
                            <AlertTriangle class="h-4.5 w-4.5 shrink-0 text-amber-500" />
                            <div>
                                <p class="font-bold">Kebutuhan Pakan Tidak Ditemukan</p>
                                <p class="text-[11px] opacity-80 mt-0.5">
                                    Fase umur untuk {{ simStatus?.umur_hari }} hari belum diatur.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Bottom: Table Log Simulasi -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-xs">
            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-50 mb-4 flex items-center gap-2">
                <Activity class="h-4 w-4 text-indigo-500" />
                <span>Log Aktivitas Simulasi</span>
            </h2>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50 text-slate-500 dark:text-slate-400 font-semibold">
                            <th class="p-3 w-32">Waktu</th>
                            <th class="p-3 w-40">Komponen</th>
                            <th class="p-3 w-48">Peristiwa</th>
                            <th class="p-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-sans">
                        <tr 
                            v-for="(log, idx) in localLogs" 
                            :key="idx" 
                            class="hover:bg-slate-50/50 dark:hover:bg-slate-800/25 transition-colors"
                        >
                            <td class="p-3 font-mono text-slate-400 dark:text-slate-500 whitespace-nowrap">{{ log.waktu }}</td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="font-semibold text-slate-800 dark:text-slate-200">{{ log.komponen }}</span>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="getPeristiwaClass(log.peristiwa)">
                                    {{ log.peristiwa }}
                                </span>
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">{{ log.keterangan }}</td>
                        </tr>
                        <tr v-if="localLogs.length === 0">
                            <td colspan="4" class="p-8 text-center text-slate-400 dark:text-slate-500 font-medium">
                                Belum ada log aktivitas simulasi. Silakan klik tombol "Mulai" untuk menjalankan simulasi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* Spinning motor keyframe */
@keyframes spin-rotor {
    from {
        transform: rotate(0deg);
        transform-origin: 95px 290px;
    }
    to {
        transform: rotate(360deg);
        transform-origin: 95px 290px;
    }
}
.motor-spin {
    animation: spin-rotor 0.8s linear infinite;
}

/* Auger helical screw spiral shifting keyframe */
@keyframes scroll-screw {
    from {
        stroke-dashoffset: 0;
    }
    to {
        stroke-dashoffset: 48;
    }
}
.auger-screw-active {
    animation: scroll-screw 1.5s linear infinite;
}

/* Ultrasonic wave animation */
@keyframes pulse-wave {
    0% {
        opacity: 0.2;
        transform: scale(0.96);
    }
    50% {
        opacity: 1;
        transform: scale(1.04);
    }
    100% {
        opacity: 0.2;
        transform: scale(0.96);
    }
}
.ultrasonic-wave {
    animation: pulse-wave 1.6s ease-in-out infinite;
    transform-origin: 200px 39px;
}
.wave-1 {
    animation-delay: 0s;
}
.wave-2 {
    animation-delay: 0.4s;
}
.wave-3 {
    animation-delay: 0.8s;
}

/* Feed direction glowing arrow glide */
@keyframes arrow-glide {
    from {
        stroke-dashoffset: 24;
    }
    to {
        stroke-dashoffset: 0;
    }
}
.flow-arrow-active {
    animation: arrow-glide 0.8s linear infinite;
    stroke: #34d399 !important;
}

/* General pulse */
.pulse-wave {
    animation: pulse-wave 1s ease-in-out infinite;
}
</style>
