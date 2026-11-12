<template>
  <div>
    <label v-if="label" :for="id" class="mb-1.5 block text-xs font-medium tracking-wider text-teks-redup uppercase">
      {{ label }}<span v-if="required" class="text-bahaya" aria-hidden="true"> *</span>
    </label>

    <input
      :id="id"
      v-model="model"
      :type="type"
      :autocomplete="autocomplete"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="idDeskripsi"
      class="input disabled:cursor-not-allowed disabled:opacity-60"
      :class="{ 'input-galat': error, 'input-ro': readonly }"
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
  type?: 'text' | 'email' | 'password' | 'search' | 'tel' | 'number' | 'url' | 'date'
  placeholder?: string
  autocomplete?: string
  hint?: string
  error?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  required: false,
  disabled: false,
  readonly: false,
})

const model = defineModel<string>({ default: '' })

const id = useId()
const idDeskripsi = computed(() => {
  if (props.error) return `${id}-error`
  if (props.hint) return `${id}-hint`
  return undefined
})
</script>
