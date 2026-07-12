import * as Localization from 'expo-localization';
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const resources = {
  'pt-BR': {
    translation: {
      home: {
        greeting: {
          morning: 'Bom dia,',
          afternoon: 'Boa tarde,',
          evening: 'Boa noite,',
        },
        notifications: 'Notificações',
        countdown: 'Faltam {{value}} dias para o Natal',
        draw: {
          eyebrow: 'VOCÊ TIROU',
          inGroup: 'no grupo · {{group}}',
          wishlistLabel: 'LISTA DE DESEJOS DELE',
          budget: 'Orçamento · {{budget}}',
          seeWishes: 'Ver desejos',
          reveal: 'Toque para revelar',
        },
        groups: {
          title: 'Meus grupos',
          seeAll: 'Ver todos',
          members: '{{value}} participantes',
          drawn: 'Sorteado ✓',
          draw: 'Sortear',
          create: 'Criar novo grupo',
        },
        tabs: {
          home: 'Início',
          groups: 'Grupos',
          profile: 'Perfil',
        },
        placeholder: {
          groups: 'Seus grupos de amigo secreto vão aparecer aqui.',
          profile: 'Suas informações e preferências vão aparecer aqui.',
        },
      },
    },
  },
} as const;

void i18n.use(initReactI18next).init({
  resources,
  lng: Localization.getLocales()[0]?.languageTag ?? 'pt-BR',
  fallbackLng: 'pt-BR',
  interpolation: { escapeValue: false },
});

export { i18n };
