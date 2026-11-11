<template>
  <!-- Panel sederhana bergaris tipis; bukan kartu berbayang (DESIGN.md §11). -->
  <section class="rounded-lg bg-permukaan ring-1 ring-garis">
    <header v-if="$slots.header || title" class="border-b border-garis px-6 py-4">
      <slot name="header">
        <h2 class="text-sm font-medium text-teks-kuat">{{ title }}</h2>
      </slot>
    </header>

    <div class="px-6 py-5">
      <slot />
    </div>

    <footer v-if="$slots.footer" class="border-t border-garis px-6 py-4">
      <slot name="footer" />
    </footer>
  </section>
</template>

<script setup lang="ts">
defineProps<{ title?: string }>()
</script>
