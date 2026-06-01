import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import StudentDashboard from './pages/StudentDashboard';
import FileView from './pages/fileview/[id]';
import ProfessorDashboard from './pages/ProfessorDashboard';
import AdminDashboard from './pages/AdminDashboard';
import DemoUserSwitcher from './components/DemoUserSwitcher';

function AppRoutes() {
  const { user, loading } = useAuth();

  // Attendre le chargement de l'authentification
  if (loading || !user) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Chargement...</p>
        </div>
      </div>
    );
  }

  return (
    <Routes>
      {/* Student Routes */}
      {user.role === 'student' && (
        <>
          <Route path="/" element={<StudentDashboard />} />
        </>
      )}

      {/* Professor Routes */}
      {user.role === 'professor' && (
        <>
          <Route path="/professor" element={<ProfessorDashboard />} />
          <Route path="/admin" element={<AdminDashboard />} />
        </>
      )}

      {/* Admin Routes */}
      {user.role === 'admin' && (
        <>
          <Route path="/admin" element={<AdminDashboard />} />
          <Route path="/professor" element={<ProfessorDashboard />} />
        </>
      )}

      {/* File View Routes - Available for all authenticated users */}
      <Route path="/file/:fileId" element={<FileView />} />
      <Route path="/professor/file/:fileId" element={<FileView />} />

      {/* Default redirect based on role */}
      <Route
        path="*"
        element={
          <Navigate
            to={
              user.role === 'student'
                ? '/'
                : user.role === 'professor'
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
      <Router basename="/cvtek">
        <div className="size-full">
          <AppRoutes />
          <DemoUserSwitcher />
        </div>
      </Router>
    </AuthProvider>
  );
}