import test from 'node:test'
import assert from 'node:assert/strict'
import { webcrypto } from 'node:crypto'
import { fetchPaymentOrder, paymentTitle } from '../../frontend/src/services/paymentResult.js'
import { startGoogleLogin } from '../../frontend/src/services/googleAuth.js'

test('payment result ignores spoofed amount, method and paid flags in URL', async () => {
  const saved = { id: 7, final_amount: '25000000.00', payment_method: 'VNPAY', payment_status: 'unpaid', status: 'pending' }
  const calls = []
  const api = { get: async path => { calls.push(path); return { data: { data: saved } } } }
  const result = await fetchPaymentOrder({ order_id: 7, amount: 1, method: 'COD', payment_status: 'paid' }, api)
  assert.equal(result.final_amount, '25000000.00')
  assert.equal(paymentTitle(result), 'Đang chờ xác nhận thanh toán')
  assert.deepEqual(calls, ['/orders/7'])
  assert.equal(paymentTitle({ ...saved, payment_status: 'paid' }), 'Thanh toán thành công!')
  assert.equal(paymentTitle({ ...saved, payment_method: 'COD' }), 'Đã tiếp nhận đơn COD')
  assert.equal(paymentTitle({ ...saved, status: 'cancelled' }), 'Đơn hàng đã hủy')
})

test('missing/malformed order id and forbidden order never become success', async () => {
  for (const id of ['', '0', '-1', '7/../../users', 'abc']) {
    await assert.rejects(fetchPaymentOrder({ order_id: id }, { get: () => assert.fail('No HTTP expected') }))
  }
  await assert.rejects(fetchPaymentOrder({ order_id: 7 }, { get: async () => { throw new Error('403') } }))
  assert.equal(paymentTitle(null), 'Chưa xác minh kết quả')
})

test('Google login stores proof in tab and sends only its SHA256 challenge', async () => {
  Object.defineProperty(globalThis, 'crypto', { value: webcrypto, configurable: true })
  let verifier, target
  globalThis.sessionStorage = { setItem: (key, value) => { assert.equal(key, 'google_code_verifier'); verifier = value } }
  globalThis.window = { location: { assign: url => { target = new URL(url, 'http://localhost') } } }
  await startGoogleLogin()
  const hash = Buffer.from(await webcrypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier))).toString('base64url')
  assert.equal(verifier.length, 43)
  assert.equal(target.pathname, '/api/auth/google/redirect')
  assert.equal(target.searchParams.get('code_challenge'), hash)
  assert.equal(target.searchParams.has('token'), false)
  assert.equal(target.search.includes(verifier), false)
})
