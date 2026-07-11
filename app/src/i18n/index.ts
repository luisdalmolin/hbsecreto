import * as Localization from 'expo-localization';
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

const resources = {
  'pt-BR': {
    translation: {
      home: {
        title: 'CPX Secreto',
        description: 'Organize seus amigos secretos de um jeito simples e especial.',
        apiStatus: 'A conexão com a API estará disponível em breve.',
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
