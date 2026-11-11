<template>
  <!-- Avatar inisial (KNF-02: foto anggota tidak dipakai sebagai avatar).
       Warna dipilih tetap dari id, jadi satu orang selalu berwarna sama. -->
  <span
    class="grid shrink-0 place-items-center rounded-full font-medium text-tombol-teks select-none"
    :class="[kelasUkuran[ukuran], WARNA[indeksWarna]]"
    :style="{ fontSize: ukuran === 'mini' ? '11px' : ukuran === 'kecil' ? '9px' : ukuran === 'besar' ? '15px' : '12px' }"
    :title="nama"
    aria-hidden="true"
  >
    {{ inisial(nama) }}
  </span>
</template>

<script setup lang="ts">
interface Props {
  nama: string | null | undefined
  /** Dipakai untuk memilih warna; biasanya user.id. */
  id?: number | null
  ukuran?: 'mini' | 'kecil' | 'sedang' | 'besar'
}

const props = withDefaults(defineProps<Props>(), {
  id: 0,
  ukuran: 'sedang',
})

const WARNA = ['bg-avatar-1', 'bg-avatar-2', 'bg-avatar-3', 'bg-avatar-4', 'bg-avatar-5']

const kelasUkuran: Record<NonNullable<Props['ukuran']>, string> = {
  mini: 'h-[26px] w-[26px]',
  kecil: 'h-5 w-5',
  sedang: 'h-8 w-8',
  besar: 'h-11 w-11',
}

// Data struktur publik tidak mengirim id, jadi warna diambil dari hash nama
// (DESIGN.md §9.4). Hash sederhana: jumlah kode karakter, dibagi 5.
const indeksWarna = computed(() => {
  if (props.id) return props.id % WARNA.length
  if (!props.nama) return 0

  const jumlah = [...props.nama].reduce((total, huruf) => total + (huruf.codePointAt(0) ?? 0), 0)

  return jumlah % WARNA.length
})
</script>