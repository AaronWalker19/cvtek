// Mock data for the document management system

export type UserRole = 'student' | 'professor' | 'admin';

export interface User {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  license?: string;
}

export interface Comment {
  id: string;
  authorId: string;
  authorName: string;
  content: string;
  createdAt: Date;
}

export interface DocumentFile {
  id: string;
  name: string;
  type: string;
  uploadDate: Date;
  version: string;
  ownerId: string;
  ownerName: string;
  ownerLicense?: string;
  description?: string;
  comments: Comment[];
}

// Mock users
export const mockUsers: User[] = [
  {
    id: 'student1',
    name: 'Mael Valin',
    email: 'mael.valin@etu.unilim.fr',
    role: 'student',
    license: 'But MMI 1'
  },
  {
    id: 'prof1',
    name: 'M. Nival',
    email: 'nival@etu.unilim.fr',
    role: 'professor'
  },
  {
    id: 'admin1',
    name: 'Admin User',
    email: 'admin@unilim.fr',
    role: 'admin'
  }
];

// Mock comments
const mockComments: Comment[] = [
  {
    id: 'c1',
    authorId: 'prof1',
    authorName: 'M. Valin',
    content: 'vous devriez modifier se fichier fgfjkg bfgu bdifjgbfdg ufiugdig bdfigudfbiugfgubh gidfgidug ubguifdbg ifgdiu ghfdui hgfiudhg fg hhgdiuf hgf guhdfiuf iufghdiuh fgfduih gfdghiuf gf guidhfgig dufhgiudgh dfuigh',
    createdAt: new Date('2026-01-27')
  },
  {
    id: 'c2',
    authorId: 'prof1',
    authorName: 'M. niel',
    content: 'vous devriez modifier se fichier fgfjkg bfgu bdifjgbfdg ufiugdig bdfigudfbiugfgubh gidfgidug ubguifdbg ifgdiu ghfdui hgfiudhg fg hhgdiuf hgf guhdfiuf iufghdiuh fgfduih gfdghiuf gf guidhfgig dufhgiudgh dfuigh',
    createdAt: new Date('2026-01-28')
  }
];

// Mock documents
export const mockDocuments: DocumentFile[] = [
  {
    id: 'doc1',
    name: 'Cv premiere année',
    type: 'Cv',
    uploadDate: new Date('2026-01-28'),
    version: '1.0',
    ownerId: 'student1',
    ownerName: 'Mael Valin',
    ownerLicense: 'But MMI 1',
    description: 'CV pour la première année',
    comments: mockComments
  },
  {
    id: 'doc2',
    name: 'Cv premiere année',
    type: 'Cv',
    uploadDate: new Date('2026-01-28'),
    version: '1.0',
    ownerId: 'student1',
    ownerName: 'Mael Valin',
    ownerLicense: 'But MMI 1',
    comments: []
  },
  {
    id: 'doc3',
    name: 'Cv premiere année',
    type: 'Cv',
    uploadDate: new Date('2026-01-28'),
    version: '1.0',
    ownerId: 'student1',
    ownerName: 'Mael Valin',
    ownerLicense: 'But MMI 1',
    comments: []
  },
  {
    id: 'doc4',
    name: 'Cv premiere année',
    type: 'Cv',
    uploadDate: new Date('2026-01-28'),
    version: '1.0',
    ownerId: 'student1',
    ownerName: 'Mael Valin',
    ownerLicense: 'But MMI 1',
    comments: []
  },
  {
    id: 'doc5',
    name: 'Cv premiere année',
    type: 'Cv',
    uploadDate: new Date('2026-01-28'),
    version: '1.0',
    ownerId: 'student1',
    ownerName: 'Mael Valin',
    ownerLicense: 'But MMI 1',
    comments: []
  },
  {
    id: 'doc6',
    name: 'Cv premiere année',
    type: 'Cv',
    uploadDate: new Date('2026-01-28'),
    version: '1.0',
    ownerId: 'student1',
    ownerName: 'Mael Valin',
    ownerLicense: 'But MMI 1',
    comments: []
  }
];

// Current user (can be changed for testing different roles)
export let currentUser: User = mockUsers[0]; // Default to student

export const setCurrentUser = (user: User) => {
  currentUser = user;
};
