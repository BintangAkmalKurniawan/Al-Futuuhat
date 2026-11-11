<template>
  <!-- Pemilih PIC program (KP-21.2): satu atau lebih anggota aktif.
       Pola pencariannya sama dengan pemilih anggota di tab Pengurus. -->
  <div>
    <p class="mb-1.5 text-xs font-medium tracking-wider text-teks-redup uppercase">
      PIC (penanggung jawab)<span class="text-bahaya" aria-hidden="true"> *</span>
    </p>

    <!-- PIC terpilih -->
    <ul v-if="model.length" class="mb-3 flex flex-wrap gap-2">
      <li
        v-for="orang in model"
        :key="orang.anggota_id"
        class="inline-flex items-center gap-2 rounded-full bg-angkat py-1 pr-1 pl-1.5 text-sm text-teks-kuat ring-1 ring-garis"
      >
        <AvatarInisial :nama="orang.nama" :id="orang.anggota_id" ukuran="kecil" />
        {{ orang.nama || `Anggota #${orang.anggota_id}` }}
        <button
          type="button"
          class="grid h-6 w-6 place-items-center rounded-full text-teks-redup transition-colors duration-200 hover:text-teks-kuat"
          :aria-label="`Lepas ${orang.nama || 'anggota ini'} dari PIC`"
          @click="lepas(orang.anggota_id)"
        >
          <X class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
      </li>
    </ul>

    <!-- Bukan SearchPill: komponen ini berada di dalam <form> formulir program. -->
    <input
      v-model="kataKunci"
      type="search"
      class="input"
      placeholder="Cari nama atau email anggota aktif..."
      aria-label="Cari anggota untuk dijadikan PIC"
      @keydown.enter.prevent
    />

    <div class="mt-2 max-h-56 overflow-y-auto rounded-md ring-1 ring-garis">
      <p v-if="mencari" class="px-3 py-3 text-[13px] text-teks-redup">Mencari anggota...</p>
      <p v-else-if="hasil.length === 0" class="px-3 py-3 text-[13px] text-teks-redup">
        Tidak ada anggota aktif yang cocok.
      </p>

      <ul v-else class="divide-y divide-garis">
        <li v-for="item in hasil" :key="item.id">
          <button
            type="button"
            class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left transition-colors duration-200 hover:bg-permukaan"
            :aria-pressed="terpilih(item.anggota?.id)"
            @click="alihkan(item)"
          >
            <span class="min-w-0">
              <span class="block truncate text-sm text-teks">{{ item.nama }}</span>
              <span class="block truncate text-[13px] text-teks-redup">{{ item.email }}</span>
            </span>
            <Check
              v-if="terpilih(item.anggota?.id)"
              class="h-4 w-4 shrink-0 text-aksen"
              aria-hidden="true"
            />
          </button>
        </li>
      </ul>
    </div>

    <p class="mt-1.5 text-[13px] text-teks-redup">
      Pilih satu atau lebih anggota aktif, misalnya ketua atau koordinator kegiatan.
    </p>
    <p v-if="error" class="pesan-galat" role="alert">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { Check, X } from '@lucide/vue'
import type { PicProgram, User } from '~/types/api'

defineProps<{ error?: string }>()

const model = defineModel<PicProgram[]>({ default: () => [] })

const { daftarAnggota } = useAnggota()

const kataKunci = ref('')
const hasil = ref<User[]>([])
const mencari = ref(false)

let nomorPermintaan = 0
let penunda: ReturnType<typeof setTimeout> | undefined

async function cari() {
  const nomor = ++nomorPermintaan
  mencari.value = true

  try {
    const respons = await daftarAnggota({ q: kataKunci.value.trim() || undefined, status: 'aktif' })

    // Hanya akun yang punya baris anggota yang dapat dijadikan PIC.
    if (nomor === nomorPermintaan) hasil.value = respons.data.filter((item) => item.anggota?.id)
  } catch {
    if (nomor === nomorPermintaan) hasil.value = []
  } finally {
    if (nomor === nomorPermintaan) mencari.value = false
  }
}

function terpilih(anggotaId: number | undefined) {
  return model.value.some((orang) => orang.anggota_id === anggotaId)
}

/** Klik baris hasil: pilih bila belum, lepas bila sudah. */
function alihkan(item: User) {
  const anggotaId = item.anggota?.id
  if (!anggotaId) return

  if (terpilih(anggotaId)) lepas(anggotaId)
  else model.value = [...model.value, { anggota_id: anggotaId, nama: item.nama }]
}

function lepas(anggotaId: number) {
  model.value = model.value.filter((orang) => orang.anggota_id !== anggotaId)
}

// Pencarian dengan jeda ±350 ms (satu permintaan per jeda).
watch(kataKunci, () => {
  clearTimeout(penunda)
  penunda = setTimeout(cari, 350)
})

onMounted(cari)
onBeforeUnmount(() => clearTimeout(penunda))
</script>
