<template>
  <div>
    <label v-if="label" :for="id" class="mb-1.5 block text-xs font-medium tracking-wider text-teks-redup uppercase">
      {{ label }}<span v-if="required" class="text-bahaya" aria-hidden="true"> *</span>
    </label>

    <textarea
      :id="id"
      v-model="model"
      :rows="rows"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="idDeskripsi"
      class="input h-auto resize-y py-3 leading-relaxed disabled:cursor-not-allowed disabled:opacity-60"
      :class="{ 'input-galat': error }"
    />

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
  placeholder?: string
  rows?: number
  hint?: string
  error?: string
  required?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rows: 4,
  required: false,
  disabled: false,
})

const model = defineModel<string>({ default: '' })

const id = useId()
const idDeskripsi = computed(() => {
  if (props.error) return `${id}-error`
  if (props.hint) return `${id}-hint`
  return undefined
})
</script>
