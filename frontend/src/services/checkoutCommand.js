const storageKey = 'checkout_command'

export function beginCheckoutIntent(storage = sessionStorage) {
  storage.removeItem(storageKey)
}

// Keep only a digest and operation key in this tab, never shipping details or tokens.
export async function checkoutOperationKey(payload, storage = sessionStorage) {
  const normalized = {
    ...payload,
    items: payload.items.map(({ product_id, variant_id, quantity }) => ({ product_id, variant_id, quantity }))
      .sort((a, b) => a.variant_id - b.variant_id),
  }
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(JSON.stringify(normalized)))
  const fingerprint = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('')
  let saved
  try { saved = JSON.parse(storage.getItem(storageKey)) } catch { /* Ignore corrupt tab state. */ }
  if (saved?.fingerprint === fingerprint && saved?.key) return saved.key
  const key = crypto.randomUUID()
  storage.setItem(storageKey, JSON.stringify({ fingerprint, key }))
  return key
}
