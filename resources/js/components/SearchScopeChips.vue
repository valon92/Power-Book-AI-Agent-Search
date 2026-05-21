<template>
  <div class="mt-3 text-left">
    <p class="text-[11px] uppercase tracking-wider text-slate-500 mb-2">
      {{ t('location_scope_title') }}
    </p>
    <div class="scope-scroll" role="group" :aria-label="t('location_scope_title')">
      <div class="scope-scroll-track">
        <button
          v-for="opt in options"
          :key="opt.value"
          type="button"
          class="scope-chip"
          :class="{ 'scope-chip--active': modelValue === opt.value }"
          :disabled="disabled"
          @click="$emit('update:modelValue', opt.value)"
        >
          {{ opt.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import api from '../services/api';

defineProps({
  modelValue: { type: String, default: 'auto' },
  disabled: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const { t } = inject('i18n');
const cityName = ref(null);

onMounted(async () => {
  try {
    const geo = await api.getGeo();
    cityName.value = geo?.city || null;
  } catch {
    cityName.value = null;
  }
});

const options = computed(() => [
  { value: 'auto', label: t('scope_auto') },
  { value: 'city', label: cityName.value ? t('scope_city_named', { city: cityName.value }) : t('scope_city') },
  { value: 'country', label: t('scope_country') },
  { value: 'region', label: t('scope_region') },
  { value: 'world', label: t('scope_world') },
]);
</script>

<style scoped>
/* Mobile: swipe left ↔ right; desktop: wrap if needed */
.scope-scroll {
  @apply -mx-4 px-4 sm:mx-0 sm:px-0;
}

.scope-scroll-track {
  @apply flex flex-nowrap gap-1.5 overflow-x-auto overflow-y-hidden pb-1
    scroll-smooth snap-x snap-mandatory
    sm:flex-wrap sm:overflow-visible sm:snap-none;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior-x: contain;
  scrollbar-width: none;
}

.scope-scroll-track::-webkit-scrollbar {
  display: none;
}

.scope-chip {
  @apply shrink-0 snap-start whitespace-nowrap px-2.5 py-1 rounded-lg text-[11px] font-medium
    text-slate-400 bg-white/5 border border-white/10
    hover:bg-white/10 hover:text-slate-200 transition-colors
    disabled:opacity-50 disabled:pointer-events-none;
}

.scope-chip--active {
  @apply text-sky-200 bg-sky-500/15 border-sky-500/30;
}
</style>
