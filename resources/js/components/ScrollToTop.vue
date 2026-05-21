<template>
  <Transition name="top-btn">
    <button
      v-show="visible"
      type="button"
      class="scroll-to-top"
      :aria-label="t('back_to_top')"
      @click="scrollToTop"
    >
      <svg
        class="w-5 h-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M12 19V5M5 12l7-7 7 7" />
      </svg>
      <span class="text-xs font-semibold tracking-wide">{{ t('top') }}</span>
    </button>
  </Transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted, inject } from 'vue';

const { t } = inject('i18n');
const visible = ref(false);

function onScroll() {
  visible.value = window.scrollY > 320;
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
});
</script>

<style scoped>
.scroll-to-top {
  @apply fixed z-50 bottom-6 right-4 sm:right-6
    flex flex-col items-center justify-center gap-0.5
    w-14 h-14 rounded-2xl
    bg-gradient-to-br from-sky-500/90 to-violet-600/90
    text-white shadow-lg shadow-sky-500/30
    border border-white/20 backdrop-blur-md
    transition-all duration-300
    hover:from-sky-400 hover:to-violet-500 hover:scale-105 hover:shadow-xl hover:shadow-sky-500/40
    focus:outline-none focus:ring-2 focus:ring-sky-400/60;
}

.top-btn-enter-active,
.top-btn-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.top-btn-enter-from,
.top-btn-leave-to {
  opacity: 0;
  transform: translateY(1rem);
}
</style>
