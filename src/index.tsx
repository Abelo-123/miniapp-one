// Include Telegram UI styles first to allow our code override the package CSS.
import '@telegram-apps/telegram-ui/dist/styles.css';

import ReactDOM from 'react-dom/client';
import { StrictMode } from 'react';
import { retrieveLaunchParams } from '@telegram-apps/sdk-react';

import { Root } from '@/components/Root.tsx';
import { EnvUnsupported } from '@/components/EnvUnsupported.tsx';
import { init } from '@/init.ts';

import './index.css';

// Mock the environment in dev mode only (tree-shaken in production)
if (import.meta.env.DEV) {
  await import('./mockEnv.ts');
}

const root = ReactDOM.createRoot(document.getElementById('root')!);

try {
  let launchParams;
  try {
    launchParams = retrieveLaunchParams();
  } catch (err) {
    // Try to load cached launch params from sessionStorage
    const cached = sessionStorage.getItem('paxyo:launch_params');
    if (cached) {
      const parsedParams = new URLSearchParams(cached);
      // Mock the environment with cached parameters
      const { mockTelegramEnv } = await import('@telegram-apps/sdk-react');
      mockTelegramEnv({
        launchParams: parsedParams,
      });
      launchParams = retrieveLaunchParams();
    } else {
      // Fallback to mock environment in production for testing/redirect scenarios
      console.warn('[Init] Launch params missing. Error:', err instanceof Error ? err.message : err);
      console.warn('[Init] Current URL keys:', {
        searchKeys: Array.from(new URLSearchParams(window.location.search).keys()),
        hashKeys: Array.from(new URLSearchParams(window.location.hash.slice(1)).keys()),
      });
      console.warn('[Init] Setting up mock environment fallback.');
      const { mockTelegramEnv, emitEvent } = await import('@telegram-apps/sdk-react');
      const mockTheme = {
        accent_text_color: '#6ab2f2',
        bg_color: '#17212b',
        button_color: '#5288c1',
        button_text_color: '#ffffff',
        destructive_text_color: '#ec3942',
        header_bg_color: '#17212b',
        hint_color: '#708499',
        link_color: '#6ab3f3',
        secondary_bg_color: '#232e3c',
        text_color: '#f5f5f5',
      } as const;
      
      const mockUser = {
        id: 123456789,
        first_name: 'Demo',
        last_name: 'User',
        username: 'demouser',
        language_code: 'en',
      };

      const mockInitData = new URLSearchParams([
        ['auth_date', (Date.now() / 1000 | 0).toString()],
        ['hash', 'mock-hash'],
        ['signature', 'mock-signature'],
        ['user', JSON.stringify(mockUser)],
      ]).toString();

      mockTelegramEnv({
        onEvent(e) {
          if (e[0] === 'web_app_request_theme') {
            return emitEvent('theme_changed', { theme_params: mockTheme });
          }
        },
        launchParams: new URLSearchParams([
          ['tgWebAppThemeParams', JSON.stringify(mockTheme)],
          ['tgWebAppData', mockInitData],
          ['tgWebAppVersion', '8.4'],
          ['tgWebAppPlatform', 'tdesktop'],
        ]),
      });
      
      launchParams = retrieveLaunchParams();
    }
  }

  const { tgWebAppPlatform: platform } = launchParams;
  const debug = (launchParams.tgWebAppStartParam || '').includes('platformer_debug')
    || import.meta.env.DEV;

  // Start init — but render as soon as critical phase completes
  init({
    debug,
    eruda: debug && ['ios', 'android'].includes(platform),
    mockForMacOS: platform === 'macos',
  }).then(() => {
    // Deferred tasks done
  });

  // Render immediately — don't wait for full init
  root.render(
    <StrictMode>
      <Root />
    </StrictMode>,
  );
} catch (e) {
  console.error('[Init] Initialization crashed completely:', e);
  root.render(<EnvUnsupported />);
}

