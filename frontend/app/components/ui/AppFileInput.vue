<template>
  <div>
    <label v-if="label" :for="id" class="mb-1.5 block text-xs font-medium tracking-wider text-teks-redup uppercase">
      {{ label }}
    </label>

    <input
      :id="id"
      ref="input"
      type="file"
      :accept="accept"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="idDeskripsi"
      class="block w-full cursor-pointer rounded-lg bg-permukaan text-sm text-teks-lembut ring-1 transition-colors duration-200 file:mr-3 file:cursor-pointer file:border-0 file:bg-aksen file:px-3 file:py-2 file:text-sm file:font-medium file:text-tombol-teks hover:file:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
      :class="error ? 'ring-bahaya' : 'ring-garis-kuat'"
      @change="pilihBerkas"
    />

    <div v-if="model" class="mt-2 flex items-center gap-3">
      <img
        v-if="pratinjau"
        :src="pratinjau"
        alt="Pratinjau gambar yang dipilih"
        class="h-16 w-16 rounded-sm border border-garis object-cover"
      />
      <div class="min-w-0">
        <p class="truncate text-xs text-teks">{{ model.name }}</p>
        <button type="button" class="mt-0.5 text-xs text-bahaya hover:underline" @click="bersihkan">
          Hapus pilihan
        </button>
      </div>
    </div>

    <p v-if="error" :id="`${id}-error`" class="pesan-galat" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${id}-hint`" class="mt-1.5 text-xs text-teks-redup">
      {{ hint }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label?: string
  hint?: string
  error?: string
  accept?: string
}

const props = withDefaults(defineProps<Props>(), {
  accept: 'image/jpeg,image/png',
})

const model = defineModel<File | null>({ default: null })

const input = ref<HTMLInputElement | null>(null)
const pratinjau = ref('')

const id = useId()
const idDeskripsi = computed(() => {
  if (props.error) return `${id}-error`
  if (props.hint) return `${id}-hint`
  return undefined
})

function pilihBerkas(event: Event) {
  const berkas = (event.target as HTMLInputElement).files?.[0] ?? null

  bersihkanPratinjau()
  model.value = berkas

  if (berkas) pratinjau.value = URL.createObjectURL(berkas)
}

function bersihkan() {
  model.value = null
  bersihkanPratinjau()

  if (input.value) input.value.value = ''
}

function bersihkanPratinjau() {
  if (pratinjau.value) URL.revokeObjectURL(pratinjau.value)
  pratinjau.value = ''
}

onBeforeUnmount(bersihkanPratinjau)
</script>
