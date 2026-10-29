import test from 'node:test'
import assert from 'node:assert/strict'
import { webcrypto } from 'node:crypto'
import { beginCheckoutIntent, checkoutOperationKey } from '../../frontend/src/services/checkoutCommand.js'

test('checkout retry preserves one key, ignores client prices and item order, stores no PII', async () => {
  Object.defineProperty(globalThis, 'crypto', { value: webcrypto, configurable: true })
  const map = new Map()
  const storage = { getItem: k => map.get(k), setItem: (k, v) => map.set(k, v), removeItem: k => map.delete(k) }
  const payload = { fullname: 'PRIVATE NAME', phone: 'PRIVATE PHONE', address: 'PRIVATE ADDRESS', items: [
    { product_id: 1, variant_id: 2, quantity: 1, price: 1 }, { product_id: 1, variant_id: 1, quantity: 2, price: 2 },
  ] }
  const first = await checkoutOperationKey(payload, storage)
  const retry = { ...payload, items: [...payload.items].reverse().map(item => ({ ...item, price: 999 })) }
  assert.equal(await checkoutOperationKey(retry, storage), first)
  for (const value of ['PRIVATE NAME', 'PRIVATE PHONE', 'PRIVATE ADDRESS']) assert.equal([...map.values()][0].includes(value), false)
  assert.notEqual(await checkoutOperationKey({ ...payload, address: 'Different address' }, storage), first)
  const second = await checkoutOperationKey(payload, storage)
  beginCheckoutIntent(storage)
  assert.notEqual(await checkoutOperationKey(payload, storage), second)
})
