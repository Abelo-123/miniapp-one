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
  const launchParams = retrieveLaunchParams();
  const { tgWebAppPlatform: platform } = launchParams;
  const debug = (launchParams.tgWebAppStartParam || '').includes('platformer_debug')
    || import.meta.env.DEV;

  // Start init — but render as soon as critical phase completes
  // (init() now signals ready and renders before viewport/colors finish)
  init({
    debug,
    eruda: debug && ['ios', 'android'].includes(platform),
    mockForMacOS: platform === 'macos',
  }).then(() => {
    // Deferred tasks done — nothing more to do, UI is already rendered
  });

  // Render immediately — don't wait for full init
  root.render(
    <StrictMode>
      <Root />
    </StrictMode>,
  );
} catch (e) {
  root.render(<EnvUnsupported />);
}
