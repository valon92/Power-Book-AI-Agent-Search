<template>
  <article
    class="product-card group/card relative flex flex-col h-full overflow-hidden rounded-xl border border-white/[0.08] bg-slate-900/50 backdrop-blur-sm transition-[border-color,box-shadow,background-color] duration-200 hover:border-sky-500/30 hover:bg-slate-900/75 hover:shadow-[0_10px_32px_-14px_rgba(56,189,248,0.28)]"
  >
    <div class="relative aspect-square sm:aspect-[4/3] overflow-hidden bg-slate-950/60 shrink-0">
      <img
        :src="product.image"
        :alt="displayTitle"
        class="h-full w-full object-cover transition-transform duration-300 group-hover/card:scale-[1.04]"
        loading="lazy"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/10 to-transparent pointer-events-none" />

      <span
        class="absolute top-1.5 right-1.5 px-1 py-px rounded text-[9px] font-semibold tabular-nums backdrop-blur-md"
        :class="scoreClass"
      >
        {{ product.match_score }}%
      </span>

      <span
        v-if="product.live"
        class="absolute top-1.5 left-1.5 px-1 py-px rounded text-[8px] uppercase tracking-wide bg-emerald-500/90 text-black font-semibold"
      >
        Live
      </span>
      <span
        v-else-if="product.sponsored"
        class="absolute top-1.5 left-1.5 px-1 py-px rounded text-[8px] uppercase tracking-wide bg-amber-500/90 text-black font-semibold"
      >
        Ad
      </span>

      <span
        v-if="product.source"
        class="absolute bottom-1.5 left-1.5 max-w-[calc(100%-2.5rem)] truncate px-1.5 py-0.5 rounded text-[9px] font-medium bg-black/60 text-slate-200 backdrop-blur-sm border border-white/10"
      >
        {{ product.source }}
      </span>
    </div>

    <div class="relative flex flex-col flex-1 p-2 gap-1 min-h-0 pr-9">
      <h3
        class="text-[11px] leading-[1.35] font-medium text-slate-100 line-clamp-2 tracking-tight"
        :title="displayTitle"
      >
        {{ displayTitle }}
      </h3>

      <div class="flex items-center gap-1.5 flex-wrap mt-auto">
        <p class="text-[13px] font-semibold text-sky-400 tabular-nums leading-none">
          {{ formatPrice(product.price, product.currency) }}
        </p>
        <span
          v-if="product.offer_count > 1"
          class="px-1 py-px rounded text-[8px] font-medium text-sky-300/90 bg-sky-500/10 border border-sky-500/15"
        >
          +{{ product.offer_count - 1 }}
        </span>
      </div>

      <p
        v-if="product.location"
        class="text-[9px] text-slate-500 truncate leading-tight"
      >
        {{ product.location }}
      </p>
    </div>

    <a
      :href="product.url"
      target="_blank"
      rel="noopener noreferrer sponsored"
      class="absolute bottom-2 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-white/10 bg-slate-800/80 text-slate-400 backdrop-blur-sm transition-all duration-200 hover:border-sky-500/40 hover:bg-sky-500/15 hover:text-sky-300 group-hover/card:translate-x-0.5 group-hover/card:border-sky-500/30"
      :aria-label="listingAriaLabel"
      @click.stop
    >
      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
      </svg>
    </a>
  </article>
</template>

<script setup>
import { computed, inject } from 'vue';

const props = defineProps({
  product: { type: Object, required: true },
});

const { t } = inject('i18n');

const scoreClass = computed(() => {
  const s = props.product.match_score || 0;
  if (s >= 90) return 'bg-emerald-500/85 text-white';
  if (s >= 80) return 'bg-sky-500/85 text-white';
  return 'bg-slate-700/90 text-slate-200';
});

const displayTitle = computed(() => stripStoreFromTitle(props.product.title, props.product.source));

const listingAriaLabel = computed(() => {
  const label = t('buy');
  return `${label}: ${displayTitle.value}`;
});

function stripStoreFromTitle(title, source) {
  if (!title) return '';
  let result = title.trim();
  const sourceKey = normalizeKey(source);

  for (const sep of [' — ', ' · ', ' - ']) {
    const idx = result.lastIndexOf(sep);
    if (idx <= 0) continue;

    const suffix = result.slice(idx + sep.length);
    const suffixKey = normalizeKey(suffix);

    if (!suffixKey) continue;

    const matchesSource =
      sourceKey &&
      (suffixKey.includes(sourceKey) ||
        sourceKey.includes(suffixKey) ||
        sharesToken(suffixKey, sourceKey));

    const looksLikeStore =
      /(merrjep|dyqani|pazar\s*3|gjirafa|neptun|aza\s*electronics|focus\s*electronics|pc\s*store|sparkle|tregu\.com|ebay|amazon|etsy|auto\s*scout|mobile\.de)/i.test(
        suffix
      );

    if (matchesSource || looksLikeStore) {
      result = result.slice(0, idx).trim();
      break;
    }
  }

  return result.replace(/\s·\s*#\d+\s*$/i, '').replace(/\s{2,}/g, ' ').trim();
}

function normalizeKey(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '');
}

function sharesToken(a, b) {
  if (!a || !b || a.length < 4 || b.length < 4) return false;
  const min = Math.min(a.length, b.length, 8);
  return a.slice(0, min) === b.slice(0, min);
}

function formatPrice(price, currency = 'EUR') {
  return new Intl.NumberFormat('en-EU', { style: 'currency', currency }).format(price);
}
</script>
