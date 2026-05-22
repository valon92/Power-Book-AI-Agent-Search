<template>
  <footer class="relative z-10 px-4 py-8 text-center text-sm text-slate-500 border-t border-white/5">
    <p>{{ t('footer') }}</p>
    <p class="mt-3">
      <router-link
        to="/how-it-works"
        class="text-sky-400/80 hover:text-sky-300 transition-colors"
      >
        {{ t('how.title') }}
      </router-link>
    </p>
    <p class="mt-2 text-xs text-slate-600">{{ t('affiliate_note') }}</p>
  </footer>
</template>

<script setup>
import { inject } from 'vue';
const { t } = inject('i18n');
</script>
