import { request } from './client';

const data = (r) => r?.data ?? r;

export const api = {
  // Public
  cities: () => request('/api/cities').then(data),
  companies: () => request('/api/companies').then(data),
  company: (id) => request(`/api/companies/${id}`).then(data),
  trips: (params) => request('/api/trips', { params }).then((r) => r?.data ?? []),
  trip: (id) => request(`/api/trips/${id}`).then(data),
  // ⚠ Non documenté dans openapi.yaml : voir README (plan de places)
  tripSeats: (id) => request(`/api/trips/${id}/seats`).then(data),

  // Auth
  register: (b) => request('/api/auth/register', { method: 'POST', body: b }).then(data),
  login: (b) => request('/api/auth/login', { method: 'POST', body: b }).then(data),
  me: () => request('/api/auth/me').then(data),
  logout: () => request('/api/auth/logout', { method: 'POST' }),

  // Réservations / paiements / billets
  createReservation: (b) => request('/api/reservations', { method: 'POST', body: b }).then(data),
  reservations: () => request('/api/reservations').then(data),
  cancelReservation: (id) => request(`/api/reservations/${id}/cancel`, { method: 'PATCH' }),
  createPayment: (b) => request('/api/payments', { method: 'POST', body: b }).then(data),
  simulatePayment: (b) => request('/api/payments/simulate', { method: 'POST', body: b }).then(data),
  tickets: () => request('/api/tickets').then(data),
  ticketPdf: (id) => request(`/api/tickets/${id}/pdf`, { blob: true }),

  // Chatbot
  chat: (message) => request('/api/chatbot/message', { method: 'POST', body: { message } }),

  // Dashboards
  adminDashboard: () => request('/api/admin/dashboard').then(data),
  companyDashboard: () => request('/api/company/dashboard').then(data),
};
