<template>
  <!-- Komentar baru berupa HTML Quill; komentar lama tetap teks biasa. -->
  <div class="text-[15px] break-words text-teks">
    <div v-if="html" class="komentar-kaya" v-html="rapikanSpasiHtml(html)" />
    <p v-else-if="isi" class="whitespace-pre-line">{{ isi }}</p>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ isi: string | null | undefined }>()

// Tag yang boleh muncul dari editor komentar (sama dengan profil Purifier backend).
const TAG_DIIZINKAN = new Set([
  'p',
  'br',
  'strong',
  'b',
  'em',
  'i',
  'u',
  's',
  'sub',
  'sup',
  'blockquote',
  'pre',
  'code',
  'ol',
  'ul',
  'li',
  'a',
  'img',
  'span',
  'div',
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
])

/**
 * Hanya perlakukan sebagai HTML bila berbentuk blok Quill dan seluruh tag
 * berada di daftar aman. Komentar lama yang tersimpan sebagai teks biasa
 * (termasuk yang memuat karakter "<") tetap dirender sebagai teks.
 */
const html = computed(() => {
  const nilai = props.isi?.trim() ?? ''

  if (!/^<(p|h[1-6]|ul|ol|blockquote|pre)\b/i.test(nilai)) return null

  const namaTag = [...nilai.matchAll(/<\s*\/?\s*([a-z0-9]+)/gi)].map((cocok) =>
    cocok[1].toLowerCase(),
  )

  if (namaTag.some((nama) => !TAG_DIIZINKAN.has(nama))) return null
  if (/\son\w+\s*=/i.test(nilai) || /(javascript|data):/i.test(nilai)) return null

  return nilai
})
</script>