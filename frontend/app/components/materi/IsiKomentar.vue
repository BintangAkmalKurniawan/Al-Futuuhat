<template>
  <div class="text-[15px] break-words text-teks">
    <div v-if="html" class="komentar-kaya" v-html="rapikanSpasiHtml(html)" />
    <p v-else-if="isi" class="whitespace-pre-line">{{ isi }}</p>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ isi: string | null | undefined }>()

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