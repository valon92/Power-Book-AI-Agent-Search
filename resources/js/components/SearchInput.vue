<template>
  <form class="w-full max-w-3xl mx-auto" @submit.prevent="onSubmit">
    <div class="glass rounded-2xl">
      <div class="relative flex items-center">
        <textarea
          v-model="query"
          :placeholder="t('placeholder')"
          rows="2"
          class="w-full resize-none bg-transparent px-5 py-4 pr-[7.5rem] sm:pr-36 min-h-[4.5rem] leading-relaxed text-white placeholder-slate-400 focus:outline-none"
          :disabled="loading"
          @keydown.enter.exact.prevent="onSubmit"
        />
        <button
          type="submit"
          class="btn-primary absolute right-3 top-1/2 -translate-y-1/2 text-sm px-5 py-2.5 shrink-0"
          :disabled="loading || !canSearch"
        >
          <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <span v-else>{{ t('search') }}</span>
        </button>
      </div>

      <div class="flex items-center gap-2 px-3 py-2.5 border-t border-white/10 bg-white/[0.02]">
        <label class="search-media-btn cursor-pointer" :class="{ 'opacity-50 pointer-events-none': loading }">
          <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="sr-only"
            :disabled="loading"
            @change="onFileSelect"
          />
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          {{ t('upload_photo') }}
        </label>

        <label class="search-media-btn cursor-pointer" :class="{ 'opacity-50 pointer-events-none': loading }">
          <input
            type="file"
            accept="image/*"
            capture="environment"
            class="sr-only"
            :disabled="loading"
            @change="onFileSelect"
          />
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ t('take_photo') }}
        </label>

        <button
          v-if="imagePreview"
          type="button"
          class="ml-auto text-xs text-slate-400 hover:text-red-400 transition-colors"
          @click="clearImage"
        >
          {{ t('remove_photo') }}
        </button>
      </div>

      <div v-if="imagePreview" class="px-4 pb-4">
        <div class="relative inline-block rounded-xl overflow-hidden border border-white/15 ring-2 ring-sky-500/30">
          <img :src="imagePreview" alt="" class="h-24 w-auto max-w-full object-contain bg-white/5" />
          <span class="absolute bottom-1 left-1 px-2 py-0.5 rounded text-[10px] bg-sky-500/80 text-white font-medium">
            {{ t('ai_will_analyze') }}
          </span>
        </div>
      </div>

      <div class="px-4 pb-4">
        <SearchScopeChips v-model="locationScope" :disabled="loading" />
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, computed, watch, inject } from 'vue';
import api from '../services/api';
import SearchScopeChips from './SearchScopeChips.vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'search']);
const { t } = inject('i18n');

const query = ref(props.modelValue);
const imagePreview = ref(null);
const imageBase64 = ref(null);
const fileInput = ref(null);
const locationScope = ref(api.getLocationScope());

watch(locationScope, (v) => api.setLocationScope(v));

const canSearch = computed(() => {
  return query.value.trim().length >= 3 || !!imageBase64.value;
});

watch(() => props.modelValue, (v) => { query.value = v; });
watch(query, (v) => emit('update:modelValue', v));

function onFileSelect(event) {
  const file = event.target.files?.[0];
  if (!file || !file.type.startsWith('image/')) return;
  if (file.size > 8 * 1024 * 1024) {
    alert(t('image_too_large'));
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    const result = reader.result;
    imagePreview.value = result;
    imageBase64.value = result.includes('base64,') ? result.split('base64,')[1] : result;
  };
  reader.readAsDataURL(file);
  event.target.value = '';
}

function clearImage() {
  imagePreview.value = null;
  imageBase64.value = null;
}

function onSubmit() {
  if (!canSearch.value) return;
  emit('search', {
    query: query.value.trim(),
    imageBase64: imageBase64.value,
    locationScope: locationScope.value,
  });
}

defineExpose({ clearImage });
</script>

<style scoped>
.search-media-btn {
  @apply inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium
    text-slate-300 bg-white/5 border border-white/10
    hover:bg-white/10 hover:text-white transition-colors;
}
</style>
