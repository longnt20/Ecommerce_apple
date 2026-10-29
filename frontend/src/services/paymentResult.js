export async function fetchPaymentOrder(query, api) {
  const id = String(query.order_id || '')
  if (!/^[1-9]\d*$/.test(id)) throw new Error('Missing order reference')
  const { data } = await api.get('/orders/' + id)
  return data.data
}

export function paymentTitle(order) {
  if (!order) return 'Chưa xác minh kết quả'
  if (order.payment_status === 'paid') return 'Thanh toán thành công!'
  if (order.status === 'cancelled') return 'Đơn hàng đã hủy'
  if (order.payment_method.toLowerCase() === 'cod') return 'Đã tiếp nhận đơn COD'
  return 'Đang chờ xác nhận thanh toán'
}
