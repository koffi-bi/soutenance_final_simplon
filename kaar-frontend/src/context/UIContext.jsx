import { createContext, useContext, useState } from 'react';

const UICtx = createContext(null);
export const useUI = () => useContext(UICtx);

export function UIProvider({ children }) {
  const [chatOpen, setChatOpen] = useState(false);
  const [bookingTrip, setBookingTrip] = useState(null);
  const [authMode, setAuthMode] = useState(null); // 'login' | 'register' | null
  const [messages, setMessages] = useState([]);

  return (
    <UICtx.Provider
      value={{
        chatOpen, setChatOpen, openChat: () => setChatOpen(true), closeChat: () => setChatOpen(false),
        bookingTrip, openBooking: (trip) => setBookingTrip(trip), closeBooking: () => setBookingTrip(null),
        authMode, openAuth: (m = 'login') => setAuthMode(m), closeAuth: () => setAuthMode(null),
        messages, setMessages,
      }}
    >
      {children}
    </UICtx.Provider>
  );
}
