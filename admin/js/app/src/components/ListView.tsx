import React, { useEffect, useMemo, useState } from 'react';

type HookRow = {
  id?: number;
  hook_name: string;
  hook_type: string;
  file_path?: string;
  source_type?: string;
  source_name?: string;
};

type ApiResponse = {
  total: number;
  page: number;
  perPage: number;
  items: HookRow[];
};

function useDebounced<T>(value: T, delayMs: number) {
  const [debounced, setDebounced] = useState(value);
  useEffect(() => {
    const id = setTimeout(() => setDebounced(value), delayMs);
    return () => clearTimeout(id);
  }, [value, delayMs]);
  return debounced;
}

export const ListView: React.FC = () => {
  const [q, setQ] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<ApiResponse | null>(null);
  const debouncedQ = useDebounced(q, 250);

  const restBase = (window as any).HookExplorer?.restUrl?.replace(/\/$/, '') ?? '';
  const nonce = (window as any).HookExplorer?.nonce;

  const listUrl = useMemo(() => {
    const url = new URL(restBase + '/hooks');
    if (debouncedQ) url.searchParams.set('q', debouncedQ);
    url.searchParams.set('page', String(page));
    url.searchParams.set('per_page', String(perPage));
    return url.toString();
  }, [restBase, debouncedQ, page, perPage]);

  useEffect(() => {
    let aborted = false;
    async function run() {
      if (!restBase) return;
      setLoading(true);
      try {
        const res = await fetch(listUrl, { headers: { 'X-WP-Nonce': nonce } });
        const json = await res.json();
        if (!aborted) setData(json);
      } catch (e) {
        if (!aborted) setData({ total: 0, page: 1, perPage, items: [] });
      } finally {
        if (!aborted) setLoading(false);
      }
    }
    run();
    return () => {
      aborted = true;
    };
  }, [listUrl, nonce, restBase, perPage]);

  const total = data?.total ?? 0;
  const pageCount = Math.max(1, Math.ceil(total / (data?.perPage || perPage)));

  const [open, setOpen] = useState<Record<string, boolean>>({});

  return (
    <div>
      <div style={{ display: 'flex', gap: 12, alignItems: 'center', marginBottom: 12 }}>
        <input
          type="search"
          placeholder="Search hooks…"
          value={q}
          onChange={(e) => {
            setQ(e.target.value);
            setPage(1);
          }}
          style={{ padding: 8, minWidth: 240 }}
        />
        <label style={{ color: '#555' }}>
          Per page:{' '}
          <select value={perPage} onChange={(e) => { setPerPage(parseInt(e.target.value, 10)); setPage(1); }}>
            {[20, 50, 100].map(n => (
              <option key={n} value={n}>{n}</option>
            ))}
          </select>
        </label>
        {loading && <span style={{ color: '#555' }}>Loading…</span>}
        {!loading && <span style={{ color: '#555' }}>{total.toLocaleString()} results</span>}
      </div>

      <div style={{ overflowX: 'auto' }}>
        <table style={{ width: '100%', borderCollapse: 'collapse' }}>
          <thead>
            <tr>
              <th style={{ textAlign: 'left', borderBottom: '1px solid #eee', padding: 8, width: 40 }}></th>
              <th style={{ textAlign: 'left', borderBottom: '1px solid #eee', padding: 8 }}>Hook</th>
              <th style={{ textAlign: 'left', borderBottom: '1px solid #eee', padding: 8 }}>Type</th>
              <th style={{ textAlign: 'left', borderBottom: '1px solid #eee', padding: 8 }}>Source</th>
              <th style={{ textAlign: 'left', borderBottom: '1px solid #eee', padding: 8 }}>File</th>
            </tr>
          </thead>
          <tbody>
            {(data?.items ?? []).map((row, idx) => {
              const key = row.hook_name + '-' + idx;
              const isOpen = !!open[key];
              const toggle = () => setOpen((s) => ({ ...s, [key]: !isOpen }));
              const src = [row.source_type, row.source_name].filter(Boolean).join(': ');
              return (
                <React.Fragment key={key}>
                  <tr>
                    <td style={{ padding: 8, borderBottom: '1px solid #f2f2f2' }}>
                      <button onClick={toggle} aria-label="Expand" style={{ border: 0, background: 'transparent', cursor: 'pointer' }}>
                        {isOpen ? '▾' : '▸'}
                      </button>
                    </td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f2f2f2' }}>{row.hook_name}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f2f2f2' }}>{row.hook_type}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f2f2f2' }}>{src}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f2f2f2', color: '#555' }}>{row.file_path ?? ''}</td>
                  </tr>
                  {isOpen && (
                    <tr>
                      <td colSpan={5} style={{ padding: 12, background: '#fafafa', borderBottom: '1px solid #f0f0f0' }}>
                        <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
                          <div>
                            <strong>Hook:</strong>
                            <div style={{ fontFamily: 'monospace' }}>{row.hook_name}</div>
                          </div>
                          <div>
                            <strong>Type:</strong>
                            <div>{row.hook_type}</div>
                          </div>
                          <div>
                            <strong>Source:</strong>
                            <div>{src || '—'}</div>
                          </div>
                          <div>
                            <strong>File:</strong>
                            <div style={{ color: '#555' }}>{row.file_path || '—'}</div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  )}
                </React.Fragment>
              );
            })}
            {(!data || (data.items ?? []).length === 0) && !loading && (
              <tr>
                <td colSpan={4} style={{ padding: 16, color: '#777' }}>No hooks found.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginTop: 12 }}>
        <button disabled={page <= 1} onClick={() => setPage(p => Math.max(1, p - 1))}>Prev</button>
        <span>Page {data?.page ?? page} / {pageCount}</span>
        <button disabled={page >= pageCount} onClick={() => setPage(p => Math.min(pageCount, p + 1))}>Next</button>
      </div>
    </div>
  );
};


