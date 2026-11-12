<template>
  <div class="space-y-2">
    <p v-if="label" class="text-xs font-medium tracking-wider text-teks-redup uppercase">
      {{ label }}<span v-if="required" class="text-bahaya" aria-hidden="true"> *</span>
    </p>

    <div class="prosa-editor" :class="{ komentar }">
      <div v-if="!komentar" ref="wadah" />

      <template v-else>
        <div class="ql-bilah">
          <div ref="wadahToolbar" class="ql-bilah-toolbar">
            <button type="button" class="ql-bold" aria-label="Tebal" />
            <button type="button" class="ql-italic" aria-label="Miring" />
            <button type="button" class="ql-link" aria-label="Tautan" />
            <button v-if="lampiran" type="button" class="ql-image" aria-label="Lampirkan gambar" />
          </div>

          <span class="flex-1" />

          <slot name="aksi" />
        </div>

        <div ref="wadahIsi" />

        <!-- Petunjuk di bawah bilah -->
        <p v-if="petunjukBawah" class="help">{{ petunjukBawah }}</p>
      </template>
    </div>

    <p v-if="mengunggah" class="text-xs text-teks-redup">Mengunggah gambar...</p>
    <p v-else-if="galatUnggah" class="pesan-galat" role="alert">{{ galatUnggah }}</p>
    <p v-else-if="error" class="pesan-galat" role="alert">{{ error }}</p>
    <p v-else-if="hint && !komentar" class="text-xs text-teks-redup">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import 'quill/dist/quill.snow.css'

import type Quill from 'quill'
import type { Delta } from 'quill'
import type { KontenQuill } from '~/types/api'

interface Props {
  label?: string
  hint?: string
  error?: string
  required?: boolean
  placeholder?: string
  komentar?: boolean
  lampiran?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  required: false,
  komentar: false,
  lampiran: false,
  placeholder: 'Tulis isi materi di sini...',
})

const emit = defineEmits<{ (e: 'lampiran', berkas: File | null): void }>()

const konten = defineModel<KontenQuill>('konten', { default: () => ({ ops: [] }) })
const html = defineModel<string>('html', { default: '' })

const { unggahGambar } = useMateri()

const wadah = ref<HTMLDivElement | null>(null)
const wadahIsi = ref<HTMLDivElement | null>(null)
const wadahToolbar = ref<HTMLDivElement | null>(null)

const mengunggah = ref(false)
const galatUnggah = ref('')

let editor: Quill | null = null

const petunjukBawah = computed(() =>
  props.komentar && props.hint && !props.error && !galatUnggah.value && !mengunggah.value
    ? props.hint
    : '',
)

// Handler tombol gambar Quill
function pilihGambar() {
  const masukan = document.createElement('input')
  masukan.type = 'file'
  masukan.accept = 'image/jpeg,image/png'

  masukan.onchange = async () => {
    const berkas = masukan.files?.[0]
    if (!berkas) return

    galatUnggah.value = ''

    if (!['image/jpeg', 'image/png'].includes(berkas.type)) {
      galatUnggah.value = 'Gambarnya harus JPG atau PNG ya.'
      return
    }

    if (berkas.size > 2 * 1024 * 1024) {
      galatUnggah.value = 'Gambarnya kebesaran, maksimal 2 MB ya.'
      return
    }

    if (props.lampiran) {
      emit('lampiran', berkas)
      return
    }

    if (!editor) return

    mengunggah.value = true

    try {
      const respons = await unggahGambar(berkas)
      const posisi = editor.getSelection(true)?.index ?? editor.getLength()

      editor.insertEmbed(posisi, 'image', respons.url, 'user')
      editor.setSelection(posisi + 1, 0, 'silent')
    } catch (galatServer) {
      galatUnggah.value = pesanGalat(galatServer, 'Gambar belum berhasil diunggah. Coba lagi ya.')
    } finally {
      mengunggah.value = false
    }
  }

  masukan.click()
}

onMounted(async () => {
  const elIsi = props.komentar ? wadahIsi.value : wadah.value
  if (!elIsi) return

  const { default: QuillKelas } = await import('quill')

  const toolbar = props.komentar
    ? { container: wadahToolbar.value as HTMLDivElement, handlers: { image: pilihGambar } }
    : {
        container: [
          [{ header: [2, 3, false] }],
          ['bold', 'italic'],
          ['link', 'image'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['blockquote'],
        ],
        handlers: { image: pilihGambar },
      }

  editor = new QuillKelas(elIsi, {
    theme: 'snow',
    placeholder: props.placeholder,
    modules: { toolbar },
  })

  const isiAwal = konten.value as unknown as Delta | null

  if (isiAwal && Array.isArray(isiAwal.ops) && isiAwal.ops.length > 0) {
    editor.setContents(isiAwal, 'silent')
  } else if (html.value) {
    editor.setContents(editor.clipboard.convert({ html: html.value }), 'silent')
  }

  editor.on('text-change', () => {
    if (!editor) return

    konten.value = editor.getContents() as unknown as KontenQuill
    html.value = rapikanSpasiHtml(editor.getSemanticHTML())
  })
})

onBeforeUnmount(() => {
  editor = null
})
</script>
