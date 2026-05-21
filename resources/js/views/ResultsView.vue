<template>
  <section class="px-4 sm:px-6 lg:px-8 pb-16">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <router-link to="/" class="text-sm text-slate-400 hover:text-sky-400 transition-colors">
          ← Powerbook.ai
        </router-link>
        <h1 class="text-2xl sm:text-3xl font-bold mt-4">
          {{ t('results_for') }}
          <span class="text-sky-400">"{{ displayQuery }}"</span>
        </h1>
        <p v-if="data?.meta" class="text-sm text-slate-500 mt-2">
          {{ data.meta.total }} {{ t('matches') }} · {{ data.meta.processing_ms }}ms
        </p>
      </div>

      <div
        v-if="uploadedPreview"
        class="glass rounded-xl p-3 mb-4 inline-flex items-center gap-3"
      >
        <img :src="uploadedPreview" alt="" class="h-16 w-16 object-contain rounded-lg bg-white/10" />
        <p class="text-xs text-slate-400">{{ t('searched_by_photo') }}</p>
      </div>

      <SearchPipeline
        v-if="data?.meta"
        :pipeline="data.pipeline"
        :report="data.meta.source_report"
      />

      <div
        v-if="data?.vision?.description || data?.parsed?.description"
        class="glass rounded-xl p-4 mb-6 border border-violet-500/20"
      >
        <p class="text-xs uppercase tracking-wider text-violet-300 mb-2">{{ t('ai_product_description') }}</p>
        <p class="text-sm text-slate-300 leading-relaxed">
          {{ data.vision?.description || data.parsed.description }}
        </p>
      </div>

      <div v-if="!loading" class="mb-4 max-w-xl -mx-1 sm:mx-0 overflow-visible">
        <SearchScopeChips
          :model-value="activeScope"
          @update:model-value="onScopeChange"
        />
        <p v-if="scopeSummary" class="text-[11px] text-slate-500 mt-2">{{ scopeSummary }}</p>
      </div>

      <div v-if="data?.parsed" class="glass rounded-xl p-4 mb-6 flex flex-wrap gap-2 items-center">
        <span class="text-xs text-slate-400">{{ t('parsed_intent') }}:</span>
        <span class="px-2 py-1 rounded-lg bg-sky-500/20 text-sky-300 text-xs font-medium">
          {{ categoryLabel(data.parsed.category) }}
        </span>
        <span
          v-for="(val, key) in parsedTags"
          :key="key"
          class="px-2 py-1 rounded-lg bg-white/5 text-slate-300 text-xs"
        >
          {{ fieldLabel(key) }}: {{ formatTagValue(val) }}
        </span>
        <span v-if="data.geo" class="ml-auto text-xs text-slate-500">
          {{ data.geo.city }}, {{ data.geo.country }}
        </span>
      </div>

      <div class="lg:grid lg:grid-cols-[240px_1fr] gap-6">
        <DynamicFilters
          v-if="data?.filters?.length"
          :filters="data.filters"
          v-model="activeFilters"
          class="mb-6 lg:mb-0"
          @change="refineSearch"
        />

        <div>
          <div v-if="loading" class="mb-4 text-slate-400 text-sm animate-pulse">{{ t('searching') }}</div>
          <ResultsSkeleton v-if="loading" />
          <p v-else-if="!results.length" class="text-center text-slate-400 py-16 glass rounded-2xl">
            {{ t('no_results') }}
          </p>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <ProductCard
              v-for="product in results"
              :key="product.id + (product.source_key || '')"
              :product="product"
              class="group"
            />
          </div>

          <div v-if="data?.expanded?.marketplaces" class="mt-8 text-center">
            <p class="text-xs text-slate-500 mb-2">{{ t('sources') }}</p>
            <div class="flex flex-wrap justify-center gap-2">
              <span
                v-for="src in data.expanded.marketplaces"
                :key="src"
                class="glass px-3 py-1 rounded-full text-xs text-slate-400"
              >
                {{ src }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, watch, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import SearchPipeline from '../components/SearchPipeline.vue';
import ProductCard from '../components/ProductCard.vue';
import DynamicFilters from '../components/DynamicFilters.vue';
import ResultsSkeleton from '../components/ResultsSkeleton.vue';
import SearchScopeChips from '../components/SearchScopeChips.vue';

const route = useRoute();
const router = useRouter();
const { t, locale, setLocale } = inject('i18n');

const data = ref(null);
const loading = ref(true);
const activeFilters = ref({});
let debounceTimer = null;

const displayQuery = computed(() => {
  if (route.query.has_image === '1' && data.value?.vision?.search_query) {
    return data.value.vision.search_query;
  }
  return route.query.q || '';
});
const results = computed(() => data.value?.results || []);
const uploadedPreview = ref(null);

const activeScope = ref(api.getLocationScope());

const parsedTags = computed(() => {
  if (!data.value?.parsed) return {};
  const skip = ['raw_query', 'category', 'keywords', 'country', 'language_hint', 'description', 'vision', 'search_query'];
  return Object.fromEntries(
    Object.entries(data.value.parsed).filter(([k, v]) => !skip.includes(k) && v != null && v !== '')
  );
});

const scopeSummary = computed(() => {
  const tiers = data.value?.meta?.location_tiers;
  if (!tiers?.length) return '';
  const labels = tiers.map((t) => t.label).filter(Boolean);
  return labels.length ? `${t('search_area')}: ${labels.join(' → ')}` : '';
});

function categoryLabel(cat) {
  const key = `categories.${cat}`;
  const label = t(key);
  return label !== key ? label : cat;
}

function fieldLabel(key) {
  const k = `parsed_fields.${key}`;
  const label = t(k);
  return label !== k ? label : key;
}

function formatTagValue(val) {
  if (Array.isArray(val)) return val.join(', ');
  if (typeof val === 'boolean') return val ? '✓' : '—';
  return String(val);
}

function onScopeChange(scope) {
  activeScope.value = scope;
  api.setLocationScope(scope);
  router.replace({ query: { ...route.query, scope } });
}

async function runSearch() {
  const q = String(route.query.q || '');
  const imageBase64 = api.loadSearchImage();
  const hasImage = route.query.has_image === '1' && imageBase64;

  if (!hasImage && q.length < 3) {
    router.replace({ name: 'home' });
    return;
  }

  if (route.query.locale) {
    setLocale(route.query.locale);
  }

  if (route.query.scope) {
    activeScope.value = String(route.query.scope);
    api.setLocationScope(activeScope.value);
  }

  uploadedPreview.value = hasImage ? `data:image/jpeg;base64,${imageBase64}` : null;
  loading.value = true;

  try {
    data.value = await api.search(
      q || 'find this product',
      mapFilters(activeFilters.value),
      locale.value,
      imageBase64,
      activeScope.value
    );
    api.clearSearchImage();
  } catch (e) {
    console.error(e);
    data.value = { results: [], filters: [], pipeline: [] };
  } finally {
    loading.value = false;
  }
}

function mapFilters(filters) {
  const mapped = { ...filters };
  if (mapped.price != null) {
    mapped.price_max = mapped.price;
    delete mapped.price;
  }
  return mapped;
}

function refineSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(runSearch, 400);
}

watch(() => [route.query.q, route.query.scope], runSearch);
onMounted(runSearch);
</script>
