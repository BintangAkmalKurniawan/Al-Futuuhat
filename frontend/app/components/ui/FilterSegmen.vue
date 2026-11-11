<template>
  <!-- Filter segmen: pil berisi beberapa pilihan status (DESIGN.md §5, layar 18 & 20). -->
  <div class="seg" role="group" :aria-label="label">
    <button
      v-for="opsi in opsi"
      :key="opsi.nilai"
      type="button"
      :aria-pressed="model === opsi.nilai"
      @click="model = opsi.nilai"
    >
      {{ opsi.label }}<span v-if="opsi.jumlah !== undefined" class="ml-1.5">{{ opsi.jumlah }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
interface OpsiSegmen {
  nilai: string
  label: string
  /** Angka jumlah di sebelah label (opsional). */
  jumlah?: number
}

withDefaults(defineProps<{ opsi: OpsiSegmen[]; label?: string }>(), {
  label: 'Filter status',
})

const model = defineModel<string>({ required: true })
</script>
