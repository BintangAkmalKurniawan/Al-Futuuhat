<template>
  <!-- Laci riwayat versi -->
  <div
    v-if="open"
    class="fixed inset-0 z-40"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="idJudul"
  >
    <div class="absolute inset-0 bg-black/60" @click="tutup" />

    <aside
      class="absolute top-0 right-0 flex h-full w-[min(440px,100%)] flex-col border-l border-garis bg-permukaan"
    >
      <header class="border-b border-garis px-6 py-4">
        <div class="flex items-center justify-between gap-4">
          <h2 :id="idJudul" class="text-sm font-medium text-teks-kuat">Riwayat perubahan</h2>

          <button
            type="button"
            class="-m-1 rounded-full p-1 text-teks-redup transition-colors duration-200 hover:text-teks-kuat"
            aria-label="Tutup riwayat perubahan"
            @click="tutup"
          >
            <X class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>

        <p class="mt-2 text-[12px] leading-relaxed text-teks-redup">{{ catatanPeran }}</p>
      </header>

      <div class="flex-1 overflow-y-auto px-6 py-5">
        <p v-if="daftar.length === 0" class="text-sm text-teks-lembut">Belum ada riwayat perubahan.</p>

        <ol v-else class="space-y-5">
          <li
            v-for="versi in daftar"
            :key="versi.id"
            class="relative border-b border-garis pb-5 last:border-0 last:pb-0"
          >
            <span
              v-if="versi.id === aktifId"
              class="absolute top-[18px] bottom-[18px] -left-6 w-0.5 bg-aksen"
              aria-hidden="true"
            />

            <div class="flex flex-wrap items-center gap-2">
              <span class="text-sm text-teks-kuat">v{{ versi.nomor_versi }}</span>
              <StatusChip :status="chip(versi)" />
              <span class="text-[13px] text-teks-redup">{{ formatTanggal(versi.dibuat_pada) }}</span>
            </div>

            <p v-if="versi.catatan_revisi" class="mt-2 text-[13px] break-words whitespace-pre-line text-teks">
              {{ versi.catatan_revisi }}
            </p>

            <div v-if="keputusan(versi)" class="mt-3 rounded-lg bg-angkat px-3 py-2 text-[13px]">
              <p class="text-teks-kuat">
                {{ keputusan(versi)!.admin?.nama || 'Pengurus' }}
                {{ keputusan(versi)!.keputusan === 'disetujui' ? 'menyetujui' : 'menolak' }}
                <span v-if="keputusan(versi)!.dibuat_pada" class="font-normal text-teks-lembut">
                  · {{ formatTanggalWaktu(keputusan(versi)!.dibuat_pada) }}
                </span>
              </p>
              <p v-if="keputusan(versi)!.catatan" class="mt-1 break-words text-teks-lembut">
                {{ keputusan(versi)!.catatan }}
              </p>
            </div>

            <div class="mt-3">
              <span
                v-if="versi.id === aktifId"
                class="inline-flex items-center gap-2 text-[13px] text-teks-redup"
              >
                <BookOpen class="h-4 w-4" aria-hidden="true" />
                Sedang dibaca
              </span>

              <button
                v-else
                type="button"
                class="inline-flex items-center gap-2 text-[13px] text-teks-lembut transition-colors duration-200 hover:text-teks-kuat disabled:opacity-60"
                :disabled="memuatId === versi.id"
                @click="emit('buka-versi', versi.id)"
              >
                <BookOpen class="h-4 w-4" aria-hidden="true" />
                {{ memuatId === versi.id ? 'Memuat isi versi…' : 'Lihat isi versi ini' }}
              </button>
            </div>
          </li>
        </ol>
      </div>

      <p class="border-t border-garis px-6 py-4 text-[12px] leading-relaxed text-teks-redup">
        Pilih "Lihat isi versi ini" untuk membaca versi lama. Versi terbaru bisa dibuka lagi kapan
        saja.
      </p>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { BookOpen, X } from '@lucide/vue'
import type { RevisiRingkas, StatusMateri, Verifikasi } from '~/types/api'

defineProps<{
  daftar: RevisiRingkas[]
  aktifId?: number | null
  memuatId?: number | null
}>()

const emit = defineEmits<{
  (e: 'buka-versi', id: number): void
}>()

const open = defineModel<boolean>('open', { default: false })

const idJudul = useId()

const { isAdmin } = useAuth()

const catatanPeran = computed(() =>
  isAdmin.value
    ? 'Catatan admin hanya dapat dilihat oleh Anda dan penulis materi.'
    : 'Catatan dari admin hanya bisa dilihat penulisnya. Anggota lain hanya melihat daftar versinya.',
)

function tutup() {
  open.value = false
}

function keputusan(versi: RevisiRingkas): Verifikasi | null {
  return versi.verifikasi?.[0] ?? null
}

// Chip versi
function chip(versi: RevisiRingkas): StatusMateri {
  if (versi.status === 'disetujui') return 'terbit'

  return versi.status
}

// Tutup dengan Escape
function tekanTombol(event: KeyboardEvent) {
  if (event.key === 'Escape' && open.value) tutup()
}

onMounted(() => document.addEventListener('keydown', tekanTombol))
onBeforeUnmount(() => document.removeEventListener('keydown', tekanTombol))
</script>
