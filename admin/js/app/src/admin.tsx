import React from 'react';
import { createRoot } from 'react-dom/client';
import { App } from './components/App';
import './styles.css';

function mount() {
  const el = document.getElementById('hook-explorer-root');
  if (!el) return;
  const root = createRoot(el);
  root.render(<App />);
}

mount();


