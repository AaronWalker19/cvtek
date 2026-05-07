import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import StudentDashboard from './pages/StudentDashboard';
import FileView from './pages/fileview/[id]';
import ProfessorDashboard from './pages/ProfessorDashboard';
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
        </>
      )}

      {/* Professor Routes */}
      {currentUser.role === 'professor' && (
        <>
          <Route path="/professor" element={<ProfessorDashboard />} />
          <Route path="/admin" element={<AdminDashboard />} />
        </>
      )}

      {/* Admin Routes */}
      {currentUser.role === 'admin' && (
        <>
          <Route path="/admin" element={<AdminDashboard />} />
          <Route path="/professor" element={<ProfessorDashboard />} />
        </>
      )}

      {/* File View Routes - Available for all authenticated users */}
      <Route path="/file/:fileId" element={<FileView />} />
      <Route path="/professor/file/:fileId" element={<FileView />} />

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