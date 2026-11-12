<template>
  <div :role="peran" :class="['rounded-lg border px-4 py-3 text-sm', kelasVarian[variant]]">
    <p v-if="title" class="font-medium">{{ title }}</p>
    <div :class="title ? 'mt-1' : ''">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'info' | 'sukses' | 'peringatan' | 'bahaya' | 'aksen'
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'info',
})

const peran = computed(() => (props.variant === 'bahaya' ? 'alert' : 'status'))

const kelasVarian: Record<NonNullable<Props['variant']>, string> = {
  info: 'border-garis bg-permukaan text-teks-lembut',
  sukses: 'border-terbit/30 bg-terbit/10 text-terbit',
  peringatan: 'border-diajukan/30 bg-diajukan/10 text-diajukan',
  bahaya: 'border-bahaya/30 bg-bahaya/10 text-bahaya',
  aksen: 'border-aksen/35 bg-aksen/10 text-teks',
}
</script>
