<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :aria-busy="loading || undefined"
    :class="kelas"
  >
    <!-- Indikator memuat; tombol tetap bisa dibaca pembaca layar lewat teks di slot. -->
    <svg
      v-if="loading"
      class="h-4 w-4 shrink-0 animate-spin"
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
    </svg>

    <slot />
  </button>
</template>

<script setup lang="ts">
interface Props {
  type?: 'button' | 'submit' | 'reset'
  variant?: 'utama' | 'sekunder' | 'hantu' | 'bahaya'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'button',
  variant: 'utama',
  size: 'md',
  loading: false,
  disabled: false,
})

// Varian memetakan ke kelas pil di main.css (DESIGN.md §5 BaseButton).
// Dalam satu kelompok aksi hanya boleh ada satu tombol `utama`.
const kelasVarian: Record<NonNullable<Props['variant']>, string> = {
  utama: 'btn-primer',
  sekunder: 'btn-sekunder',
  hantu: 'btn-hantu',
  bahaya: 'btn-bahaya',
}

const kelasUkuran: Record<NonNullable<Props['size']>, string> = {
  sm: 'btn-sm',
  md: '',
  lg: 'btn-lg',
}

const kelas = computed(() => ['btn', kelasVarian[props.variant], kelasUkuran[props.size]])
</script>
