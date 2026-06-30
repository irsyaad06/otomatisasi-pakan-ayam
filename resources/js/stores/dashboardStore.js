import { defineStore } from 'pinia';
import api from '../services/api';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        // Telemetry dashboard
        dashboard: {
            stok_pakan_terbaru: null,
            status_alat: null,
            jadwal_pakan_berikutnya: null,
            total_pakan_hari_ini: 0,
            log_terbaru: []
        },
        
        // Full collections
        jadwalList: [],
        stokList: [],
        logList: [],
        statusList: [],

        // Chart data
        stokChartData: [],
        logChartData: [],

        // Peramalan stok pakan
        ramalanStok: null,

        // Loading states
        loading: false,
        actionLoading: false,
        error: null,
    }),
    actions: {
        async fetchDashboard() {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/dashboard');
                if (response.data && response.data.status) {
                    this.dashboard = response.data.data;
                } else {
                    this.error = response.data.message || 'Gagal memuat data dashboard';
                }
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || 'Terjadi kesalahan jaringan';
            } finally {
                this.loading = false;
            }
        },

        async fetchCharts() {
            try {
                const [stokRes, logRes] = await Promise.all([
                    api.get('/grafik/stok-pakan'),
                    api.get('/grafik/log-pakan')
                ]);
                
                if (stokRes.data?.status) {
                    this.stokChartData = stokRes.data.data;
                }
                if (logRes.data?.status) {
                    this.logChartData = logRes.data.data;
                }
            } catch (err) {
                console.error('Gagal mengambil data chart:', err);
            }
        },
        
        // --- JADWAL PAKAN CRUD ---
        async fetchJadwal() {
            this.loading = true;
            try {
                const response = await api.get('/jadwal-pakan');
                if (response.data && response.data.status) {
                    this.jadwalList = response.data.data;
                }
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || 'Gagal mengambil jadwal pakan';
            } finally {
                this.loading = false;
            }
        },

        async tambahJadwal(payload) {
            this.actionLoading = true;
            try {
                const response = await api.post('/jadwal-pakan', payload);
                await this.fetchJadwal();
                await this.fetchDashboard();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async updateJadwal(id, payload) {
            this.actionLoading = true;
            try {
                const response = await api.put(`/jadwal-pakan/${id}`, payload);
                await this.fetchJadwal();
                await this.fetchDashboard();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async hapusJadwal(id) {
            this.actionLoading = true;
            try {
                const response = await api.delete(`/jadwal-pakan/${id}`);
                await this.fetchJadwal();
                await this.fetchDashboard();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        // --- STOK PAKAN CRUD ---
        async fetchStok() {
            this.loading = true;
            try {
                const [stokRes, ramalanRes] = await Promise.all([
                    api.get('/stok-pakan'),
                    api.get('/stok-pakan/peramalan')
                ]);
                
                if (stokRes.data && stokRes.data.status) {
                    this.stokList = stokRes.data.data;
                }
                
                if (ramalanRes.data && ramalanRes.data.status) {
                    this.ramalanStok = ramalanRes.data.data;
                }
            } catch (err) {
                console.error('Gagal mengambil data stok/peramalan:', err);
            } finally {
                this.loading = false;
            }
        },

        async fetchRamalanStok() {
            try {
                const response = await api.get('/stok-pakan/peramalan');
                if (response.data && response.data.status) {
                    this.ramalanStok = response.data.data;
                }
            } catch (err) {
                console.error('Gagal mengambil data peramalan:', err);
            }
        },

        async tambahStok(payload) {
            this.actionLoading = true;
            try {
                const response = await api.post('/stok-pakan', payload);
                await this.fetchStok();
                await this.fetchDashboard();
                await this.fetchCharts();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async updateStok(id, payload) {
            this.actionLoading = true;
            try {
                const response = await api.put(`/stok-pakan/${id}`, payload);
                await this.fetchStok();
                await this.fetchDashboard();
                await this.fetchCharts();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async hapusStok(id) {
            this.actionLoading = true;
            try {
                const response = await api.delete(`/stok-pakan/${id}`);
                await this.fetchStok();
                await this.fetchDashboard();
                await this.fetchCharts();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        // --- LOG PEMBERIAN PAKAN CRUD ---
        async fetchLogs() {
            this.loading = true;
            try {
                const response = await api.get('/log-pemberian-pakan');
                if (response.data && response.data.status) {
                    this.logList = response.data.data;
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async tambahLog(payload) {
            this.actionLoading = true;
            try {
                const response = await api.post('/log-pemberian-pakan', payload);
                await this.fetchLogs();
                await this.fetchDashboard();
                await this.fetchCharts();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async updateLog(id, payload) {
            this.actionLoading = true;
            try {
                const response = await api.put(`/log-pemberian-pakan/${id}`, payload);
                await this.fetchLogs();
                await this.fetchDashboard();
                await this.fetchCharts();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async hapusLog(id) {
            this.actionLoading = true;
            try {
                const response = await api.delete(`/log-pemberian-pakan/${id}`);
                await this.fetchLogs();
                await this.fetchDashboard();
                await this.fetchCharts();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        // --- STATUS ALAT CRUD ---
        async fetchStatus() {
            this.loading = true;
            try {
                const response = await api.get('/status-alat');
                if (response.data && response.data.status) {
                    this.statusList = response.data.data;
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async tambahStatus(payload) {
            this.actionLoading = true;
            try {
                const response = await api.post('/status-alat', payload);
                await this.fetchStatus();
                await this.fetchDashboard();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async updateStatus(id, payload) {
            this.actionLoading = true;
            try {
                const response = await api.put(`/status-alat/${id}`, payload);
                await this.fetchStatus();
                await this.fetchDashboard();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        async hapusStatus(id) {
            this.actionLoading = true;
            try {
                const response = await api.delete(`/status-alat/${id}`);
                await this.fetchStatus();
                await this.fetchDashboard();
                return response.data;
            } catch (err) {
                console.error(err);
                throw err;
            } finally {
                this.actionLoading = false;
            }
        },

        // --- SIMULASI Telemetry ---
        async simulasiKirimData() {
            this.loading = true;
            try {
                const randomBerat = Math.floor(Math.random() * (48000 - 25000 + 1)) + 25000;
                const persentase = Math.round((randomBerat / 50000) * 100);
                const statusStok = persentase < 20 ? 'hampir_habis' : 'aman';
                
                const payload = {
                    berat_pakan_gram: randomBerat,
                    persentase_stok: persentase,
                    status_stok: statusStok,
                    status_koneksi: 'online',
                    status_motor: 'mati',
                    status_sensor: 'normal'
                };
                
                await api.post('/iot/kirim-data', payload);
                await this.fetchDashboard();
                await this.fetchStok();
                await this.fetchCharts();
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || 'Gagal mengirim simulasi data ESP32';
            } finally {
                this.loading = false;
            }
        },

        async simulasiPakan() {
            this.loading = true;
            try {
                await api.post('/iot/simulasi-pakan');
                await this.fetchDashboard();
                await this.fetchLogs();
                await this.fetchStok();
                await this.fetchCharts();
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || 'Gagal menjalankan simulasi pakan';
            } finally {
                this.loading = false;
            }
        },

        async simulasiStokMenipis() {
            this.loading = true;
            try {
                await api.post('/iot/simulasi-stok-menipis');
                await this.fetchDashboard();
                await this.fetchStok();
                await this.fetchCharts();
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || 'Gagal menjalankan simulasi stok menipis';
            } finally {
                this.loading = false;
            }
        },

        async simulasiAutoCut() {
            this.loading = true;
            try {
                await api.post('/iot/simulasi-auto-cut');
                await this.fetchDashboard();
                await this.fetchLogs();
                await this.fetchStok();
                await this.fetchCharts();
            } catch (err) {
                console.error(err);
                this.error = err.response?.data?.message || 'Gagal menjalankan simulasi auto-cut';
            } finally {
                this.loading = false;
            }
        }
    }
});
