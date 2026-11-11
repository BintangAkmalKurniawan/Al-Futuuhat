<template>
  <div>
    <label v-if="label" :for="id" class="mb-1.5 block text-xs font-medium tracking-wider text-teks-redup uppercase">
      {{ label }}<span v-if="required" class="text-bahaya" aria-hidden="true"> *</span>
    </label>

    <select
      :id="id"
      v-model="model"
      :disabled="disabled"
      :required="required"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="idDeskripsi"
      class="input disabled:cursor-not-allowed disabled:opacity-60"
      :class="{ 'input-galat': error }"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option v-for="item in opsi" :key="item.nilai" :value="item.nilai">
        {{ item.label }}
      </option>
    </select>

    <p v-if="error" :id="`${id}-error`" class="pesan-galat" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" :id="`${id}-hint`" class="mt-1.5 text-xs text-teks-redup">
      {{ hint }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Opsi {
  nilai: string | number
  label: string
}

interface Props {
  opsi: Opsi[]
  label?: string
  placeholder?: string
  hint?: string
  error?: string
  required?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  required: false,
  disabled: false,
})

const model = defineModel<string | number | null>({ default: '' })

const id = useId()
const idDeskripsi = computed(() => {
  if (props.error) return `${id}-error`
  if (props.hint) return `${id}-hint`
  return undefined
})
</script>
