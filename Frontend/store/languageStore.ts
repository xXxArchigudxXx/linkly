import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { translations } from '../locales';

type Language = 'en' | 'ru';

interface LanguageState {
  language: Language;
  t: typeof translations.en;
  setLanguage: (lang: Language) => void;
}

export const useLanguageStore = create<LanguageState>()(
  persist(
    (set) => ({
      language: 'en',
      t: translations.en,
      setLanguage: (lang) => set({ language: lang, t: translations[lang] }),
    }),
    {
      name: 'language-storage',
      partialize: (state) => ({ language: state.language }), // only persist the language string
      onRehydrateStorage: () => (state) => {
        if (state) {
          // restore the translation object based on the persisted language
          state.t = translations[state.language];
        }
      },
    }
  )
);
