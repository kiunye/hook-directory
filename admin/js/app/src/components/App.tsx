import React, { useState } from 'react';
import { ListView } from './ListView';

const TABS = [
  { id: 'list', label: 'List' },
  { id: 'graph', label: 'Graph' },
  { id: 'timeline', label: 'Timeline' },
  { id: 'docs', label: 'Docs' }
];

export const App: React.FC = () => {
  const [tab, setTab] = useState('list');

  return (
    <div style={{ padding: 16, fontFamily: 'inherit' }}>
      <h1>Hook Explorer</h1>
      <nav style={{ borderBottom: '1px solid #eee', marginBottom: 24, display: 'flex', gap: 12 }}>
        {TABS.map((t) => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            style={{
              background: tab === t.id ? '#222' : '#f9f9f9',
              color: tab === t.id ? '#fff' : '#222',
              border: 0,
              padding: '8px 18px',
              cursor: 'pointer',
              borderRadius: 3,
              fontWeight: tab === t.id ? 'bold' : undefined
            }}
          >
            {t.label}
          </button>
        ))}
      </nav>
      {tab === 'list' && <ListView />}
      {tab !== 'list' && <em style={{ color: '#555' }}>Tab coming soon…</em>}
    </div>
  );
};


