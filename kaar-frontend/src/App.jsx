import { Outlet, Route, Routes, useLocation } from 'react-router-dom';
import { useEffect } from 'react';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import ChatWidget from './components/ChatWidget';
import AuthModal from './components/AuthModal';
import BookingModal from './components/BookingModal';
import { useUI } from './context/UIContext';
import Home from './pages/Home';
import Trips from './pages/Trips';
import TripDetail from './pages/TripDetail';
import { Companies, CompanyDetail } from './pages/Companies';
import MyTickets from './pages/MyTickets';
import Dashboard from './pages/Dashboard';

function Public() {
  const { pathname } = useLocation();
  useEffect(() => { window.scrollTo(0, 0); }, [pathname]);
  return (<><Navbar /><main><Outlet /></main><Footer /></>);
}

export default function App() {
  const { bookingTrip } = useUI();
  const { pathname } = useLocation();
  const inDash = pathname.startsWith('/admin') || pathname.startsWith('/compagnie/') || pathname === '/compagnie';
  return (
    <>
      <Routes>
        <Route element={<Public />}>
          <Route path="/" element={<Home />} />
          <Route path="/trajets" element={<Trips />} />
          <Route path="/trajets/:id" element={<TripDetail />} />
          <Route path="/compagnies" element={<Companies />} />
          <Route path="/compagnies/:id" element={<CompanyDetail />} />
          <Route path="/mes-billets" element={<MyTickets />} />
        </Route>
        <Route path="/admin" element={<Dashboard scope="admin" />} />
        <Route path="/compagnie" element={<Dashboard scope="company" />} />
      </Routes>
      <AuthModal />
      {bookingTrip && <BookingModal key={bookingTrip.id} />}
      {!inDash && <ChatWidget />}
    </>
  );
}
