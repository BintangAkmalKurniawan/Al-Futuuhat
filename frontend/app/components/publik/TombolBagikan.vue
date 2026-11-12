<template>
  <!-- Bagikan halaman ini -->
  <div class="relative inline-block">
    <AppButton variant="sekunder" size="sm" :aria-expanded="menuTerbuka" @click="bagikan">
      <Share2 class="h-4 w-4" aria-hidden="true" />
      Bagikan
    </AppButton>

    <div
      v-if="menuTerbuka"
      class="absolute left-0 z-20 mt-2 w-48 overflow-hidden rounded-lg border border-garis bg-angkat py-1 shadow-[0_14px_40px_rgba(0,0,0,.5)]"
      role="menu"
    >
      <button
        type="button"
        class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-teks hover:bg-permukaan"
        role="menuitem"
        @click="salinTautan"
      >
        <Link class="h-4 w-4 text-teks-lembut" aria-hidden="true" />
        Salin tautan
      </button>
      <a
        :href="tautanWhatsapp"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center gap-3 px-4 py-2 text-sm text-teks hover:bg-permukaan"
        role="menuitem"
        @click="menuTerbuka = false"
      >
        <MessageCircle class="h-4 w-4 text-teks-lembut" aria-hidden="true" />
        WhatsApp
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, MessageCircle, Share2 } from '@lucide/vue'

const props = defineProps<{ judul: string }>()

const { tampilkan } = usePesanToast()
const menuTerbuka = ref(false)

const alamat = () => window.location.href

const tautanWhatsapp = computed(() =>
  `https://wa.me/?text=${encodeURIComponent(`${props.judul} — ${import.meta.client ? alamat() : ''}`)}`,
)

async function bagikan() {
  if (navigator.share) {
    try {
      await navigator.share({ title: props.judul, url: alamat() })
    } catch {
    }
    return
  }

  menuTerbuka.value = !menuTerbuka.value
}

async function salinTautan() {
  menuTerbuka.value = false

  try {
    await navigator.clipboard.writeText(alamat())
    tampilkan('Tautannya sudah disalin.', 'sukses')
  } catch {
    tampilkan('Tautan belum bisa disalin. Salin dari kolom alamat peramban ya.', 'bahaya')
  }
}
</script>
