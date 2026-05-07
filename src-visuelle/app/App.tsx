import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import StudentDashboard from './pages/StudentDashboard';
import StudentFileView from './pages/StudentFileView';
import ProfessorDashboard from './pages/ProfessorDashboard';
import ProfessorFileView from './pages/ProfessorFileView';
import AdminDashboard from './pages/AdminDashboard';
import Profile from './pages/Profile';

function AppRoutes() {
  const { currentUser } = useAuth();

  return (
    <Routes>
      {/* Student Routes */}
      {currentUser.role === 'student' && (
        <>
          <Route path="/" element={<StudentDashboard />} />
          <Route path="/file/:fileId" element={<StudentFileView />} />
        </>
      )}

      {/* Professor Routes */}
      {currentUser.role === 'professor' && (
        <>
          <Route path="/professor" element={<ProfessorDashboard />} />
          <Route path="/professor/file/:fileId" element={<ProfessorFileView />} />
          <Route path="/admin" element={<AdminDashboard />} />
        </>
      )}

      {/* Admin Routes */}
      {currentUser.role === 'admin' && (
        <>
          <Route path="/admin" element={<AdminDashboard />} />
          <Route path="/professor" element={<ProfessorDashboard />} />
          <Route path="/professor/file/:fileId" element={<ProfessorFileView />} />
        </>
      )}

      {/* Common Routes */}
      <Route path="/profile" element={<Profile />} />

      {/* Default redirect based on role */}
      <Route
        path="*"
        element={
          <Navigate
            to={
              currentUser.role === 'student'
                ? '/'
                : currentUser.role === 'professor'
                ? '/professor'
                : '/admin'
            }
            replace
          />
        }
      />
    </Routes>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <Router>
        <div className="size-full">
          <AppRoutes />
        </div>
      </Router>
    </AuthProvider>
  );
}