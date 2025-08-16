<template>
  <Head :title="`Integrasi Accurate - ${tenantName}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
        Integrasi dengan Accurate Online
      </h1>
      <p class="text-muted-foreground text-lg">
        Hubungkan data tenant <span class="font-semibold">{{ tenantName }}</span> ke Accurate Online.
      </p>
      <!-- Status Integrasi -->
      <div v-if="isIntegrated" class="my-2">
        <Card v-if="tokenValid" class="p-4 flex items-center gap-2 bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700">
          <CheckCircle class="h-5 w-5 text-blue-600" />
          <span class="text-blue-800 dark:text-blue-200 font-semibold">
            Sudah terintegrasi dengan Accurate Online. Token masih aktif.
          </span>
        </Card>
        <Card v-else class="p-4 flex items-center gap-2 bg-yellow-50 dark:bg-yellow-900 border-yellow-200 dark:border-yellow-700">
          <Zap class="h-5 w-5 text-yellow-600" />
          <span class="text-yellow-800 dark:text-yellow-200 font-semibold">
            Sudah login ke Accurate Online, namun token sudah tidak aktif. Silakan login ulang.
          </span>
        </Card>
      </div>
      <div v-if="success" class="my-2">
        <Card class="p-4 flex items-center gap-2 bg-green-50 dark:bg-green-900 border-green-200 dark:border-green-700">
          <CheckCircle class="h-5 w-5 text-green-600" />
          <span class="text-green-800 dark:text-green-200 font-semibold">{{ success }}</span>
        </Card>
      </div>
      <div class="grid gap-4 md:grid-cols-2">
        <Card class="p-6 flex flex-col gap-4 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
          <h3 class="text-xl font-semibold flex items-center gap-2 text-gray-900 dark:text-gray-100">
            <Zap class="h-5 w-5" /> Tindakan Integrasi
          </h3>
          <div class="flex flex-col gap-2">
            <Button
              class="w-full"
              @click="redirectToOAuth"
              :disabled="isIntegrated && tokenValid"
            >
              Login & Integrasi dengan Accurate Online
            </Button>
            <div class="flex gap-2">
              <Button
                variant="secondary"
                class="flex-1"
                :disabled="loadingDbList"
                @click="fetchAccurateDbs"
              >
                {{ loadingDbList ? 'Mengambil DB...' : 'Get List DB' }}
              </Button>
              <Button
                variant="secondary"
                class="flex-1"
                @click="reloginAccurate"
              >
                Relogin Accurate
              </Button>
            </div>
          </div>
          <div class="mt-4 flex flex-col gap-2">
            <label class="font-semibold">Pilih Database Accurate Online:</label>
            <select v-model="selectedDbId" @change="resetSession" class="border rounded px-2 py-1">
              <option value="">-- Pilih Database --</option>
              <option
                v-for="db in accurateDbs"
                :key="db.id"
                :value="db.id"
              >
                {{ db.alias ? db.alias : db.name }} (ID: {{ db.id }})
              </option>
            </select>
            <Button
              class="mt-2 w-full"
              variant="outline"
              :disabled="!selectedDbId || loadingSession"
              @click="getSession"
            >
              {{ loadingSession ? 'Mengambil Session...' : 'Get Session' }}
            </Button>
            <div v-if="sessionStatus" class="text-sm mt-1" :class="sessionStatus === 'Aktif' ? 'text-green-600' : 'text-red-600'">
              Session: {{ sessionId }} ({{ sessionStatus }})
            </div>
          </div>
        </Card>
        <Card class="p-6 flex flex-col gap-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
            Informasi Integrasi
          </h3>
          <ul class="list-disc pl-5 text-muted-foreground text-sm">
            <li>Pastikan Anda sudah login ke Accurate Online.</li>
            <li>Sinkronisasi akan mengirim seluruh data tenant ke Accurate Online.</li>
            <li>Jika belum terintegrasi, lakukan login terlebih dahulu.</li>
            <li v-if="isIntegrated && tokenValid" class="text-blue-600">Status: Sudah terintegrasi & token aktif.</li>
            <li v-else-if="isIntegrated && !tokenValid" class="text-yellow-600">Status: Token sudah tidak aktif, silakan login ulang.</li>
            <li v-else class="text-red-600">Status: Belum terintegrasi.</li>
          </ul>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, usePage, Link, router } from '@inertiajs/vue3'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Zap, CheckCircle } from 'lucide-vue-next'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const page = usePage()
const success = page.props.success || ''
const tenantSlug = page.props.tenantSlug
const tenantName = page.props.tenantName || ''
const isIntegrated = page.props.isIntegrated || false
const tokenValid = page.props.tokenValid || false

const accurateDbs = ref([])
const selectedDbId = ref('')
const sessionId = ref('')
const sessionStatus = ref('')
const loadingSession = ref(false)
const loadingDbList = ref(false)
const error = ref('')

const breadcrumbs = [
  {
    title: 'Integrasi Accurate',
    href: route('accurate.login', { tenantSlug }),
  },
]

function redirectToOAuth() {
  window.location.href = route('accurate.oauth.redirect', { tenantSlug })
}

// Fetch Accurate DB list after login
async function fetchAccurateDbs() {
  loadingDbList.value = true
  error.value = ''
  try {
    const res = await axios.get(`/api/accurate/db-list?tenantSlug=${tenantSlug}`)
    accurateDbs.value = res.data.dbList || []
  } catch (e) {
    error.value = 'Gagal mengambil daftar database Accurate.'
  }
  loadingDbList.value = false
}

// Reset session when DB changes
function resetSession() {
  sessionId.value = ''
  sessionStatus.value = ''
}

// Get session for selected DB
async function getSession() {
  if (!selectedDbId.value) return
  loadingSession.value = true
  error.value = ''
  try {
    // Replace with your backend endpoint that proxies to /open-db.do
    const res = await axios.get(`/api/accurate/open-db?id=${selectedDbId.value}&tenantSlug=${tenantSlug}`)
    sessionId.value = res.data.session || ''
    await checkSession()
  } catch (e) {
    error.value = 'Gagal mengambil session.'
    sessionId.value = ''
    sessionStatus.value = ''
  }
  loadingSession.value = false
}

// Check session status and renew if not active
async function checkSession() {
  if (!sessionId.value) return
  try {
    const res = await axios.get(`/api/accurate/db-check-session?session=${sessionId.value}&tenantSlug=${tenantSlug}`)
    if (res.data.valid) {
      sessionStatus.value = 'Aktif'
    } else {
      // Try to renew session if not active
      const refreshRes = await axios.get(`/api/accurate/db-refresh-session?id=${selectedDbId.value}&session=${sessionId.value}&tenantSlug=${tenantSlug}`)
      sessionId.value = refreshRes.data.session || ''
      // Re-check session after refresh
      const recheckRes = await axios.get(`/api/accurate/db-check-session?session=${sessionId.value}&tenantSlug=${tenantSlug}`)
      sessionStatus.value = recheckRes.data.valid ? 'Aktif' : 'Tidak Aktif'
    }
  } catch (e) {
    sessionStatus.value = 'Tidak Aktif'
  }
}

function syncAll() {
  router.post(route('accurate.syncAll'))
}

function reloginAccurate() {
  redirectToOAuth()
}

// Fetch DBs on mount if integrated
onMounted(() => {
  if (isIntegrated && tokenValid) {
    fetchAccurateDbs()
  }
})
</script>