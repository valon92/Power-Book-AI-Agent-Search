import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import ResultsView from '../views/ResultsView.vue';
import HowItWorksView from '../views/HowItWorksView.vue';

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { title: 'Powerbook.ai — AI Semantic Shopping' },
  },
  {
    path: '/how-it-works',
    name: 'how-it-works',
    component: HowItWorksView,
    meta: { title: 'How It Works — Powerbook.ai' },
  },
  {
    path: '/search',
    name: 'search',
    component: ResultsView,
    meta: { title: 'Search Results — Powerbook.ai' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

const titles = {
  home: { en: 'Powerbook.ai — AI Semantic Shopping', sq: 'Powerbook.ai — Blerje semantike me AI' },
  'how-it-works': { en: 'How It Works — Powerbook.ai', sq: 'Si funksionon — Powerbook.ai' },
  search: { en: 'Search Results — Powerbook.ai', sq: 'Rezultatet — Powerbook.ai' },
};

router.afterEach((to) => {
  const lang = document.documentElement.lang === 'sq' ? 'sq' : 'en';
  const routeTitles = titles[to.name];
  document.title = routeTitles?.[lang] || routeTitles?.en || 'Powerbook.ai';
});

export default router;
